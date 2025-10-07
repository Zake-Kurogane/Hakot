<?php
require 'dbcon.php'; // Your Firebase Realtime DB connection
require __DIR__ . '/vendor/autoload.php';

use Google\Cloud\Storage\StorageClient;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create a response array
    $response = [];

    // Extract form data
    $driverId        = trim($_POST['driverId']);
    $fullName        = trim($_POST['fullName']);
    $username        = strtolower(trim($_POST['username']));
    $password        = trim($_POST['password']);
    $confirmPassword = trim($_POST['confirmPassword']);

    // Basic checks
    if (empty($driverId) || empty($fullName) || empty($username)) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Driver ID, Full Name, and Username are required.'
        ]);
        exit();
    }

    try {
        // 1) Retrieve existing driver from Realtime DB
        $driverPath = 'drivers/' . $driverId;
        $driverRef  = $database->getReference($driverPath)->getValue();

        if (!$driverRef) {
            echo json_encode([
                'status'  => 'error',
                'message' => 'Driver does not exist or invalid driverId.'
            ]);
            exit();
        }

        // 2) Handle password (only update if provided)
        $hashedPassword = null;
        if (!empty($password) || !empty($confirmPassword)) {
            if ($password !== $confirmPassword) {
                echo json_encode([
                    'status'  => 'error',
                    'message' => 'Passwords do not match.'
                ]);
                exit();
            }
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        }

        // We'll store the old imageUrl to delete if a new image is uploaded
        $oldImageUrl = isset($driverRef['imageUrl']) ? $driverRef['imageUrl'] : '';

        // 3) Check if new image was uploaded
        $newImageUrl = null;
        if (isset($_FILES['driverImage']) && $_FILES['driverImage']['error'] === UPLOAD_ERR_OK) {
            // Delete the old image from Storage if it exists
            if (!empty($oldImageUrl)) {
                deleteImageFromStorage($oldImageUrl);
            }

            // ------------------------------------------------------------
            // ORIGINAL FIREBASE STORAGE LOGIC (COMMENTED, DO NOT REMOVE)
            // ------------------------------------------------------------
            /*
            // Now handle the new image upload
            $imageTmpPath = $_FILES['driverImage']['tmp_name'];
            $imageName    = $_FILES['driverImage']['name'];

            // Validate image type
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $imageType    = mime_content_type($imageTmpPath);
            if (!in_array($imageType, $allowedTypes)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Invalid image type. Only JPG, PNG, and GIF are allowed.'
                ]);
                exit();
            }

            // Generate a unique name for the new image
            $uniquePart   = uniqid('driver_', true);
            $extension    = pathinfo($imageName, PATHINFO_EXTENSION);

            // "Folder" prefix if desired
            $folderName   = 'upload_drivers/';
            // Full path in the bucket
            $storagePath  = $folderName . $uniquePart . '.' . $extension;

            // Upload to Firebase Storage
            $storage = new StorageClient([
                'keyFilePath' => __DIR__ . '/strp-1cbd6-firebase-adminsdk-75ypw-0f127e847c.json'
            ]);
            // Use your specified bucket
            $bucketName   = 'strp-1cbd6.firebasestorage.app';
            $bucket       = $storage->bucket($bucketName);

            // Upload
            $object = $bucket->upload(
                fopen($imageTmpPath, 'r'),
                [
                    'name' => $storagePath,
                    'predefinedAcl' => 'publicRead',
                ]
            );

            // Build the new image URL
            $newImageUrl = "https://storage.googleapis.com/$bucketName/$storagePath";
            */

            // ------------------------------------------------------------
            // NEW CLOUDINARY LOGIC (INSERTED)
            // ------------------------------------------------------------
            $imageTmpPath = $_FILES['driverImage']['tmp_name'];
            $imageName    = $_FILES['driverImage']['name'];

            // Validate image type (same validation used above)
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $imageType    = mime_content_type($imageTmpPath);
            if (!in_array($imageType, $allowedTypes)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Invalid image type. Only JPG, PNG, and GIF are allowed.'
                ]);
                exit();
            }

            // Generate a unique name
            $uniquePart   = uniqid('driver_', true);
            $extension    = pathinfo($imageName, PATHINFO_EXTENSION);

            // We'll maintain the idea of a folder, "upload_drivers"
            $cloudinaryFolder = 'upload_drivers';

            // Get Cloudinary instance (defined in dbcon.php)
            $cloudinary = getCloudinaryInstance();

            // Upload to Cloudinary
            $uploadOptions = [
                'folder'        => $cloudinaryFolder,
                'public_id'     => $uniquePart,
                'overwrite'     => true,
                'resource_type' => 'image'
            ];
            $uploadResult = $cloudinary->uploadApi()->upload($imageTmpPath, $uploadOptions);

            // Secure URL from Cloudinary
            $newImageUrl = $uploadResult['secure_url'];
        }

        // 4) Prepare updated data
        $updateData = [
            'fullName' => $fullName,
            'username' => $username,
        ];

        if ($hashedPassword) {
            $updateData['password'] = $hashedPassword;
        }
        if ($newImageUrl) {
            $updateData['imageUrl'] = $newImageUrl;
        }

        // 5) Update driver record in Firebase
        $database->getReference($driverPath)->update($updateData);

        // 6) Update associated truck record(s) with the new driver full name.
        // This function looks for a field called 'vehicleDriver' in each truck record and updates it if it matches the old name.
        updateTrucksWithNewDriverName($database, $driverRef['fullName'], $fullName);

        echo json_encode([
            'status'  => 'success',
            'message' => 'Driver updated successfully!'
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'status'  => 'error',
            'message' => $e->getMessage()
        ]);
    }
    exit();
}

