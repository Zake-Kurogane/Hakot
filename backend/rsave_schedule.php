<?php
// backend/rsave_schedule.php

header('Content-Type: application/json');

// Include Firebase connection
require 'dbcon.php'; 

function sendOneSignalNotification($playerId, $message) {
    $onesignal_app_id  = '3e9baa63-ad8f-4703-a744-f4de6f2483dd'; 
    $onesignal_rest_key = 'os_v2_app_h2n2uy5nr5dqhj2e6tpg6jed3vxzplzo4cuurkupo5aki3m5dssca23z4obdu7loq7wu26vugiz7gjevvikswjnhchgkmlqlncmgt6y'; 

    $content = array("en" => $message);
    $fields = array(
        'app_id' => $onesignal_app_id,
        'include_player_ids' => array($playerId),
        'contents' => $content
    );
    $fields = json_encode($fields);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json; charset=utf-8',
        'Authorization: Basic ' . $onesignal_rest_key
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

// Get the raw POST data
$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true);

// Validate input
if (!isset($data['truckKey']) || !isset($data['schedule'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid input. truckKey and schedule are required.'
    ]);
    exit;
}

$truckKey = $data['truckKey'];
$schedule = $data['schedule'];

// Basic validation of schedule structure
if (!isset($schedule['days']) || !is_array($schedule['days'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid schedule format. "days" should be an array.'
    ]);
    exit;
}

try {
    // --------------------------
    // 1. Check if Truck Exists
    // --------------------------
    $truckSnapshot = $database->getReference("trucks/{$truckKey}")->getSnapshot();
    if (!$truckSnapshot->exists()) {
        throw new Exception("Truck with key {$truckKey} does not exist.");
    }

    // --------------------------
    // 2. Save the Schedule
    // --------------------------
    $database->getReference("trucks/{$truckKey}/schedules")->set($schedule);

    // --------------------------
    // 3. Retrieve Truck Name
    // --------------------------
    $truckName = $truckSnapshot->getChild('vehicleName')->getValue();
    if (!$truckName) {
        throw new Exception("Truck name not found for truckKey: {$truckKey}");
    }

    // --------------------------
    // 4. Create the Report
    // --------------------------
    $report = [
        'truckKey'    => $truckKey,
        'truckName'   => $truckName,
        'reportType'  => 'routes optimized',
        'timestamp'   => date('c') // ISO 8601 format
    ];

    // --------------------------
    // 5. Save the Report Under optimized_routes
    // --------------------------
    $database->getReference("reports/optimized_routes")->push($report);

    // --------------------------
    // 6. Update Users in "usersUID" for Existing or Modified Places
    // --------------------------
    // Loop over each day in the new schedule and update users that have matching placeId.
    if (isset($schedule['days']) && is_array($schedule['days'])) {
        foreach ($schedule['days'] as $day => $dayData) {
            if (isset($dayData['places']) && is_array($dayData['places'])) {
                foreach ($dayData['places'] as $place) {
                    $placeId = $place['id'];
                    // Query users in "usersUID" where "placeId" equals the schedule place's id.
                    $usersSnapshot = $database->getReference("usersUID")
                        ->orderByChild("placeId")
                        ->equalTo($placeId)
                        ->getSnapshot();
                    if ($usersSnapshot->exists()) {
                        $users = $usersSnapshot->getValue();
                        foreach ($users as $userKey => $userData) {
                            $updateData = [
                                'nearestPin'    => $place['name'],
                                'latitude'      => (string)$place['latitude'],
                                'longitude'     => (string)$place['longitude'],
                                'collectionDay' => $day
                            ];
                            $database->getReference("usersUID/{$userKey}")->update($updateData);

                            // Send notification after updating user record if a OneSignal ID exists
                            if (isset($userData['oneSignalId']) && !empty($userData['oneSignalId'])) {
                                sendOneSignalNotification($userData['oneSignalId'], "Your schedule has been updated. Please open the app for more details.");
                            }
                        }
                    }
                }
            }
        }
    }


    // Respond with success
    echo json_encode([
        'status'  => 'success',
        'message' => 'Schedule saved, report created, and user records updated successfully.',
        'data'    => $schedule // Optionally return the final schedule data
    ]);
  
} catch (Exception $ex) {
    http_response_code(500); // Internal Server Error
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to save schedule or update user records: ' . $ex->getMessage()
    ]);
}
?>
