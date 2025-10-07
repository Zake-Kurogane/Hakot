<?php
session_start(); // Start or resume the session
header('Content-Type: application/json');

require 'dbcon.php'; // or your actual path to Firebase Realtime DB connection

try {
    // 1) Grab JSON input
    $inputData = json_decode(file_get_contents('php://input'), true);
    if (!isset($inputData['username']) || !isset($inputData['password'])) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Missing username or password.'
        ]);
        exit;
    }

    $username = strtolower(trim($inputData['username']));
    $password = trim($inputData['password']);

    // 2) Retrieve users from DB
    // Adjust node if your DB path is different
    $usersRef = $database->getReference('users')->getValue();
    if (!$usersRef) {
        echo json_encode([
            'status' => 'error',
            'message' => 'No users found in the database.'
        ]);
        exit;
    }

    // 3) Search for the matching username
    foreach ($usersRef as $userKey => $userData) {
        if (
            isset($userData['username']) && 
            strtolower($userData['username']) === $username
        ) {
            // 4) Check the password hash
            if (password_verify($password, $userData['password'])) {
                // SUCCESS
                // Optionally store user info in session
                $_SESSION["loggedin"] = true;
                $_SESSION['user_id'] = $userKey;
                $_SESSION['username'] = $userData['username'];
                // etc.

                echo json_encode([
                    'status' => 'success',
                    'message' => 'Login successful.',
                    'session_id' => session_id(), // Return session ID
                    'data' => [
                        'name'     => $userData['name'] ?? 'Unknown',
                        'position' => $userData['position'] ?? 'User'
                    ]
                ]);
                exit;
            } else {
                // Password mismatch
                echo json_encode([
                    'status'  => 'error',
                    'message' => 'Invalid password.'
                ]);
                exit;
            }
        }
    }

    // If we never found a matching username
    echo json_encode([
        'status'  => 'error',
        'message' => 'User not found.'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage()
    ]);
}