/**
 * Deletes an image from *Cloudinary* 
 * (ORIGINAL Firebase Storage logic is commented below).
 */
function deleteImageFromStorage($imageUrl) {
    if (empty($imageUrl)) {
        return;
    }

    // --------------------------------------------------------
    // ORIGINAL FIREBASE STORAGE DELETION CODE (COMMENTED OUT)
    // --------------------------------------------------------
    /*
    // Parse the bucket name + object name from the URL
    $parsed = parse_url($imageUrl);
    if (!isset($parsed['path'])) {
        return;
    }

    // Typically, path is "/strp-1cbd6.firebasestorage.app/upload_drivers/driver_..."
    $path = ltrim($parsed['path'], '/'); 
    $segments = explode('/', $path, 2);

    if (count($segments) < 2) {
        return; // Can't parse properly
    }

    $bucketName = $segments[0]; // "strp-1cbd6.firebasestorage.app"
    $objectName = $segments[1]; // "upload_drivers/driver_..."

    // Delete from GCS
    $storage = new Google\Cloud\Storage\StorageClient([
        'keyFilePath' => __DIR__ . '/strp-1cbd6-firebase-adminsdk-75ypw-0f127e847c.json'
    ]);

    $bucket = $storage->bucket($bucketName);
    $object = $bucket->object($objectName);
    if ($object->exists()) {
        $object->delete();
    }
    */

    // --------------------------------------------------------
    // NEW CLOUDINARY DELETION LOGIC (INSERTED)
    // --------------------------------------------------------
    // 1) Parse URL to extract the public_id from Cloudinary
    $parsed = parse_url($imageUrl);
    if (!isset($parsed['path'])) {
        return;
    }

    // Example path might be: "/<cloud_name>/image/upload/v123456/upload_drivers/driver_abc.jpg"
    $pathParts = explode('/', trim($parsed['path'], '/'));
    // e.g. ["<cloud_name>", "image", "upload", "v123456", "upload_drivers", "driver_abc.jpg"]
    if (count($pathParts) < 5) {
        return;
    }

    // The first few segments are the cloud name, "image", "upload", possibly a "v" version
    $startIndex = 3;
    if (strpos($pathParts[3], 'v') === 0) {
        $startIndex = 4;
    }

    // Rebuild the remainder after that to form the public ID
    $publicIdParts = array_slice($pathParts, $startIndex);
    // Remove extension from last segment
    $lastPart = array_pop($publicIdParts);
    $lastPartNoExt = pathinfo($lastPart, PATHINFO_FILENAME);
    $publicIdParts[] = $lastPartNoExt;
    $publicId = implode('/', $publicIdParts);

    // 2) Destroy the resource from Cloudinary
    try {
        $cloudinary = getCloudinaryInstance();
        $cloudinary->uploadApi()->destroy($publicId, ['invalidate' => true]);
    } catch (Exception $e) {
        error_log("Cloudinary deletion error: " . $e->getMessage());
    }
}

/**
 * Function to update trucks (vehicles) with the new driver full name.
 * This function searches for truck records where the 'vehicleDriver' field matches the old full name,
 * and updates it to the new full name.
 */
function updateTrucksWithNewDriverName($database, $oldFullName, $newFullName) {
    // Fetch all trucks from Firebase
    $trucksRef = $database->getReference('trucks')->getValue();

    if (!$trucksRef) {
        return;
    }

    foreach ($trucksRef as $truckId => $truckData) {
        // Check if this truck has a 'vehicleDriver' field and if it matches the old full name
        if (isset($truckData['vehicleDriver']) && $truckData['vehicleDriver'] === $oldFullName) {
            // Update the vehicleDriver field in the truck record
            $database->getReference("trucks/$truckId")->update([
                'vehicleDriver' => $newFullName
            ]);
        }
    }
}
