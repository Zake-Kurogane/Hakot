<?php
// fetch_truck_stats_by_truck.php
require 'dbcon.php';  // This file should initialize your Firebase $database instance

header('Content-Type: application/json');

// Determine timeframe parameter
$timeframe = 'daily';
if (isset($_GET['month'])) {
    $timeframe = 'monthly';
} elseif (isset($_GET['year'])) {
    $timeframe = 'yearly';
}

if ($timeframe === 'daily') {
    $selected = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
} elseif ($timeframe === 'monthly') {
    $selected = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
} elseif ($timeframe === 'yearly') {
    $selected = isset($_GET['year']) ? $_GET['year'] : date('Y');
}

// ------------- CROSS-REFERENCE TRUCKS for kmPerLiter -------------
$allTrucks = $database->getReference("trucks")->getValue();
$truckKmPerLiterMap = [];
if ($allTrucks && is_array($allTrucks)) {
    foreach ($allTrucks as $truckId => $truckInfo) {
        if (!empty($truckInfo['vehicleName'])) {
            $name  = $truckInfo['vehicleName'];
            $kmpl  = isset($truckInfo['kmPerLiter']) ? floatval($truckInfo['kmPerLiter']) : 0;
            $truckKmPerLiterMap[$name] = $kmpl;
        }
    }
}
// -----------------------------------------------------------------

// Usage data in /reports/truckusagedata
$truckUsageData = $database->getReference("reports/truckusagedata")->getValue();

$groupedData = [];

// Summation
if ($truckUsageData && is_array($truckUsageData)) {
    foreach ($truckUsageData as $report) {
        if (isset($report['date'], $report['truckName'])) {
            $isMatch = false;
            $dateVal = $report['date'];

            if ($timeframe === 'daily' && substr($dateVal, 0, 10) === $selected) {
                $isMatch = true;
            } elseif ($timeframe === 'monthly' && substr($dateVal, 0, 7) === $selected) {
                $isMatch = true;
            } elseif ($timeframe === 'yearly' && substr($dateVal, 0, 4) === $selected) {
                $isMatch = true;
            }

            if (!$isMatch) continue;

            $truckName = $report['truckName'];

            if (!isset($groupedData[$truckName])) {
                $groupedData[$truckName] = [
                    'truckName'                => $truckName,
                    'totalFuelUsed'            => 0,
                    'totalTimeTravel'          => 0,
                    'totalDisposedTrashWeight' => 0,
                    'totalKilometersTraveled'  => 0,
                    'count'                    => 0
                ];
            }

            $groupedData[$truckName]['totalFuelUsed']            += $report['fuelUsed']            ?? 0;
            $groupedData[$truckName]['totalTimeTravel']          += $report['timeTravel']          ?? 0;
            $groupedData[$truckName]['totalDisposedTrashWeight'] += $report['disposedTrashWeight'] ?? 0;

            if (!empty($report['kilometersTraveled'])) {
                $groupedData[$truckName]['totalKilometersTraveled'] += floatval($report['kilometersTraveled']);
            }
            $groupedData[$truckName]['count']++;
        }
    }
}

// Compute averages
$truckStats = [];
foreach ($groupedData as $truckName => $data) {
    if ($data['count'] > 0) {
        $kmpl = isset($truckKmPerLiterMap[$truckName]) ? $truckKmPerLiterMap[$truckName] : 0;
        if ($kmpl < 1) $kmpl = 0; // avoid dividing by 0

        $avgFuelUsed            = $data['totalFuelUsed']            / $data['count'];
        $avgTimeTravel          = $data['totalTimeTravel']          / $data['count']; // in seconds
        $avgDisposedTrashWeight = $data['totalDisposedTrashWeight'] / $data['count'];
        $avgKilometersTraveled  = $data['totalKilometersTraveled']  / $data['count'];

        $avgDistanceFuel = 0;
        if ($kmpl > 0) {
            $avgDistanceFuel = ($avgKilometersTraveled / $kmpl);
        }

        $truckStats[] = [
            'truckName'              => $truckName,
            'avgFuelUsed'            => $avgFuelUsed,
            'avgTimeTravel'          => $avgTimeTravel,
            'avgDisposedTrashWeight' => $avgDisposedTrashWeight,

            'avgKilometersTraveled'  => $avgKilometersTraveled,
            'avgDistanceFuel'        => $avgDistanceFuel
        ];
    }
}

$response = [
    "status" => "success",
    "data"   => $truckStats
];

echo json_encode($response);
