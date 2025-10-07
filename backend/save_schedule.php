<?php
// backend/save_schedule.php

require 'dbcon.php'; // Your Firebase connection
require_once 'onesignalid.php'; // External OneSignal IDs
header('Content-Type: application/json');

function sendOneSignalNotification($playerId, $message) {
    // Use the external OneSignal IDs
    global $onesignal_app_id, $onesignal_rest_key;
    
    $content = array("en" => $message);
    $fields = array(
        'app_id' => $onesignal_app_id,
        'include_player_ids' => array($playerId),
        'headings' => array("en" => "Hakot Basura - Schedule Update!"), // Added notification header
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

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode([
        'status' => 'error',
        'message' => 'Only POST requests are allowed.'
    ]);
    exit;
}

// Parse JSON input
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400); // Bad Request
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid JSON format.'
    ]);
    exit;
}

// Validate required fields
if (!isset($data['truckKey'], $data['shift'], $data['schedules'])) {
    http_response_code(400); // Bad Request
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing truckKey, shift, or schedules.'
    ]);
    exit;
}

$truckKey = trim($data['truckKey']);
$shift = trim($data['shift']); // "day" or "night"
$schedules = $data['schedules']; // array of day objects

// Optionally read morning/afternoon times
$morningStart    = isset($data['morningStart'])    ? trim($data['morningStart'])    : '';
$morningEnd      = isset($data['morningEnd'])      ? trim($data['morningEnd'])      : '';
$afternoonStart  = isset($data['afternoonStart'])  ? trim($data['afternoonStart'])  : '';
$afternoonEnd    = isset($data['afternoonEnd'])    ? trim($data['afternoonEnd'])    : '';

// Basic validations
if (empty($truckKey)) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'truckKey cannot be empty.']);
    exit;
}
if ($shift !== 'day' && $shift !== 'night') {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Shift must be "day" or "night".']);
    exit;
}
if (!is_array($schedules) || count($schedules) === 0) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'schedules must be a non-empty array.']);
    exit;
}

// Define all days of the week and prepare an array for them
$allDaysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
$daysData = array_fill_keys($allDaysOfWeek, ['places' => []]);

/**
 * Function to generate a unique ID.
 * Generates 16 random bytes and returns a 32-character hexadecimal string.
 */
function generateUniqueId() {
    return bin2hex(random_bytes(16));
}

// --------------------------------------------------
// Fetch existing schedule for this truck (if any)
// --------------------------------------------------
$existingDaysData = [];
$existingScheduleSnapshot = $database->getReference("trucks/{$truckKey}/schedules")->getSnapshot();
if ($existingScheduleSnapshot->exists()) {
    $existingSchedule = $existingScheduleSnapshot->getValue();
    if (isset($existingSchedule['days']) && is_array($existingSchedule['days'])) {
        $existingDaysData = $existingSchedule['days'];
    }
}

