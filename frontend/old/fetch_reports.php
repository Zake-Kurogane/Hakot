<?php
header('Content-Type: application/json');
require 'dbcon.php'; // or wherever your Firebase Realtime DB connection is

try {
    // 1) Reference: "reports/optimized_routes"
    $routesRef = $database->getReference('reports/optimized_routes')->getValue();
    if (!$routesRef) {
        // No data found
        echo json_encode([
            'status' => 'success',
            'data' => ['provenRoutes' => 0]
        ]);
        exit;
    }

    // 2) Collect unique truckName if reportType="routes optimized"
    $truckNameSet = [];
    foreach ($routesRef as $reportKey => $reportData) {
        if (
            isset($reportData['reportType']) && 
            $reportData['reportType'] === 'routes optimized' &&
            isset($reportData['truckName'])
        ) {
            $truckNameSet[] = $reportData['truckName'];
        }
    }

    // 3) Keep them unique
    $uniqueTrucks = array_unique($truckNameSet);
    $provenRoutes = count($uniqueTrucks);

    // 4) Return total
    echo json_encode([
        'status' => 'success',
        'data'   => ['provenRoutes' => $provenRoutes]
    ]);
} catch (Exception $e) {
    // On error, respond accordingly
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage()
    ]);
} 
?>