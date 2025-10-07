<?php
session_start();

// If not logged in, redirect
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../index.php");
    exit;
}

// Include your Firebase connection file. This file should initialize $database via the Kreait Firebase SDK.
require 'dbcon.php';

header('Content-Type: application/json');

// Read and decode the JSON input
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
    exit;
}

if (!isset($input['truckId'])) {
    echo json_encode(['success' => false, 'message' => 'Truck ID not provided.']);
    exit;
}

$truckId = $input['truckId'];
$latitude = isset($input['latitude']) ? $input['latitude'] : null;
$longitude = isset($input['longitude']) ? $input['longitude'] : null;

if ($latitude === null || $longitude === null) {
    echo json_encode(['success' => false, 'message' => 'Latitude or longitude not provided.']);
    exit;
}

try {
    // Update the truck's current location in Firebase.
    // This assumes your Firebase structure has a "trucks" node where each truck is identified by its key
    // and its current location is stored in a child node called "truckCurrentLocation".
    $updateRef = $database->getReference("trucks/{$truckId}/truckCurrentLocation");
    $updateRef->set([
        'latitude'  => $latitude,
        'longitude' => $longitude
    ]);

    echo json_encode(['success' => true, 'message' => 'Truck location reset successfully.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
