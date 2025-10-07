<?php
session_start();
header('Content-Type: application/json');

// Ensure the user is logged in and a username exists in session.
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in.']);
    exit;
}
if (!isset($_SESSION['username'])) {
    echo json_encode(['status' => 'error', 'message' => 'No username in session.']);
    exit;
}

require 'dbcon.php';

// Retrieve new values from POST
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$position = isset($_POST['position']) ? trim($_POST['position']) : '';
$newUsername = isset($_POST['username']) ? trim($_POST['username']) : '';

if (empty($name)) {
    echo json_encode(['status' => 'error', 'message' => 'Name is required.']);
    exit;
}

$updateData = ['name' => $name];

// Process the new username if provided.
if (!empty($newUsername)) {
    // If the new username is different from the session username (case-insensitive)
    if (strtolower($newUsername) !== strtolower($_SESSION['username'])) {
        // Fetch all users from Firebase
        $allUsers = $database->getReference('users')->getValue();
        if ($allUsers) {
            foreach ($allUsers as $user) {
                if (isset($user['username']) && strtolower($user['username']) === strtolower($newUsername)) {
                    echo json_encode(['status' => 'error', 'message' => 'Username already exists.']);
                    exit;
                }
            }
        }
    }
    $updateData['username'] = $newUsername;
}

if (!empty($position)) {
    $updateData['position'] = $position;
}

// Process image upload if a file is provided.
if (isset($_FILES['profileImage']) && $_FILES['profileImage']['error'] === UPLOAD_ERR_OK) {
    $tmpName = $_FILES['profileImage']['tmp_name'];
    try {
        $cloudinary = getCloudinaryInstance();
        $uploadResult = $cloudinary->uploadApi()->upload(
            $tmpName,
            [
                'folder' => 'hakot_profiles',
                'resource_type' => 'image'
            ]
        );
        if (isset($uploadResult['secure_url'])) {
            $updateData['profile-image'] = $uploadResult['secure_url'];
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Image upload failed: No secure URL returned.']);
            exit;
        }
    } catch (Exception $ex) {
        echo json_encode(['status' => 'error', 'message' => 'Cloudinary error: ' . $ex->getMessage()]);
        exit;
    }
}

// Locate the current user in Firebase by the session username (case-insensitive)
$sessionUsername = strtolower(trim($_SESSION['username']));
$usersRef = $database->getReference('users')->getValue();
$userKey = null;
if ($usersRef) {
    foreach ($usersRef as $key => $user) {
        if (isset($user['username']) && strtolower($user['username']) === $sessionUsername) {
            $userKey = $key;
            break;
        }
    }
}

if (!$userKey) {
    echo json_encode(['status' => 'error', 'message' => 'User not found.']);
    exit;
}

try {
    $database->getReference('users/' . $userKey)->update($updateData);
    // If the username was updated, update the session variable.
    if (isset($updateData['username'])) {
        $_SESSION['username'] = $updateData['username'];
    }
    echo json_encode(['status' => 'success', 'message' => 'Profile updated successfully.']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
