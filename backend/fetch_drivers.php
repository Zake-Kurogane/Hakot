<?php
require 'dbcon.php';
header('Content-Type: application/json');

try {
    $driversData = $database->getReference('drivers')->getValue();

    if ($driversData) {
        echo json_encode([
            'status' => 'success',
            'data' => $driversData
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'No drivers found.'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
