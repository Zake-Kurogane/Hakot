<?php
// ors_proxy.php

// Load the OpenRouteService API key from the external config file
$apiConfig = require 'ors_api.php';
$orsApiKey = $apiConfig['ors_api_key'];

// Enable error reporting for debugging (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Read the raw POST data
    $postData = file_get_contents("php://input");

    // Decode JSON to extract parameters
    $requestData = json_decode($postData, true);

    // Validate the 'profile' parameter (optional)
    $validProfiles = ['driving-car', 'driving-hgv', 'cycling-regular', 'foot-walking', 'foot-hiking'];
    $profile = isset($requestData['profile']) ? $requestData['profile'] : 'driving-car'; // Default to driving-car

    if (!in_array($profile, $validProfiles)) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid profile. Must be one of: " . implode(", ", $validProfiles)]);
        exit;
    }

    // Construct the ORS endpoint URL (using the GeoJSON endpoint)
    $orsUrl = "https://api.openrouteservice.org/v2/directions/$profile/geojson";

    // Initialize cURL session
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $orsUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: $orsApiKey",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));

    // Execute the cURL request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Optionally, you can handle cURL errors here if needed.
    if (curl_errno($ch)) {
        // For example: error_log('cURL error: ' . curl_error($ch));
    }
    curl_close($ch);

    // Set CORS headers and content type before outputting the response
    header("Access-Control-Allow-Origin: *");
    header("Content-Type: application/json");

    echo $response;
} else {
    // If the request method is not POST, return a 405 Method Not Allowed response
    http_response_code(405);
    echo json_encode(["error" => "Method Not Allowed"]);
}
?>
