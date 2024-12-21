<?php
/* ---------------------------------------- */
/* ---------------------------------------- */
/* ---------------------------------------- */
/*      GHL API Functions Credentials       */
/* ---------------------------------------- */
/* ---------------------------------------- */
/* ---------------------------------------- */
function curl_get($url, $headers) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        log_error('Curl error: ' . curl_error($ch));
    }
    curl_close($ch);
    return $response;
}

function curl_post($url, $data, $headers) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        log_error('Curl error: ' . curl_error($ch));
    }
    curl_close($ch);
    return $response;
}

function store_tokens($tokens) {
    global $tokens_file;
    file_put_contents($tokens_file, json_encode($tokens));
}

function load_tokens() {
    global $tokens_file;
    if (file_exists($tokens_file)) {
        return json_decode(file_get_contents($tokens_file), true);
    }
    return null;
}

function redirect_to_authorization() {
    global $authorization_url, $client_id, $redirection_url, $scope;
    $authorization_url = $authorization_url . '?' . http_build_query([
            'response_type' => 'code',
            'client_id' => $client_id,
            'redirect_uri' => $redirection_url,
            'scope' => $scope,
        ]);

    header('Location: ' . $authorization_url);
    exit();
}

$tokens = load_tokens();



if (isset($_GET['code'])) {
    log_error('Authorization code received: ' . $_GET['code']);
    $code = $_GET['code'];
    $data = [
        'grant_type' => 'authorization_code',
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'redirect_uri' => $redirection_url,
        'code' => $code,
        'user_type' => 'Location',
    ];

    $response = curl_post($token_url, $data, ['Content-Type: application/x-www-form-urlencoded']);
    $response_data = json_decode($response, true);

    log_error('Token response: ' . json_encode($response_data));

    if (isset($response_data['access_token'])) {
        $_SESSION['access_token'] = $response_data['access_token'];
        $_SESSION['refresh_token'] = $response_data['refresh_token'];
        $_SESSION['token_expires'] = time() + $response_data['expires_in'];

        store_tokens([
            'access_token' => $response_data['access_token'],
            'refresh_token' => $response_data['refresh_token'],
            'expires_in' => time() + $response_data['expires_in']
        ]);

        header('Location: ' . $redirection_url);
        exit();
    } else {
        echo 'Error retrieving access token: ' . $response;
        log_error('Error retrieving access token: ' . $response);
        exit();
    }
}


if ($tokens && time() < $tokens['expires_in']) {
    log_error('Tokens loaded successfully.');
    $_SESSION['access_token'] = $tokens['access_token'];
    $_SESSION['refresh_token'] = $tokens['refresh_token'];
    $_SESSION['token_expires'] = $tokens['expires_in'];
} else {
    log_error('No valid tokens found or tokens expired.');
    redirect_to_authorization();
}



function load_user_names() {
    global $user_names_file;
    if (file_exists($user_names_file)) {
        return json_decode(file_get_contents($user_names_file), true);
    }
    return [];
}

function save_user_names($user_names) {
    global $user_names_file;
    file_put_contents($user_names_file, json_encode($user_names));
}




function get_user_name($user_id, $access_token) {
    $url = 'https://services.leadconnectorhq.com/users/' . $user_id;
    $headers = [
        "Accept: application/json",
        "Authorization: Bearer $access_token",
        "Version: 2021-07-28"
    ];
    $response = curl_get($url, $headers);
    $user_data = json_decode($response, true);
    if (isset($user_data['name'])) {
        return $user_data['name'];
    } else {
        return 'Unknown';
    }
}



function getSheetId($gSheetService, $sheet_ID, $sheet_name) {
    $spreadsheet = $gSheetService->spreadsheets->get($sheet_ID);
    $sheets = $spreadsheet->getSheets();

    foreach ($sheets as $sheet) {
        $properties = $sheet->getProperties();
        if ($properties->getTitle() == $sheet_name) {
            return $properties->getSheetId();
        }
    }
    throw new Exception("Sheet not found: " . $sheet_name);
}

