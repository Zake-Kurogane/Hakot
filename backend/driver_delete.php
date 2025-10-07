<?php
require 'dbcon.php'; // Your Firebase Realtime DB connection
require __DIR__ . '/vendor/autoload.php';

use Google\Cloud\Storage\StorageClient;

// Read JSON input: driverId, imageUrl
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['driverId']) || empty($input['driverId'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'No driver ID provided.'
    ]);
    exit();
}

$driverId  = $input['driverId'];
$imageUrl  = isset($input['imageUrl']) ? $input['imageUrl'] : '';

try {

    // *** ADDED CODE: Find trucks with vehicleDriver = driver's fullName, set them to Unassigned
    $driverFullName = $database->getReference('drivers/' . $driverId . '/fullName')->getValue();
    if (!empty($driverFullName)) {
        $allTrucks = $database->getReference('trucks')->getValue();
        if (!empty($allTrucks)) {
            foreach ($allTrucks as $truckKey => $truckData) {
                if (isset($truckData['vehicleDriver']) && $truckData['vehicleDriver'] === $driverFullName) {
                    $database->getReference('trucks/' . $truckKey)->update(['vehicleDriver' => 'Unassigned']);
                }
            }
        }
    }
    // *** END OF ADDED CODE ***

    // 1) Delete driver record from Firebase Realtime Database
    $database->getReference('drivers/' . $driverId)->remove();

    // ---------------------------------------------------------------------
    // ORIGINAL FIREBASE STORAGE CODE (COMMENTED OUT, DO NOT REMOVE)
    // ---------------------------------------------------------------------
    /*
    // 2) If we have an imageUrl, parse the object name and delete from Storage
    if (!empty($imageUrl)) {
        // Example URL: https://storage.googleapis.com/<bucket>/<folder>/<filename>
        // We can parse out <folder>/<filename> from $imageUrl
        $parsed = parse_url($imageUrl); 
        // Typically: path => "/<bucket>/<folder>/<filename>"
        // So let's remove the leading "/<bucket>/" part
        if (isset($parsed['path'])) {
            // e.g., "/strp-1cbd6.appspot.com/upload_drivers/driver_..."
            $path = ltrim($parsed['path'], '/'); // remove leading slash
            // $path might be "strp-1cbd6.appspot.com/upload_drivers/driver_..."

            // Bucket name is the first segment
            $segments = explode('/', $path, 2);
            if (count($segments) == 2) {
                $bucketName = $segments[0];            // "strp-1cbd6.appspot.com"
                $objectName = $segments[1];            // "upload_drivers/driver_..."
                
                // Initialize Google Cloud Storage client
                $storage = new StorageClient([
                  'keyFilePath' => __DIR__ . '/strp-1cbd6-firebase-adminsdk-75ypw-0f127e847c.json'
                ]);

                $bucket = $storage->bucket($bucketName);
                $object = $bucket->object($objectName);

                if ($object->exists()) {
                    $object->delete();
                }
            }
        }
    }
    */

    // ---------------------------------------------------------------------
    // NEW CLOUDINARY DELETION LOGIC
    // ---------------------------------------------------------------------
    if (!empty($imageUrl)) {
        // Example Cloudinary URL:
        //   https://res.cloudinary.com/<cloud_name>/image/upload/v<timestamp>/<folder>/<file>.ext

        $parsed = parse_url($imageUrl);
        if (isset($parsed['path'])) {
            // e.g. "/<cloud_name>/image/upload/v123456/upload_drivers/driver_abcd.jpg"
            $pathParts = explode('/', trim($parsed['path'], '/'));
            // e.g. ["<cloud_name>", "image", "upload", "v123456", "upload_drivers", "driver_abcd.jpg"]
            if (count($pathParts) >= 5) {
                // The first 3–4 segments might be the cloud name, "image", "upload", version
                $startIndex = 3;
                if (strpos($pathParts[3], 'v') === 0) {
                    $startIndex = 4;
                }

                // Rebuild the remainder
                $publicIdParts = array_slice($pathParts, $startIndex);
                $lastPart = array_pop($publicIdParts);
                // Remove extension
                $filenameNoExt = pathinfo($lastPart, PATHINFO_FILENAME);
                $publicIdParts[] = $filenameNoExt;
                $publicId = implode('/', $publicIdParts);

                // Call Cloudinary to delete
                $cloudinary = getCloudinaryInstance();
                try {
                    $cloudinary->uploadApi()->destroy($publicId, ['invalidate' => true]);
                } catch (Exception $ex) {
                    error_log("Cloudinary deletion error: " . $ex->getMessage());
                }
            }
        }
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Driver deleted successfully.'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
