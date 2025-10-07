<?php
// announcement_crud.php

session_start();
require 'dbcon.php';          // Initializes $database and getCloudinaryInstance()
require 'onesignalid.php';    // Provides $onesignal_app_id and $onesignal_rest_key

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['status' => 'error', 'message' => 'Not authorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($action === 'create') {
    // --- CREATE Announcement ---
    $header  = trim($_POST['header'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $pushToUserUID = isset($_POST['pushToUserUID']) ? true : false;

    if (empty($header) || empty($message)) {
        echo json_encode(['status' => 'error', 'message' => 'Announcement header and message are required.']);
        exit;
    }

    // Prepare announcement data
    $announcementData = [
         'header'    => $header,
         'message'   => $message,
         'timestamp' => time()
    ];

    // Handle optional image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES['image']['tmp_name'];
        try {
            $cloudinary = getCloudinaryInstance();
            $uploadResult = $cloudinary->uploadApi()->upload(
                $tmpName,
                [
                    'folder'        => 'hakot_announcement',
                    'resource_type' => 'image'
                ]
            );
            if (isset($uploadResult['secure_url'])) {
                $announcementData['image'] = $uploadResult['secure_url'];
            }
        } catch (Exception $ex) {
            echo json_encode(['status' => 'error', 'message' => 'Cloudinary error: ' . $ex->getMessage()]);
            exit;
        }
    }

    try {
        // Save to the Announcements node
        $annRef = $database->getReference('Announcements')->push($announcementData);
        $announcementKey = $annRef->getKey();

        // If the "push to all users" option is enabled AND the announcement has an image, push now.
        if ($pushToUserUID && isset($announcementData['image'])) {  // <-- Modified condition
            $usersUID = $database->getReference('usersUID')->getValue();
            if ($usersUID) {
                foreach ($usersUID as $uid => $userData) {
                    // Check if this announcement already exists for this user.
                    $userAnnRef = $database->getReference("usersUID/{$uid}/announcements");
                    $userAnnData = $userAnnRef->getValue();
                    $alreadyExists = false;
                    if ($userAnnData) {
                        foreach ($userAnnData as $entry) {
                            if (isset($entry['announcementKey']) && $entry['announcementKey'] === $announcementKey) {
                                $alreadyExists = true;
                                break;
                            }
                        }
                    }
                    if (!$alreadyExists) {
                        // Prepare data including image URL
                        $dataToPush = [
                            'announcementKey' => $announcementKey,
                            'header'          => $header,
                            'message'         => $message,
                            'timestamp'       => time()
                        ];
                        if (isset($announcementData['image'])) {
                            $dataToPush['image'] = $announcementData['image'];
                        }
                        $userAnnRef->push($dataToPush);
                    }
                }
            }
            // Send OneSignal notification with headings and contents fields
            $onesignalUrl = 'https://onesignal.com/api/v1/notifications';
            $headings = [ "en" => $header ];    // Notification title
            $contents = [ "en" => $message ];     // Notification content
            $fields = [
                'app_id'            => $onesignal_app_id,
                'included_segments' => ['All'],
                'headings'          => $headings,
                'contents'          => $contents,
                'data'              => ['announcementKey' => $announcementKey]
            ];
            $fields = json_encode($fields);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $onesignalUrl);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json; charset=utf-8',
                'Authorization: Basic ' . $onesignal_rest_key
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_exec($ch);
            curl_close($ch);
        }

        echo json_encode([
            'status'          => 'success',
            'message'         => 'Announcement saved successfully.',
            'announcementKey' => $announcementKey
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
} elseif ($action === 'update') {
    // --- UPDATE Announcement ---
    $announcementKey = trim($_POST['announcementKey'] ?? '');
    $header  = trim($_POST['header'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($announcementKey) || empty($header) || empty($message)) {
        echo json_encode(['status' => 'error', 'message' => 'Missing required fields.']);
        exit;
    }

    $updatedData = [
         'header'    => $header,
         'message'   => $message,
         'timestamp' => time()
    ];

    // If a new image file is provided, update it
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES['image']['tmp_name'];
        try {
            $cloudinary = getCloudinaryInstance();
            $uploadResult = $cloudinary->uploadApi()->upload(
                $tmpName,
                [
                    'folder'        => 'hakot_announcement',
                    'resource_type' => 'image'
                ]
            );
            if (isset($uploadResult['secure_url'])) {
                $updatedData['image'] = $uploadResult['secure_url'];
            }
        } catch (Exception $ex) {
            echo json_encode(['status' => 'error', 'message' => 'Cloudinary error: ' . $ex->getMessage()]);
            exit;
        }
    }

    try {
        $database->getReference("Announcements/{$announcementKey}")->update($updatedData);
        echo json_encode(['status' => 'success', 'message' => 'Announcement updated successfully.']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
} elseif ($action === 'delete') {
    // --- DELETE Announcement ---
    $announcementKey = trim($_POST['announcementKey'] ?? '');
    if (empty($announcementKey)) {
        echo json_encode(['status' => 'error', 'message' => 'Announcement key is required.']);
        exit;
    }
    try {
        // Remove from the Announcements node
        $database->getReference("Announcements/{$announcementKey}")->remove();

        // Remove any entries for this announcement in each user's announcements
        $usersUID = $database->getReference('usersUID')->getValue();
        if ($usersUID) {
            foreach ($usersUID as $uid => $userData) {
                $userAnnRef = $database->getReference("usersUID/{$uid}/announcements");
                $userAnnData = $userAnnRef->getValue();
                if ($userAnnData) {
                    foreach ($userAnnData as $entryKey => $ann) {
                        if (isset($ann['announcementKey']) && $ann['announcementKey'] === $announcementKey) {
                            $userAnnRef->getChild($entryKey)->remove();
                        }
                    }
                }
            }
        }

        echo json_encode(['status' => 'success', 'message' => 'Announcement deleted successfully.']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
} elseif ($action === 'push') {
    // --- PUSH to Users: Manually push an existing announcement ---
    $announcementKey = trim($_POST['announcementKey'] ?? '');
    if (empty($announcementKey)) {
        echo json_encode(['status' => 'error', 'message' => 'Announcement key is required.']);
        exit;
    }
    // Retrieve the announcement data from Firebase
    $announcementData = $database->getReference("Announcements/{$announcementKey}")->getValue();
    if (!$announcementData) {
        echo json_encode(['status' => 'error', 'message' => 'Announcement not found.']);
        exit;
    }
    
    $header = $announcementData['header'] ?? '';
    $message = $announcementData['message'] ?? '';
    
    // Determine whether to push/update the announcement data in each user's record (only if there is an image)
    if (isset($announcementData['image'])) {
        $dataToPush = [
            'announcementKey' => $announcementKey,
            'header'          => $header,
            'message'         => $message,
            'timestamp'       => time(),
            'image'           => $announcementData['image']
        ];
        
        $usersUID = $database->getReference('usersUID')->getValue();
        if ($usersUID) {
            foreach ($usersUID as $uid => $userData) {
                $userAnnRef = $database->getReference("usersUID/{$uid}/announcements");
                $userAnnData = $userAnnRef->getValue();
                $found = false;
                if ($userAnnData) {
                    foreach ($userAnnData as $childKey => $entry) {
                        if (isset($entry['announcementKey']) && $entry['announcementKey'] === $announcementKey) {
                            // Update the existing announcement data
                            $userAnnRef->getChild($childKey)->update($dataToPush);
                            $found = true;
                            break;
                        }
                    }
                }
                if (!$found) {
                    // Push new announcement data if it does not already exist
                    $userAnnRef->push($dataToPush);
                }
            }
        }
    }
    
    // Always send the OneSignal notification regardless of image presence.
    $onesignalUrl = 'https://onesignal.com/api/v1/notifications';
    $headings = [ "en" => $header ];    // Notification title from header
    $contents = [ "en" => $message ];     // Notification message from message
    $fields = [
         'app_id'            => $onesignal_app_id,
         'included_segments' => ['All'],
         'headings'          => $headings,
         'contents'          => $contents,
         'data'              => ['announcementKey' => $announcementKey]
    ];
    $fields = json_encode($fields);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $onesignalUrl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
         'Content-Type: application/json; charset=utf-8',
         'Authorization: Basic ' . $onesignal_rest_key
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_exec($ch);
    curl_close($ch);

    echo json_encode(['status' => 'success', 'message' => 'Announcement pushed to users successfully.']);
    exit;
}
