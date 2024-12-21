<?php

include_once('ghl_api_conf.php');

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

$tokens = load_tokens();

if (!$tokens) {
    log_error('No tokens found. Please authorize the application first.');
    exit('No tokens found. Please authorize the application first.');
}

if (time() <= $tokens['expires_in']) {
    log_error('Access token not expired. Refreshing...');

    $data = [
        'grant_type' => 'refresh_token',
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'refresh_token' => $tokens['refresh_token'],
    ];

    $response = curl_post($token_url, $data, ['Content-Type: application/x-www-form-urlencoded']);
    $response_data = json_decode($response, true);

    log_error('Refresh token response: ' . json_encode($response_data));

    if (isset($response_data['access_token'])) {
        $tokens['access_token'] = $response_data['access_token'];
        $tokens['refresh_token'] = $response_data['refresh_token'];
        $tokens['expires_in'] = time() + $response_data['expires_in'];

        store_tokens($tokens);

        log_error('Tokens refreshed successfully.');
    } else {
        log_error('Error refreshing tokens: ' . $response);
        exit('Error refreshing tokens: ' . $response);
    }
} else {
    log_error('Access token is expired. Please authorize again. ');
echo 'Access token is expired. Please authorize again. ';
}