<?php
// ..backend/truck_register.php
require 'dbcon.php'; // Your Firebase Realtime DB connection

require __DIR__ . '/vendor/autoload.php';
use Google\Cloud\Storage\StorageClient;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = [];

    // Grab form inputs
    $vehicleName      = trim($_POST['vehicleName']);
    $plateNumber      = trim($_POST['plateNumber']);
    $vehicleDriver    = trim($_POST['vehicleDriver']);
    $truckType        = isset($_POST['truckType']) ? trim($_POST['truckType']) : ''; // NEW: Truck Type
    $garbageCollectors = isset($_POST['garbageCollectors']) ? $_POST['garbageCollectors'] : []; // May be empty for sewage trucks

    // ------------------------------
    // ADDED: Retrieve the 'kmPerLiter' input
    // ------------------------------
    $kmPerLiter = isset($_POST['kmPerLiter']) ? trim($_POST['kmPerLiter']) : '';
    // ------------------------------

    // Validate required fields
    if (empty($vehicleName) || empty($plateNumber) || empty($vehicleDriver) || empty($truckType)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Vehicle Name, Plate Number, Vehicle Driver, and Truck Type are required.'
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

    // For Garbage Trucks, validate garbage collectors; for Sewage Trucks, ignore them.
    if ($truckType === 'Garbage Truck') {
        // Force minimum 1 collector
        if (count($garbageCollectors) < 1) {
            echo json_encode([
                'status' => 'error',
                'message' => 'At least 1 garbage collector is required for Garbage Trucks.'
            ]);
            exit();
        }
        // Force maximum 6 collectors
        if (count($garbageCollectors) > 6) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Maximum of 6 garbage collectors allowed.'
            ]);
            exit();
        }

        // Filter out "Unassigned" values (case-insensitive)
        $filteredCollectors = array_filter($garbageCollectors, function($name) {
            return strtolower(trim($name)) !== 'unassigned';
        });

        // Check for duplicate garbage collectors (case-insensitive)
        $lowerCollectors = array_map('strtolower', array_map('trim', $filteredCollectors));
        if (count($lowerCollectors) !== count(array_unique($lowerCollectors))) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Duplicate garbage collectors are not allowed (case-insensitive).'
            ]);
            exit();
        }

        // Check in database if garbage collectors are already assigned
        $existingTrucks = $database->getReference('trucks')->getValue();
        $alreadyAssignedCollectors = [];

        if ($existingTrucks) {
            foreach ($existingTrucks as $truck) {
                if (isset($truck['garbageCollectors']) && is_array($truck['garbageCollectors'])) {
                    foreach ($truck['garbageCollectors'] as $collector) {
                        $normalized = strtolower(trim($collector));
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
        // 1) Handle the truck image
        if (isset($_FILES['vehicleImage']) && $_FILES['vehicleImage']['error'] === UPLOAD_ERR_OK) {
            $imgTmpPath = $_FILES['vehicleImage']['tmp_name'];
            $imgName    = $_FILES['vehicleImage']['name'];

            // Validate image type
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
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

            // ------------------------------------------------------
            // NEW CLOUDINARY LOGIC (ADDED WITHOUT REMOVING ORIGINAL)
            // ------------------------------------------------------
            // 1. Get Cloudinary instance (defined in dbcon.php)
            $cloudinary = getCloudinaryInstance();

            // 2. Define upload options (similar to your folder structure)
            $uploadOptions = [
                'folder'        => 'upload_trucks',
                'public_id'     => $uniquePart,
                'overwrite'     => true,
                'resource_type' => 'image'
            ];

            // 3. Upload to Cloudinary
            $uploadResult = $cloudinary->uploadApi()->upload($imgTmpPath, $uploadOptions);

            // 4. Retrieve the secure URL from Cloudinary
            $truckImgUrl = $uploadResult['secure_url'];
            // ------------------------------------------------------
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'No truck image uploaded or an error occurred.'
            ]);
            exit();
        }

        // 2) Build the new truck object
        $newTruck = [
            'vehicleName'       => $vehicleName,
            'plateNumber'       => $plateNumber,
            'vehicleDriver'     => $vehicleDriver,
            'truckType'         => $truckType, // NEW: Include Truck Type
            'garbageCollectors' => $garbageCollectors, // Array (empty for Sewage Trucks)
            'imageUrl'          => $truckImgUrl  // Points to Cloudinary
        ];

        // ------------------------------
        // ADDED: Insert the 'kmPerLiter' property
        // ------------------------------
        $newTruck['kmPerLiter'] = floatval($kmPerLiter);
        // ------------------------------

        // 3) Save to "trucks" node in Firebase
        $database->getReference('trucks')->push($newTruck);

        echo json_encode([
            'status' => 'success',
            'message' => 'Truck created successfully!'
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
    exit();
}
