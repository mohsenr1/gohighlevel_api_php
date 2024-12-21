<?php

include_once('ghl_api_conf.php');
include_once('functions.php');

// Sheet ID and Name
$sheet_ID = 'your_sheet_id';
$sheet_name = 'your_sheet_name'; // Change this to the name of the sheet you want to target

$gSheetService = new Google_Service_Sheets($g_client);

/* Make sure that the GSheet is shared with this user:
XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX.iam.gserviceaccount.com
*/

// Clear the specific sheet before writing new values and remove all formatting
$sheetId = getSheetId($gSheetService, $sheet_ID, $sheet_name);

$requests = [
    new Google_Service_Sheets_Request([
        'updateCells' => [
            'range' => [
                'sheetId' => $sheetId,
            ],
            'fields' => 'userEnteredValue'
        ]
    ]),
    new Google_Service_Sheets_Request([
        'repeatCell' => [
            'range' => [
                'sheetId' => $sheetId,
            ],
            'cell' => [
                'userEnteredFormat' => [
                    'backgroundColor' => [
                        'red' => 1,
                        'green' => 1,
                        'blue' => 1
                    ]
                ]
            ],
            'fields' => 'userEnteredFormat.backgroundColor'
        ]
    ])
];

$batchUpdateRequest = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
    'requests' => $requests
]);

$gSheetService->spreadsheets->batchUpdate($sheet_ID, $batchUpdateRequest);

// Function to fetch slots and blocked slots
function fetch_slots_and_blocked($calendar_id, $timezone, $access_token, $user_id, $start_date, $end_date, $locationID) {
    $free_slots_url = 'https://services.leadconnectorhq.com/calendars/' . $calendar_id . '/free-slots?' . http_build_query([
            'startDate' => $start_date,
            'endDate' => $end_date,
            'timezone' => $timezone,
            'userId' => $user_id
        ]);

    $blocked_slots_url = 'https://services.leadconnectorhq.com/calendars/events?' . http_build_query([
            'calendarId' => $calendar_id,
            'locationId' => $locationID,
            'startTime' => $start_date,
            'endTime' => $end_date
        ]);

    $headers = [
        "Accept: application/json",
        "Authorization: Bearer $access_token",
        "Version: 2021-04-15"
    ];

    // Multi cURL handler
    $mh = curl_multi_init();
    $curl_handles = [];

    // Free slots request
    $ch1 = curl_init();
    curl_setopt($ch1, CURLOPT_URL, $free_slots_url);
    curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch1, CURLOPT_HTTPHEADER, $headers);
    curl_multi_add_handle($mh, $ch1);
    $curl_handles['free_slots'] = $ch1;

    // Blocked slots request
    $ch2 = curl_init();
    curl_setopt($ch2, CURLOPT_URL, $blocked_slots_url);
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch2, CURLOPT_HTTPHEADER, $headers);
    curl_multi_add_handle($mh, $ch2);
    $curl_handles['blocked_slots'] = $ch2;

    // Execute all queries simultaneously, and continue when all are complete
    $running = null;
    do {
        curl_multi_exec($mh, $running);
        curl_multi_select($mh);
    } while ($running > 0);

    // Collect the results
    $results = [];
    foreach ($curl_handles as $key => $ch) {
        $results[$key] = curl_multi_getcontent($ch);
        curl_multi_remove_handle($mh, $ch);
    }
    curl_multi_close($mh);

    // Parse responses
    $free_slots = json_decode($results['free_slots'], true);
    $blocked_slots = json_decode($results['blocked_slots'], true);

    return [$free_slots, $blocked_slots];
}

