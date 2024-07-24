<?php

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/haizea-google-sheets-error.log');

require_once __DIR__ . '/google-api-php-client/vendor/autoload.php';

session_start();

try {
    $client = new Google_Client();
    $client->setAuthConfig(__DIR__ . '/google-oauth/client_secret_33609460872-v16h2pbd04oubdcthb4ivrnkkpk3oh4n.apps.googleusercontent.com.json');
    $client->addScope(Google_Service_Sheets::SPREADSHEETS);
    $client->setRedirectUri('https://leadwise.pro/eleve/eleve-plugins/api/oauth2callback.php');

    // If we're not authenticated, start the OAuth flow
    if (!isset($_SESSION['access_token'])) {
        $auth_url = $client->createAuthUrl();
        error_log("No access token, redirecting to auth URL: " . $auth_url);
        header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
        exit;
    }

    // Set the access token on the client
    $client->setAccessToken($_SESSION['access_token']);

    // If the access token is expired, refresh it
    if ($client->isAccessTokenExpired()) {
        error_log("Access token expired, refreshing...");
        $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        $_SESSION['access_token'] = $client->getAccessToken();
        error_log("New access token obtained: " . json_encode($_SESSION['access_token']));
    }

    $service = new Google_Service_Sheets($client);

    // Google Sheet ID
    $spreadsheetId = '1D5YSB6aUce-qdcE1_sALhmnoHKVwFt8eJH183kA_dLc';
    $range = 'leads!A:I';

    // Get the form data
    $raw_input = file_get_contents('php://input');
    error_log("Raw input received: " . $raw_input);

    $formData = json_decode($raw_input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON decode error: " . json_last_error_msg());
    }


    // Prepare the values to insert
    $values = [
        [
            date('Y-m-d H:i:s'),
            $formData['field1'] ?? '',
            $formData['field2'] ?? '',
            $formData['field3'] ?? '',
            $formData['field4'] ?? '',
            $formData['field11'] ?? '',
            $formData['field8'] ?? '',
            $formData['field9'] ?? '',
            $formData['field10'] ?? ''
        ]
    ];

    $body = new Google_Service_Sheets_ValueRange([
        'values' => $values
    ]);
    $params = [
        'valueInputOption' => 'USER_ENTERED'
    ];

    $result = $service->spreadsheets_values->append($spreadsheetId, $range, $body, $params);
    error_log("Data successfully appended to sheet: " . json_encode($result));
    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Data added to sheet']);
} catch (Exception $e) {
    error_log('Google Sheets API error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to add data to sheet: ' . $e->getMessage()]);
}
