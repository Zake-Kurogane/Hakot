<?php
// ..backend/optimization_proxy.php 

// Set Content-Type to JSON
header('Content-Type: application/json');

// Function to send JSON error responses
function send_error($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['status' => 'error', 'message' => $message]);
    exit;
}

// Enable error logging but disable display to prevent HTML errors
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Allow only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error('Only POST requests are allowed.', 405);
}

// Load ORS API key from external configuration
$apiConfigPath = __DIR__ . '/ors_api.php'; // Adjust the path if necessary
if (!file_exists($apiConfigPath)) {
    send_error('ORS API configuration file not found.', 500);
}

$apiConfig = require $apiConfigPath;

if (!isset($apiConfig['ors_api_key']) || empty($apiConfig['ors_api_key'])) {
    send_error('ORS API key not set in configuration.', 500);
}

$orsApiKey = $apiConfig['ors_api_key'];

// Read the raw POST data
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

// Validate input
if (!isset($input['jobs']) || !isset($input['vehicles'])) {
    send_error('Invalid input. "jobs" and "vehicles" are required.', 400);
}

$jobs = $input['jobs'];
$vehicles = $input['vehicles'];

// Additional validation: Ensure jobs and vehicles do not exceed ORS limits
$MAX_JOBS = 50; // Reduced batch size to 50
$MAX_VEHICLES = 10; // Example limit, adjust as needed

if (count($jobs) > $MAX_JOBS) {
    send_error("Too many jobs. Maximum allowed is {$MAX_JOBS}.", 400);
}

if (count($vehicles) > $MAX_VEHICLES) {
    send_error("Too many vehicles. Maximum allowed is {$MAX_VEHICLES}.", 400);
}

// Prepare the payload for ORS
$payload = [
    'jobs' => $jobs,
    'vehicles' => $vehicles
];

// Initialize cURL
$ch = curl_init('https://api.openrouteservice.org/optimization');

// Set cURL options
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);

// Set headers
$headers = [
    'Content-Type: application/json',
    'Authorization: ' . $orsApiKey
];
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// Set the POST body
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

// Execute the request
$response = curl_exec($ch);

// Check for cURL errors
if ($response === false) {
    error_log('cURL Error: ' . curl_error($ch));
    send_error('Failed to communicate with ORS API: ' . curl_error($ch), 500);
}

// Get HTTP status code
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Decode the response
$responseData = json_decode($response, true);

// Check if ORS returned an error
if ($httpCode >= 400) {
    $errorMsg = isset($responseData['error']['message']) ? $responseData['error']['message'] : 'An error occurred with the optimization request.';
    error_log("ORS API Error: " . $response);
    send_error($errorMsg, $httpCode);
}

// Validate the response structure
if (!isset($responseData['routes'])) {
    send_error('Invalid response from ORS API.', 500);
}

// Output the response from ORS, adding 'status': 'success'
echo json_encode([
    'status' => 'success',
    'routes' => $responseData['routes']
]);
?>
