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

$sessionUsername = strtolower(trim($_SESSION['username']));

$oldPassword = isset($_POST['oldPassword']) ? $_POST['oldPassword'] : '';
$newPassword = isset($_POST['newPassword']) ? $_POST['newPassword'] : '';

if (empty($oldPassword) || empty($newPassword)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing passwords.']);
    exit;
}

$usersRef = $database->getReference('users')->getValue();
if (!$usersRef) {
    echo json_encode(['status' => 'error', 'message' => 'No users found.']);
    exit;
}

$foundUserKey  = null;
$foundUserData = null;
foreach ($usersRef as $key => $user) {
    if (strtolower($user['username']) === $sessionUsername) {
        $foundUserKey  = $key;
        $foundUserData = $user;
        break;
    }
}
if (!$foundUserData) {
    echo json_encode(['status' => 'error', 'message' => 'User not found.']);
    exit;
}

// Verify the old password using password_verify
$currentHashed = isset($foundUserData['password']) ? $foundUserData['password'] : '';
if (!password_verify($oldPassword, $currentHashed)) {
    echo json_encode(['status' => 'error', 'message' => 'Old password is incorrect.']);
    exit;
}

// Hash the new password before storing it
$newHashed = password_hash($newPassword, PASSWORD_DEFAULT);
$foundUserData['password'] = $newHashed;

$database->getReference('users/'.$foundUserKey)->update($foundUserData);

echo json_encode(['status' => 'success', 'message' => 'Changed Password Successfully.']);
?>