try {
    // Process each schedule entry from the incoming data
    foreach ($schedules as $entry) {
        // Each entry must have 'week' and 'places'
        if (!isset($entry['week'], $entry['places'])) {
            throw new Exception('Each schedule entry must have "week" and "places".');
        }
        $dayName = trim($entry['week']);
        if ($dayName === '') {
            throw new Exception('week cannot be empty.');
        }
        if (!in_array($dayName, $allDaysOfWeek)) {
            throw new Exception('Invalid day name: ' . $dayName);
        }
        $places = $entry['places'];
        if (!is_array($places)) {
            throw new Exception('places must be an array.');
        }

        // Process each place
        foreach ($places as $p) {
            // Log incoming place data for debugging
            error_log("Place data before check for day {$dayName}: " . print_r($p, true));
            
            // If an "id" is not provided or is empty after trimming,
            // search across all existing days (not only the target day)
            // to try to find a match by name (case-insensitive) or by coordinates.
            if (!isset($p['id']) || trim($p['id']) === '') {
                $foundExisting = false;
                if (!empty($existingDaysData)) {
                    foreach ($existingDaysData as $existingDay => $dayData) {
                        if (isset($dayData['places']) && is_array($dayData['places'])) {
                            foreach ($dayData['places'] as $existingPlace) {
                                // First check by place name (case-insensitive)
                                if (strcasecmp(trim($existingPlace['name']), trim($p['name'])) === 0) {
                                    $p['id'] = $existingPlace['id'];
                                    $foundExisting = true;
                                    error_log("Found existing id by name: " . $p['id'] . " (from day {$existingDay})");
                                    break 2;
                                }
                                // Fallback: check if coordinates (rounded to 6 decimal places) match
                                $existingLat = round(floatval($existingPlace['latitude']), 6);
                                $existingLng = round(floatval($existingPlace['longitude']), 6);
                                $newLat = round(floatval($p['latitude']), 6);
                                $newLng = round(floatval($p['longitude']), 6);
                                if (abs($existingLat - $newLat) < 1e-6 && abs($existingLng - $newLng) < 1e-6) {
                                    $p['id'] = $existingPlace['id'];
                                    $foundExisting = true;
                                    error_log("Found existing id by coordinates: " . $p['id'] . " (from day {$existingDay})");
                                    break 2;
                                }
                            }
                        }
                    }
                }
                if (!$foundExisting) {
                    $p['id'] = generateUniqueId();
                    error_log("Generated new id for place on {$dayName}: " . $p['id']);
                }
            } else {
                error_log("Preserving existing id: " . $p['id'] . " for day: " . $dayName);
            }

            // Validate the place name
            $placeName = trim($p['name']);
            if ($placeName === '') {
                throw new Exception('Place name cannot be empty.');
            }
            // Validate latitude and longitude values
            $lat = floatval($p['latitude']);
            $lng = floatval($p['longitude']);
            if ($lat < -90 || $lat > 90) {
                throw new Exception('Invalid latitude value for place: ' . $placeName);
            }
            if ($lng < -180 || $lng > 180) {
                throw new Exception('Invalid longitude value for place: ' . $placeName);
            }
            // Round coordinates to 6 decimal places
            $lat = round($lat, 6);
            $lng = round($lng, 6);

            // Add the processed place to the day's array
            $daysData[$dayName]['places'][] = [
                'id'        => $p['id'],
                'name'      => $placeName,
                'latitude'  => $lat,
                'longitude' => $lng
            ];
        }
    }

    // Build the final schedule data array
    $finalData = [
        'shift'           => $shift,            // "day" or "night"
        'morningStart'    => $morningStart,     // relevant only if day shift
        'morningEnd'      => $morningEnd,
        'afternoonStart'  => $afternoonStart,
        'afternoonEnd'    => $afternoonEnd,
        'days'            => $daysData
    ];

    // Write the schedule data to "trucks/{truckKey}/schedules"
    $database->getReference("trucks/{$truckKey}/schedules")->set($finalData);

    // -------------------------------------------------------------------------------
    // NEW FEATURE: Update any user in "usersUID" whose "placeId" matches a schedule
    // place id. For each matching user, update:
    //   - nearestPin    => schedule place's name
    //   - latitude      => schedule place's latitude (as string)
    //   - longitude     => schedule place's longitude (as string)
    //   - collectionDay => the day (e.g., "Monday", "Tuesday", etc.)
    // -------------------------------------------------------------------------------
    foreach ($daysData as $day => $dayData) {
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
                        // Check if the user data differs from the new updateData
                        $shouldUpdate = false;
                        foreach ($updateData as $field => $value) {
                            if (!isset($userData[$field]) || $userData[$field] !== $value) {
                                $shouldUpdate = true;
                                break;
                            }
                        }
                        if ($shouldUpdate) {
                            $database->getReference("usersUID/{$userKey}")->update($updateData);
                            if (isset($userData['oneSignalId']) && !empty($userData['oneSignalId'])) {
                                sendOneSignalNotification($userData['oneSignalId'], "Your schedule has been updated. Please open the app for more details.");
                            }
                        }
                    }
                }
            }
        }
    }
    

    $newPlaceIds = [];
    foreach ($daysData as $day => $data) {
        if (isset($data['places']) && is_array($data['places'])) {
            foreach ($data['places'] as $place) {
                $newPlaceIds[] = $place['id'];
            }
        }
    }
    // Now iterate over the existing schedule and find any place id not in the new list
    foreach ($existingDaysData as $oldDay => $oldData) {
        if (isset($oldData['places']) && is_array($oldData['places'])) {
            foreach ($oldData['places'] as $oldPlace) {
                if (!in_array($oldPlace['id'], $newPlaceIds)) {
                    // This place was deleted from the schedule; update corresponding users to clear fields
                    $usersSnapshot = $database->getReference("usersUID")
                        ->orderByChild("placeId")
                        ->equalTo($oldPlace['id'])
                        ->getSnapshot();
                    if ($usersSnapshot->exists()) {
                        $users = $usersSnapshot->getValue();
                        foreach ($users as $userKey => $userData) {
                            $updateData = [
                                'nearestPin'    => "",
                                'latitude'      => "",
                                'longitude'     => "",
                                'collectionDay' => "",
                                'placeId'       => ""
                            ];
                            $shouldUpdate = false;
                            foreach ($updateData as $field => $value) {
                                if (!isset($userData[$field]) || $userData[$field] !== $value) {
                                    $shouldUpdate = true;
                                    break;
                                }
                            }
                            if ($shouldUpdate) {
                                $database->getReference("usersUID/{$userKey}")->update($updateData);
                                if (isset($userData['oneSignalId']) && !empty($userData['oneSignalId'])) {
                                    sendOneSignalNotification($userData['oneSignalId'], "Your schedule has been cleared.");
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    // -------------------------------------------------------------------------------

    // Respond with success
    echo json_encode([
        'status'  => 'success',
        'message' => 'Schedule saved successfully.',
        'data'    => $finalData // Optionally return the final schedule data
    ]);
  
} catch (Exception $ex) {
    // Handle exceptions and respond with error
    http_response_code(500); // Internal Server Error
    echo json_encode([
        'status' => 'error',
        'message' => $ex->getMessage()
    ]);
}
?>
