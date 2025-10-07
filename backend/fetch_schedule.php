<?php
// backend/fetch_schedule.php

require 'dbcon.php'; // Your Firebase connection
header('Content-Type: application/json');

// Get the truck key from the query parameter
$truckKey = $_GET['truckKey'] ?? '';
if (!$truckKey) {
    // If truckKey isn't provided, return error JSON and exit
    echo json_encode([
        'status'  => 'error',
        'message' => 'No truckKey provided.'
    ]);
    exit;
}

// Retrieve schedules from Firebase under "trucks/{truckKey}/schedules"
$scheduleData = $database
    ->getReference("trucks/{$truckKey}/schedules")
    ->getValue();

// If there is nothing stored, default to an empty array
if (!$scheduleData) {
    $scheduleData = [];
}

// Return the schedule in the response
echo json_encode([
    'status'    => 'success',
    'schedules' => $scheduleData
]);
