<?php
session_start();
header('Content-Type: application/json');

// Ensure the user is logged in and has a username in session.
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

// Fetch all users from Firebase so that we can check the current user's position.
$usersRef = $database->getReference('users')->getValue();
$currentUserIsAdmin = false;

if ($usersRef) {
    foreach ($usersRef as $key => $user) {
        if (isset($user['username']) && strtolower($user['username']) === $sessionUsername) {
            if (isset($user['position']) && strtolower($user['position']) === 'admin') {
                $currentUserIsAdmin = true;
            }
            break;
        }
    }
}

// Only allow registration if the current user's position is admin.
if (!$currentUserIsAdmin) {
    echo json_encode(['status' => 'error', 'message' => 'You are not allowed to register new users.']);
    exit;
}

// Retrieve new user data from POST
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$fullname = isset($_POST['fullname']) ? trim($_POST['fullname']) : '';
$position = isset($_POST['position']) ? trim($_POST['position']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

// Validate required fields
if (empty($username) || empty($fullname) || empty($position) || empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'Required fields missing.']);
    exit;
}

// Check for duplicate username (case-insensitive)
if ($usersRef) {
    foreach ($usersRef as $existingUser) {
        if (isset($existingUser['username']) && strtolower($existingUser['username']) === strtolower($username)) {
            echo json_encode(['status' => 'error', 'message' => 'Username already exists.']);
            exit;
        }
    }
}

// Hash the password before storing it
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Prepare the new user record
$newUser = [
    'username'      => $username,
    'name'          => $fullname,
    'position'      => $position,
    'password'      => $hashedPassword,
    'profile-image' => '' // default empty value
];

// Process the image file if provided
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $tmpName = $_FILES['image']['tmp_name'];
    try {
        $cloudinary = getCloudinaryInstance();
        $uploadResult = $cloudinary->uploadApi()->upload(
            $tmpName,
            [
                'folder'        => 'hakot_profiles',
                'resource_type' => 'image'
            ]
        );
        if (isset($uploadResult['secure_url'])) {
            $newUser['profile-image'] = $uploadResult['secure_url'];
        }
    } catch (Exception $ex) {
        echo json_encode(['status' => 'error', 'message' => 'Cloudinary error: ' . $ex->getMessage()]);
        exit;
    }
}

// Push the new user record into Firebase
$newRef = $database->getReference('users')->push($newUser);

echo json_encode(['status' => 'success', 'message' => 'User registered successfully.']);
?>
