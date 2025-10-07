


<?php
header('Content-Type: application/json');
require 'dbcon.php'; // Ensure this file correctly initializes your Firebase connection

try {
    // Get the data from the "reports/optimized_routes" node in Firebase
    $reportsData = $database->getReference('reports/optimized_routes')->getValue();

    // Initialize count to 0 if no data is found
    $count = 0;
    if ($reportsData) {
        // If data is an array, count the number of keys (reports)
        if (is_array($reportsData)) {
            $count = count($reportsData);
        }
    }

    // Return the count as JSON
    echo json_encode([
        'status' => 'success',
        'data'   => $count
    ]);
} catch (Exception $e) {
    // In case of an error, return an error message
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage()
    ]);
}
?>


