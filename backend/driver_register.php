<?php
require 'dbcon.php'; // Your Firebase Realtime DB connection

require __DIR__ . '/vendor/autoload.php';
// Note: Removed the "use Google\Cloud\Storage\StorageClient;" line as it is no longer needed.

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = [];

    // Get and validate form inputs
    $fullName        = trim($_POST['fullName']);
    $username        = strtolower(trim($_POST['username']));
    $password        = trim($_POST['password']);
    $confirmPassword = trim($_POST['confirmPassword']);

    // Validate required fields
    if (empty($fullName) || empty($username) || empty($password) || empty($confirmPassword)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
        exit();
    }

    if ($password !== $confirmPassword) {
        echo json_encode(['status' => 'error', 'message' => 'Passwords do not match.']);
        exit();
    }

    try {
        // Check for duplicate username in Realtime Database
        $driversRef = $database->getReference('drivers')->getValue();
        if ($driversRef) {
            foreach ($driversRef as $driver) {
                if (
                    isset($driver['username']) &&
                    strtolower($driver['username']) === $username
                ) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Username already exists.'
                    ]);
                    exit();
                }
            }
        }

        // Hash password for security
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        // -------------------------------
        // Handle driver image upload using Cloudinary
        // -------------------------------
        if (isset($_FILES['driverImage']) && $_FILES['driverImage']['error'] === UPLOAD_ERR_OK) {
            $imageTmpPath = $_FILES['driverImage']['tmp_name'];
            $imageName    = $_FILES['driverImage']['name'];

            // Validate image type
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $imageType    = mime_content_type($imageTmpPath);
            if (!in_array($imageType, $allowedTypes)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Invalid image type. Only JPG, PNG, and GIF are allowed.'
                ]);
                exit();
            }

            // Get Cloudinary instance
            $cloudinary = getCloudinaryInstance();

            // Define the folder (collection) name on Cloudinary
            $folderName = 'upload_drivers';

            // Create a unique public ID for the image (optional)
            $publicId = uniqid('driver_', true);

            // Upload the image to Cloudinary
            $uploadOptions = [
                "folder"        => $folderName,
                "public_id"     => $publicId,
                "overwrite"     => true,
                "resource_type" => "image"
            ];

            $uploadResult = $cloudinary->uploadApi()->upload($imageTmpPath, $uploadOptions);

            // Get the secure URL of the uploaded image
            $imageUrl = $uploadResult['secure_url'];

        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'No image uploaded or an error occurred.'
            ]);
            exit();
        }

        // -------------------------------
        // Build the new driver object
        // -------------------------------
        $newDriver = [
            'fullName'  => $fullName,
            'username'  => $username,
            'password'  => $hashedPassword,
            'imageUrl'  => $imageUrl
        ];

        // -------------------------------
        // Save to Firebase
        // -------------------------------
        $database->getReference('drivers')->push($newDriver);

        echo json_encode([
            'status'  => 'success',
            'message' => 'Driver registered successfully!'
        ]);

    } catch (Exception $e) {
        echo json_encode([
            'status'  => 'error',
            'message' => $e->getMessage()
        ]);
    }
    exit();
}