<?php
// ors_reverse_geocode.php

// Load the OpenRouteService API key from the external config file
$apiConfig = require 'ors_api.php';
$orsApiKey = $apiConfig['ors_api_key'];

// Set content type for JSON response
header('Content-Type: application/json');

// Allow only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Only POST requests are accepted.']);
    exit;
}

// Retrieve and decode the JSON request body
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validate that latitude and longitude are provided
if (!isset($data['latitude']) || !isset($data['longitude'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing latitude or longitude in request body.']);
    exit;
}

$latitude  = $data['latitude'];
$longitude = $data['longitude'];

// Build the ORS reverse geocoding URL
// Documentation: https://openrouteservice.org/dev/#/api-docs/geocode/reverse/get
$url = "https://api.openrouteservice.org/geocode/reverse?api_key=" . urlencode($orsApiKey) .
       "&point.lat=" . urlencode($latitude) .
       "&point.lon=" . urlencode($longitude) .
       "&size=1";

// Initialize a cURL session
$ch = curl_init();

// Set cURL options
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Execute the request and capture the response
$response = curl_exec($ch);

// Handle potential cURL errors
if (curl_errno($ch)) {
    http_response_code(500);
    echo json_encode(['error' => 'cURL error: ' . curl_error($ch)]);
    curl_close($ch);
    exit;
}

// Close the cURL session
curl_close($ch);

// Output the ORS response directly
echo $response;
?>
