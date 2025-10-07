<?php
session_start();
header('Content-Type: application/json');

require 'dbcon.php';

try {
    $usersRef = $database->getReference('users')->getValue();
    if (!$usersRef) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'No users found.'
        ]);
        exit;
    }
    $users = [];
    foreach ($usersRef as $key => $userData) {
        // Add the key into the user data so the front end can use it.
        $userData['key'] = $key;
        // Optionally, remove sensitive data (like the password)
        unset($userData['password']);
        $users[] = $userData;
    }
    echo json_encode([
        'status' => 'success',
        'users'  => $users
    ]);
} catch (Exception $e) {
    echo json_encode([
         'status'  => 'error',
         'message' => $e->getMessage()
    ]);
}
?>
