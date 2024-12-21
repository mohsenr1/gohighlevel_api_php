<?php

include_once('ghl_api_conf.php');
include_once('functions.php');

//Test Sheet ID and Name
//$sheet_ID = 'XXXXXXXXXXXXXXXX-XXXXXXXXXXXXXXXXXXXXXXXXXXX';
//$sheet_name = 'Sheet1'; // Change this to the name of the sheet you want to target

//Production Sheet ID and Name
$sheet_ID = 'XXXXXXXXXXXXXXXX-XXXXXXXXXXXXXXXXXXXXXXXXXXX';
$sheet_name = 'GHL ALL Funnels and pages (PHP)'; // Change this to the name of the sheet you want to target

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


function print_funnels_table($funnels) {
    global $locationID, $gSheetService, $sheet_ID, $sheet_name, $sheetId;

    // Prepare values to write to the Google Sheet
    $values = [
        ['Funnel Name', 'Funnel URL', 'Step Name', 'Step URL']
    ];

    ?>
    <style>
        table {font-family: tahoma; font-size:12px;}
        #process-indicator {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            padding: 20px;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            font-size: 16px;
            border-radius: 5px;
            z-index: 1000;
        }
    </style>
    <?php
    echo '<table border="1" cellspacing="0" cellpadding="10">';
    echo '<tr><th>Funnel Name</th><th>Funnel URL</th><th>Step Name</th><th>Step URL</th></tr>';
    $color = '#f2f2f2';
    $rowCount = 1; // Start counting rows for conditional formatting
    $rowRanges = [];
    foreach ($funnels as $funnel) {
        $step_count = count($funnel['steps']);
        $first_row_printed = false;
        $color = ($color == '#f2f2f2') ? '#ffffff' : '#f2f2f2'; // Alternate row color
        $startRow = $rowCount;

        if ($step_count > 0) {
            foreach ($funnel['steps'] as $step) {
                echo '<tr style="background-color:' . $color . ';">';
                if (!$first_row_printed) {
                    $funnelName = $funnel['name'];
                    $funnelUrl = 'https://app.gohighlevel.com/v2/location/' . $locationID . '/funnels-websites/funnels/' . $funnel['_id'];
                    echo '<td>' . $funnelName . '</td>';
                    echo '<td><a href="' . $funnelUrl . '">' . $funnelUrl . '</a></td>';
                    $first_row_printed = true;
                } else {
                    echo '<td></td><td></td>';
                }
                $stepName = $step['name'];
                $stepUrl = 'https://your_domain' . $step['url'];
                echo '<td>' . $stepName . '</td>';
                echo '<td><a href="' . $stepUrl . '">' . $stepUrl . '</a></td>';
                echo '</tr>';

                // Add row to the Google Sheet values
                $values[] = [$funnelName, $funnelUrl, $stepName, $stepUrl];
                $rowCount++;
            }
        } else {
            $funnelName = $funnel['name'];
            $funnelUrl = 'https://app.gohighlevel.com/v2/location/' . $locationID . '/funnels-websites/funnels/' . $funnel['_id'];
            echo '<tr style="background-color:' . $color . ';">';
            echo '<td>' . $funnelName . '</td>';
            echo '<td><a href="' . $funnelUrl . '">' . $funnelUrl . '</a></td>';
            echo '<td>No steps available</td><td></td>';
            echo '</tr>';

            // Add row to the Google Sheet values
            $values[] = [$funnelName, $funnelUrl, 'No steps available', ''];
            $rowCount++;
        }

        $endRow = $rowCount;
        $rowRanges[] = ['start' => $startRow, 'end' => $endRow, 'color' => $color];
    }
    echo '</table>';

    // Write values to the Google Sheet
    $body = new Google_Service_Sheets_ValueRange(['values' => $values]);
    $options = ['valueInputOption' => 'USER_ENTERED'];
    $result = $gSheetService->spreadsheets_values->update($sheet_ID, $sheet_name . '!A1', $body, $options);

    // Output the result
    print($result->getUpdatedRange() . PHP_EOL);

    // Apply alternating row colors by funnel groups
    $requests = [];
    foreach ($rowRanges as $range) {
        $requests[] = new Google_Service_Sheets_Request([
            'repeatCell' => [
                'range' => [
                    'sheetId' => $sheetId,
                    'startRowIndex' => $range['start'],
                    'endRowIndex' => $range['end'],
                    'startColumnIndex' => 0,
                    'endColumnIndex' => 4
                ],
                'cell' => [
                    'userEnteredFormat' => [
                        'backgroundColor' => ($range['color'] == '#f2f2f2') ?
                            ['red' => 0.95, 'green' => 0.95, 'blue' => 0.95] :
                            ['red' => 1, 'green' => 1, 'blue' => 1]
                    ]
                ],
                'fields' => 'userEnteredFormat.backgroundColor'
            ]
        ]);
    }

    $batchUpdateRequest = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
        'requests' => $requests
    ]);

    $gSheetService->spreadsheets->batchUpdate($sheet_ID, $batchUpdateRequest);
}
//log_error(json_encode($tokens));

if (isset($_SESSION['access_token'])) {
	$access_token = $_SESSION['access_token'];
	$queryParams = [
		'locationId' => $locationID
	];
	$url = $funnels_url . '?' . http_build_query($queryParams);

	$curl = curl_init();

	curl_setopt_array($curl, [
		CURLOPT_URL => $url,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => "",
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 30,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => "GET",
		CURLOPT_HTTPHEADER => [
			"Accept: application/json",
			"Authorization: Bearer $access_token",
			"Version: 2021-04-15"
		],
	]);

	$response = curl_exec($curl);
	$err = curl_error($curl);

	curl_close($curl);

	if ($err) {
		echo "cURL Error #:" . $err;
		log_error("cURL Error #:" . $err);
	} else {
		$response_data = json_decode($response, true);

		if (isset($response_data['funnels']) && is_array($response_data['funnels'])) {
			print_funnels_table($response_data['funnels']);
		} else {
			echo 'No funnels found.';
		}
	}
} else {
	echo 'Access token not available. Please authorize the app first.';
	log_error('Access token not available. Please authorize the app first.');
}


?>