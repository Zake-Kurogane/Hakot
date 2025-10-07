<?php
// fetch_announcements.php

session_start();
header('Content-Type: application/json');
require 'dbcon.php';

try {
    $announcements = $database->getReference('Announcements')->getValue();
    if (!$announcements) {
        echo json_encode(['status' => 'success', 'announcements' => []]);
        exit;
    }
    echo json_encode(['status' => 'success', 'announcements' => $announcements]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