// Function to get slots for a specific calendar and its users
function get_slots_for_calendar($calendar_id, $access_token, $locationID) {
    $timezone = 'America/New_York';
    $all_slots = [];
    $calendar_name = '#1 Calendar'; // Assuming a default name if not fetched dynamically

    // Fetch the users associated with the calendar
    $calendar_url = 'https://services.leadconnectorhq.com/calendars/' . $calendar_id;
    $headers = [
        "Accept: application/json",
        "Authorization: Bearer $access_token",
        "Version: 2021-04-15"
    ];

    $response = curl_get($calendar_url, $headers);
    $calendar_details = json_decode($response, true);

    $start_date = strtotime(date('Y-m-d') . ' 00:00:00') * 1000;
    $end_date = strtotime(date('Y-m-d', strtotime("+7 days")) . ' 23:59:59') * 1000;

    if (isset($calendar_details['calendar']['teamMembers']) && is_array($calendar_details['calendar']['teamMembers'])) {
        foreach ($calendar_details['calendar']['teamMembers'] as $member) {
            list($free_slots, $blocked_slots) = fetch_slots_and_blocked($calendar_id, $timezone, $access_token, $member['userId'], $start_date, $end_date, $locationID);

            if (is_array($free_slots)) {
                foreach ($free_slots as $slot_data) {
                    if (isset($slot_data['slots']) && is_array($slot_data['slots'])) {
                        foreach ($slot_data['slots'] as $slot) {
							// Create a DateTime object from the string
                            $date = new DateTime($slot);

                            // Format the date to 'Thursday 30 July 2024'
                            $formattedDate = $date->format('l j F Y');

                            $all_slots[] = [
                                'calendarName' => $calendar_details['calendar']['name'],
                                'agent' => $member['userId'],
                                'slotDateTime' => $formattedDate,
                                'slotDateTimeRaw' => $slot,
                                'status' => 'free'
                            ];
                        }
                    }
                }
            }

            if (is_array($blocked_slots) && isset($blocked_slots['events'])) {
                foreach ($blocked_slots['events'] as $event) {
							// Create a DateTime object from the string
                            $date = new DateTime($event['startTime']);

                            // Format the date to 'Thursday 30 July 2024'
                            $formattedDate = $date->format('l j F Y');

                    $all_slots[] = [
                        'calendarName' => $calendar_details['calendar']['name'],
                        'agent' => $event['assignedUserId'],
                        'slotDateTime' => $formattedDate,
                        'slotDateTimeRaw' => $event['startTime'],
                        'status' => 'blocked'
                    ];
                }
            }
        }
    }

    return $all_slots;
}

if (isset($_SESSION['access_token'])) {
    $access_token = $_SESSION['access_token'];

    log_error('Fetching slots for specific calendar with access token: ' . $access_token);

    // Change the calendar ID below. You can put it in a for each loop if you want to get info of more calendars.
    $slots = get_slots_for_calendar('XXXXXXXXXXXXXXXXXXXX', $access_token, $locationID);
    print_calendars_table($slots);
} else {
    echo 'Access token not available. Please authorize the app first.';
    log_error('Access token not available. Please authorize the app first.');
}


function print_calendars_table($slots) {
    global $locationID, $gSheetService, $sheet_ID, $sheet_name, $sheetId, $access_token;

    $user_names = load_user_names(); // Load user names once at the start

    // Extract the 'agent' column from the array
    $agents = array_column($slots, 'agent');
    // Remove duplicate values
    $uniqueAgents = array_unique($agents);
    // Remove any null values (in case 'agent' was not set in some items)
    $uniqueAgents = array_filter($uniqueAgents);

    $new_user_names = [];
    $missing_user_ids = [];

    foreach ($uniqueAgents as $agent) {
        if (!isset($user_names[$agent])) {
            $missing_user_ids[] = $agent;
        }
    }

    foreach ($missing_user_ids as $user_id) {
        $new_user_names[$user_id] = get_user_name($user_id, $access_token);
    }

    $user_names = array_merge($user_names, $new_user_names);
    save_user_names($user_names);

    // Prepare values to write to the Google Sheet
    $values = [
        ['Calendar Name', 'User', 'Slot DateTime', 'Slot DateTime Raw','Status']
    ];

    $user_names = load_user_names();
    foreach ($slots as $slot) {


        $values[] = [
            htmlspecialchars($slot['calendarName']),
            htmlspecialchars($user_names[$slot['agent']] ?? $slot['agent']),
            htmlspecialchars($slot['slotDateTime']),
            htmlspecialchars($slot['slotDateTimeRaw']),
            htmlspecialchars($slot['status'])
        ];
    }

    // Write values to the Google Sheet
    $body = new Google_Service_Sheets_ValueRange(['values' => $values]);
    $options = ['valueInputOption' => 'USER_ENTERED'];
    $result = $gSheetService->spreadsheets_values->update($sheet_ID, $sheet_name . '!A1', $body, $options);

    // Output the result
    print($result->getUpdatedRange() . PHP_EOL);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendars and Slots</title>
    <style>
        body {font-family: tahoma; font-size:12px;}
        table {font-size:12px;}
    </style>
</head>
<body>
</body>
</html>