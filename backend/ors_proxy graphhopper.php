<?php
// ../backend/ors_proxy.php
// Load the OpenRouteService API key from an external config file
$apiConfig = require 'ors_api.php';
$orsApiKey = $apiConfig['ors_api_key'];

// Enable error reporting for debugging (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log file path
$logFile = 'ors_proxy.log';

// Function to log messages
function logMessage($message) {
    global $logFile;
    $timestamp = date("Y-m-d H:i:s");
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

// Get request parameters from the frontend
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postData = file_get_contents("php://input");
    logMessage("Received POST request: $postData");
    
    // ORS API URL
    $orsUrl = "https://api.openrouteservice.org/v2/directions";
    
    // Initialize cURL session
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $orsUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: $orsApiKey",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    
    // Execute cURL request and get response
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if(curl_errno($ch)){
        logMessage('cURL error: ' . curl_error($ch));
    }
    curl_close($ch);
    
    logMessage("ORS Response Code: $httpCode");
    logMessage("ORS Response Body: $response");
    
    // Set correct headers for CORS
    header("Access-Control-Allow-Origin: *");
    header("Content-Type: application/json");
    
    echo $response;
} else {
    // Invalid request method
    http_response_code(405);
    $error = json_encode(["error" => "Method Not Allowed"]);
    logMessage("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    echo $error;
}
?>