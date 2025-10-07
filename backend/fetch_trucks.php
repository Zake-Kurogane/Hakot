<?php
// backend/fetch_trucks.php
header('Content-Type: application/json');
require 'dbcon.php'; // Include Firebase database connection

try {
    // Fetch all truck data from Firebase
    $trucksRef = $database->getReference('trucks')->getValue();

    // Check if there are any trucks
    $trucks = $trucksRef ? $trucksRef : []; // Assign data or empty array if none

    echo json_encode(['status' => 'success', 'data' => $trucks]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}