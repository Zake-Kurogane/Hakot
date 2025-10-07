<?php
// verify_admin.php
session_start();
header('Content-Type: application/json');

require 'dbcon.php'; // Ensure this file sets up your $database connection

// Get JSON POST data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!isset($data['password']) || empty($data['password'])) {
    echo json_encode(['status' => 'error', 'message' => 'Password is required.']);
    exit;
}

$providedPassword = $data['password'];

// Fetch all users from Firebase
$usersRef = $database->getReference('users')->getValue();
$adminVerified = false;

if ($usersRef) {
    foreach ($usersRef as $user) {
        // Check if the user has the admin position (case-insensitive) and a password is set
        if (isset($user['position'], $user['password']) && strtolower($user['position']) === 'admin') {
            // Compare the provided password against the stored hashed password
            if (password_verify($providedPassword, $user['password'])) {
                $adminVerified = true;
                break;
            }
        }
    }
}

if ($adminVerified) {
    echo json_encode(['status' => 'success', 'message' => 'Password verified.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error verifying admin password.']);
}
?>
