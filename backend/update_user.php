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

$updateData = [];

// Update username if provided
if (isset($_POST['username']) && !empty($_POST['username'])) {
    $newUsername = trim($_POST['username']);
    $updateData['username'] = $newUsername;
}

// Update full name if provided (stored as 'name')
if (isset($_POST['fullname']) && !empty($_POST['fullname'])) {
    $updateData['name'] = trim($_POST['fullname']);
}

// Update position if provided
if (isset($_POST['position']) && !empty($_POST['position'])) {
    $updateData['position'] = trim($_POST['position']);
}

// Update password if provided
if (isset($_POST['password']) && !empty($_POST['password'])) {
    // Hash the new password
    $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $updateData['password'] = $hashedPassword;
}

// Process image upload if provided (make sure the file input name matches the one in your form)
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $tmpName = $_FILES['image']['tmp_name'];
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

// --- New: Check if the username already exists ---
if (isset($updateData['username'])) {
    // Retrieve all users from Firebase
    $usersSnapshot = $database->getReference('users')->getValue();
    if ($usersSnapshot) {
        foreach ($usersSnapshot as $key => $user) {
            if ($key !== $userKey && isset($user['username'])) {
                // Case-insensitive check
                if (strtolower($user['username']) === strtolower($updateData['username'])) {
                    echo json_encode(['status' => 'error', 'message' => 'Username already exists.']);
                    exit;
                }
            }
        }
    }
}

try {
    $database->getReference('users/' . $userKey)->update($updateData);
    echo json_encode(['status' => 'success', 'message' => 'User updated successfully.']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
