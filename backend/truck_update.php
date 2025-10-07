<?php
// ..backend/truck_update.php
require 'dbcon.php'; // Your Firebase Realtime DB connection
require __DIR__ . '/vendor/autoload.php';

use Google\Cloud\Storage\StorageClient;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Gather form data
    $truckId           = trim($_POST['truckId']);
    $vehicleName       = trim($_POST['vehicleName']);
    $plateNumber       = trim($_POST['plateNumber']);
    $vehicleDriver     = trim($_POST['vehicleDriver']);
    $truckType         = isset($_POST['truckType']) ? trim($_POST['truckType']) : '';
    // Garbage collectors is an array: 'garbageCollectors[]'
    $garbageCollectors = isset($_POST['garbageCollectors']) ? $_POST['garbageCollectors'] : [];

    // ------------------------------
    // ADDED: Retrieve 'kmPerLiter' from POST
    // ------------------------------
    $kmPerLiter = isset($_POST['kmPerLiter']) ? trim($_POST['kmPerLiter']) : '';
    // ------------------------------

    // Basic validation
    if (empty($truckId) || empty($vehicleName) || empty($plateNumber) || empty($vehicleDriver) || empty($truckType)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Truck ID, Vehicle Name, Plate Number, Vehicle Driver, and Truck Type are required.'
        ]);
        exit();
    }

    // Validate allowed truck types
    $allowedTruckTypes = ['Garbage Truck', 'Sewage Truck'];
    if (!in_array($truckType, $allowedTruckTypes)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid truck type provided.'
        ]);
        exit();
    }

    // Conditional validations for garbage collectors
    if ($truckType === 'Garbage Truck') {
        // Min 1, max 6 collectors
        if (count($garbageCollectors) < 1) {
            echo json_encode([
                'status' => 'error',
                'message' => 'At least 1 garbage collector is required for Garbage Trucks.'
            ]);
            exit();
        }
        if (count($garbageCollectors) > 6) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Maximum of 6 garbage collectors allowed.'
            ]);
            exit();
        }

        // Filter out any collector that is "Unassigned" (case-insensitive)
        $filteredCollectors = array_filter($garbageCollectors, function($name) {
            return strtolower(trim($name)) !== 'unassigned';
        });

        // Check for duplicate garbage collectors (case-insensitive) among the filtered collectors
        $lowerCollectors = array_map(function($name) {
            return strtolower(trim($name));
        }, $filteredCollectors);
        if (count($lowerCollectors) !== count(array_unique($lowerCollectors))) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Duplicate garbage collectors are not allowed (case-insensitive).'
            ]);
            exit();
        }

        // Check in database if any of these collectors are already assigned to another truck
        $existingTrucks = $database->getReference('trucks')->getValue();
        $alreadyAssignedCollectors = [];

        if ($existingTrucks) {
            foreach ($existingTrucks as $key => $truck) {
                // Exclude the current truck being updated
                if ($key === $truckId) {
                    continue;
                }
                if (isset($truck['garbageCollectors']) && is_array($truck['garbageCollectors'])) {
                    foreach ($truck['garbageCollectors'] as $collector) {
                        $normalized = strtolower(trim($collector));
                        // Exclude "unassigned"
                        if ($normalized !== 'unassigned') {
                            $alreadyAssignedCollectors[] = $normalized;
                        }
                    }
                }
            }
        }

        foreach ($filteredCollectors as $collector) {
            if (in_array(strtolower(trim($collector)), $alreadyAssignedCollectors)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Garbage collector "' . $collector . '" is already assigned to another truck.'
                ]);
                exit();
            }
        }
    } else {
        // For Sewage Trucks, ignore garbage collectors
        $garbageCollectors = [];
    }

    // ------------------------------
    // ADDED: Validate the kmPerLiter input
    // ------------------------------
    if ($kmPerLiter !== '') {
        if (!is_numeric($kmPerLiter) || floatval($kmPerLiter) < 0) {
            echo json_encode([
                'status' => 'error',
                'message' => 'km/l must be a non-negative numeric value.'
            ]);
            exit();
        }
    } else {
        // If empty, default to 0 (or handle it as needed)
        $kmPerLiter = 0;
    }
    // ------------------------------

    try {
        // 1) Retrieve existing truck from DB
        $truckPath = 'trucks/' . $truckId;
        $truckRef  = $database->getReference($truckPath)->getValue();

        if (!$truckRef) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Truck does not exist or invalid truckId.'
            ]);
            exit();
        }

        // 2) Check for new image
        $oldImageUrl = isset($truckRef['imageUrl']) ? $truckRef['imageUrl'] : '';
        $newImageUrl = null;

        if (isset($_FILES['vehicleImage']) && $_FILES['vehicleImage']['error'] === UPLOAD_ERR_OK) {
            // Optionally delete old image from storage
            if (!empty($oldImageUrl)) {
                deleteOldTruckImage($oldImageUrl);
            }

            // --------------------------------------------
            // NEW CLOUDINARY LOGIC
            // --------------------------------------------
            $imgTmpPath = $_FILES['vehicleImage']['tmp_name'];
            $imgName    = $_FILES['vehicleImage']['name'];

            // Validate image type
            $allowedTypes = ['image/jpeg','image/png','image/gif'];
            $imgType = mime_content_type($imgTmpPath);
            if (!in_array($imgType, $allowedTypes)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Invalid image type. Only JPG, PNG, and GIF are allowed.'
                ]);
                exit();
            }

            // Create a unique file name
            $uniquePart  = uniqid('truck_', true);
            $extension   = pathinfo($imgName, PATHINFO_EXTENSION);

            // Get the Cloudinary instance from dbcon.php
            $cloudinary = getCloudinaryInstance();

            // Upload options
            $uploadOptions = [
                'folder'        => 'upload_trucks',
                'public_id'     => $uniquePart,
                'overwrite'     => true,
                'resource_type' => 'image'
            ];

            // Upload to Cloudinary
            $uploadResult = $cloudinary->uploadApi()->upload($imgTmpPath, $uploadOptions);

            // Get the secure URL
            $newImageUrl = $uploadResult['secure_url'];
            // --------------------------------------------
        }

        // 3) Prepare updated data
        $updateData = [
            'vehicleName'       => $vehicleName,
            'plateNumber'       => $plateNumber,
            'vehicleDriver'     => $vehicleDriver,
            'truckType'         => $truckType,              // NEW: Update Truck Type
            'garbageCollectors' => $garbageCollectors       // Array (empty for Sewage Trucks)
        ];
        if ($newImageUrl) {
            $updateData['imageUrl'] = $newImageUrl;
        }

        // ------------------------------
        // ADDED: Insert the 'kmPerLiter'
        // ------------------------------
        $updateData['kmPerLiter'] = floatval($kmPerLiter);
        // ------------------------------

        // 4) Update truck data in Realtime DB
        $database->getReference($truckPath)->update($updateData);

        echo json_encode([
            'status' => 'success',
            'message' => 'Truck updated successfully!'
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
    exit();
}

/**
 * Deletes the old truck image from *Cloudinary* 
 * (ORIGINAL Firebase Storage logic is commented below).
 */
function deleteOldTruckImage($imageUrl) {
    if (empty($imageUrl)) return;

    // ------------------------------------------------
    // NEW LOGIC: DELETE FROM CLOUDINARY
    // ------------------------------------------------
    // 1) Parse the URL to find the public ID
    $parsed = parse_url($imageUrl);
    if (!isset($parsed['path'])) {
        return;
    }

    // Example Cloudinary URL format:
    // https://res.cloudinary.com/<cloud_name>/image/upload/v<timestamp>/<folder>/<filename>.ext

    $pathParts = explode('/', trim($parsed['path'], '/'));
    // e.g. ["<cloud_name>", "image", "upload", "v<version>", "<folder>", "<filename>.ext"]
    if (count($pathParts) < 5) {
        return;
    }

    // The first 3 parts might be: [cloud_name, "image", "upload"]
    // The 4th might be the version (e.g. "v123456")
    $startIndex = 3;
    if (strpos($pathParts[3], 'v') === 0) {
        $startIndex = 4;
    }

    // Rebuild the remainder to get the public ID (minus file extension)
    $publicIdParts = array_slice($pathParts, $startIndex);
    $lastPart       = array_pop($publicIdParts);
    $fileNoExt      = pathinfo($lastPart, PATHINFO_FILENAME);
    $publicIdParts[] = $fileNoExt;
    $publicId = implode('/', $publicIdParts);

    // 2) Get Cloudinary instance
    $cloudinary = getCloudinaryInstance();

    try {
        // 3) Destroy the resource by public ID
        $cloudinary->uploadApi()->destroy($publicId, ['invalidate' => true]);
    } catch (Exception $e) {
        error_log('Cloudinary deletion error: ' . $e->getMessage());
    }
}