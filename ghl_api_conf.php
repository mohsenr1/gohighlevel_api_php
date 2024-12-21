<?php

require __DIR__ . '/vendor/autoload.php';

session_start();

// Enable error reporting and logging to a file
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set the path for the error log file and tokens file
$log_file = 'error_log.txt';
$tokens_file = 'tokens.json';
$user_names_file = 'user_names.json';

function log_error($message) {
    global $log_file;
    file_put_contents($log_file, date('Y-m-d H:i:s') . ' - ' . $message . PHP_EOL, FILE_APPEND);
}


// Function to get the current URL
function getCurrentUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $requestUri = $_SERVER['REQUEST_URI'];
    return $protocol . $host . $requestUri;
}

/* ---------------------------------------- */
/* ---------------------------------------- */
/* ---------------------------------------- */
/*      Go High Level API Credentials       */
/* ---------------------------------------- */
/* ---------------------------------------- */
/* ---------------------------------------- */
$client_id = 'your_client_id';
$client_secret = 'your_client_secret';
$locationID = 'your_location_ID';


//Make sure to put the exact same scopes defined in the marketplace otherwise the authorization process will give you 400 error. 
$scope = 'contacts.readonly contacts.write opportunities.readonly workflows.readonly funnels/funnel.readonly funnels/redirect.write funnels/redirect.readonly funnels/page.readonly calendars/groups.readonly calendars/groups.write calendars/resources.readonly calendars/resources.write calendars/events.write calendars/events.readonly calendars.write calendars.readonly users.readonly';

$authorization_url = 'https://marketplace.gohighlevel.com/oauth/chooselocation';
$token_url = 'https://services.leadconnectorhq.com/oauth/token';
$funnels_url = "https://services.leadconnectorhq.com/funnels/funnel/list";
$calendars_url = "https://services.leadconnectorhq.com/calendars/";



// Get the current URL dynamically
$current_url = getCurrentUrl();

// Extract the file name from the URL
$file_name = basename(parse_url($current_url, PHP_URL_PATH));
$redirection_url = '';

// Check if the URL contains ".local" to determine if it's a staging environment
// Make sure to point to the folder containing these scripts. 
if (strpos($current_url, '.local') !== false) {
    $redirection_url .='https://localhost.local/ghl/';
}
else {
    $redirection_url .='https://domain.com/ghl/';
}

$redirection_url .= $file_name;



/* ---------------------------------------- */
/* ---------------------------------------- */
/* ---------------------------------------- */
/*      Google Sheets API Credentials       */
/* ---------------------------------------- */
/* ---------------------------------------- */
/* ---------------------------------------- */
/* 
Follow this guide to generate credentials.json and accesstoken and the login accoun:
https://developers.google.com/sheets/api/guides/concepts
https://materialplus.srijan.net/resources/blog/integrating-google-sheets-with-php-is-this-easy-know-how

Make sure that the GSheet is shared with this user:
XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX.iam.gserviceaccount.com

*/
$g_client = new Google_Client();
$g_client->setApplicationName('Google Sheets API PHP Quickstart');
$g_client->setScopes(Google_Service_Sheets::SPREADSHEETS);
$g_client->setAuthConfig('credentials.json');
$g_client->setAccessToken('XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX');

