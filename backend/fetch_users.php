<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Not logged in or session not set.'
    ]);
    exit;
}

if (!isset($_SESSION['username'])) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'No username found in session.'
    ]);
    exit;
}

$sessionUsername = strtolower(trim($_SESSION['username']));

require 'dbcon.php';

try {
    $usersRef = $database->getReference('users')->getValue();
    if (!$usersRef) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'No users found in the database.'
        ]);
        exit;
    }

    foreach ($usersRef as $userKey => $userData) {
        if (isset($userData['username']) &&
            strtolower($userData['username']) === $sessionUsername) {
            echo json_encode([
                'status'        => 'success',
                'username'      => $userData['username'],
                'name'          => isset($userData['name']) ? $userData['name'] : '',
                'position'      => isset($userData['position']) ? $userData['position'] : '',
                'profile_image' => isset($userData['profile-image']) ? $userData['profile-image'] : '',
                'password'      => isset($userData['password']) ? $userData['password'] : ''
            ]);
            exit;
        }
    }

    echo json_encode([
        'status'  => 'error',
        'message' => 'No matching user found.'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
