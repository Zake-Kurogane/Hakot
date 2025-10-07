<?php
// fetch_fuel_usage_comparison.php

require 'dbcon.php';

header('Content-Type: application/json');

$timeframe = isset($_GET['timeframe']) ? $_GET['timeframe'] : 'daily';
$totalFuelUsed = 0;

$truckUsageData = $database->getReference("reports/truckusagedata")->getValue();

if ($truckUsageData && is_array($truckUsageData)) {
    foreach ($truckUsageData as $report) {
        if (isset($report['date']) && isset($report['fuelUsed'])) {
            if ($timeframe === 'daily') {
                $selectedDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
                if (substr($report['date'], 0, 10) === $selectedDate) {
                    $totalFuelUsed += $report['fuelUsed'];
                }
            } elseif ($timeframe === 'monthly') {
                $selectedMonth = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
                if (substr($report['date'], 0, 7) === $selectedMonth) {
                    $totalFuelUsed += $report['fuelUsed'];
                }
            } elseif ($timeframe === 'yearly') {
                $selectedYear = isset($_GET['year']) ? $_GET['year'] : date('Y');
                if (substr($report['date'], 0, 4) === $selectedYear) {
                    $totalFuelUsed += $report['fuelUsed'];
                }
            }
        }
    }
}

$response = [
    "status" => "success",
    "data"   => $totalFuelUsed
];

echo json_encode($response);
?>
