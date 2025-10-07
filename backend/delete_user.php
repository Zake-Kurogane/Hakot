<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in.']);
    exit;
}
if (!isset($_SESSION['username'])) {
    echo json_encode(['status' => 'error', 'message' => 'No username in session.']);
    exit;
}

require 'dbcon.php';

$userKey = isset($_POST['userKey']) ? trim($_POST['userKey']) : '';
if (empty($userKey)) {
    echo json_encode(['status' => 'error', 'message' => 'User key is missing.']);
    exit;
}

try {
    // Fetch the user record from Firebase
    $userData = $database->getReference('users/' . $userKey)->getValue();
    if (!$userData) {
        echo json_encode(['status' => 'error', 'message' => 'User not found.']);
        exit;
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}

// Delete the Cloudinary image if it exists
if (isset($userData['profile-image']) && !empty($userData['profile-image'])) {
    $imageUrl = $userData['profile-image'];
    // Extract public_id from the URL (adjust regex if needed)
    if (preg_match('/\/(hakot_profiles\/[^\.\/]+)\.[a-z]+$/i', $imageUrl, $matches)) {
        $publicId = $matches[1];
        try {
            $cloudinary = getCloudinaryInstance();
            $cloudinary->uploadApi()->destroy($publicId, ['resource_type' => 'image']);
        } catch (Exception $ex) {
            // Log the error if needed, but continue with deletion.
            // For example: error_log('Cloudinary deletion error: ' . $ex->getMessage());
        }
    }
}

// Delete the user record from Firebase
try {
    $database->getReference('users/' . $userKey)->remove();
    echo json_encode(['status' => 'success', 'message' => 'User deleted successfully.']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
