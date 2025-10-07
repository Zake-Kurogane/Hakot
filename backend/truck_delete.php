<?php
require 'dbcon.php';
require __DIR__ . '/vendor/autoload.php';

use Google\Cloud\Storage\StorageClient;

header('Content-Type: application/json');

try {
    // Read JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['truckId']) || empty($input['truckId'])) {
        echo json_encode([
            'status' => 'error',
            'message' => 'No truck ID provided.'
        ]);
        exit;
    }

    $truckId = $input['truckId'];

    // 1) Retrieve the truck
    $truckPath = 'trucks/' . $truckId;
    $truckRef = $database->getReference($truckPath)->getValue();
    if (!$truckRef) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Truck does not exist or invalid ID.'
        ]);
        exit();
    }

    // 2) Delete from DB
    $database->getReference($truckPath)->remove();

    // 3) Optionally delete the old image from Storage
    if (isset($truckRef['imageUrl']) && !empty($truckRef['imageUrl'])) {
        deleteTruckImage($truckRef['imageUrl']);
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Truck deleted successfully.'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

/**
 * Remove an image from *Cloudinary* (original Firebase Storage logic is commented).
 */
function deleteTruckImage($imageUrl)
{
    /*
    // ORIGINAL CODE COMMENTED OUT (Firebase Storage):
    // ----------------------------------------------
    // $parsed = parse_url($imageUrl);
    // if (!isset($parsed['path'])) {
    //     return;
    // }
    //
    // // e.g. "/strp-1cbd6.firebasestorage.app/upload_trucks/truck_..."
    // $path = ltrim($parsed['path'], '/');
    // $segments = explode('/', $path, 2);
    // if (count($segments) < 2) {
    //     return;
    // }
    //
    // $bucketName = $segments[0];
    // $objectName = $segments[1];
    //
    // $storage = new StorageClient([
    //     'keyFilePath' => __DIR__ . '/strp-1cbd6-firebase-adminsdk-75ypw-0f127e847c.json'
    // ]);
    //
    // $bucket = $storage->bucket($bucketName);
    // $object = $bucket->object($objectName);
    // if ($object->exists()) {
    //     $object->delete();
    // }
    */

    // NEW CODE (Cloudinary Deletion):
    // -------------------------------
    $parsed = parse_url($imageUrl);
    if (!isset($parsed['path'])) {
        return;
    }

    // Example Cloudinary URL format:
    //  https://res.cloudinary.com/<cloud_name>/image/upload/v<timestamp>/<folder>/<filename>.ext

    // Split the path into segments, skipping leading/trailing slash
    $pathParts = explode('/', trim($parsed['path'], '/'));

    // We expect something like:
    //  ["<cloud_name>", "image", "upload", "v<version>", "<folder>", "<filename>.ext"]
    if (count($pathParts) < 5) {
        return;
    }

    // The first 2-3 segments are typically: [cloud_name, image, upload]
    // The 4th segment might be the version (e.g., "v123456789")
    $startIndex = 3;
    if (strpos($pathParts[3], 'v') === 0) {
        $startIndex = 4;
    }
    $publicIdParts = array_slice($pathParts, $startIndex);

    // Remove file extension from the final part
    $lastPart = array_pop($publicIdParts);
    $filenameWithoutExt = pathinfo($lastPart, PATHINFO_FILENAME);
    $publicIdParts[] = $filenameWithoutExt;

    // Rebuild into a public ID (e.g. "upload_trucks/truck_ABC")
    $publicId = implode('/', $publicIdParts);

    // Get the Cloudinary instance from dbcon.php
    $cloudinary = getCloudinaryInstance();

    try {
        // Delete from Cloudinary and invalidate the cache
        $cloudinary->uploadApi()->destroy($publicId, ['invalidate' => true]);
    } catch (Exception $e) {
        error_log("Cloudinary deletion error: " . $e->getMessage());
    }
}
