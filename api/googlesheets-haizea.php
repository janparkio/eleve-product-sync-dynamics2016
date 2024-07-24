<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/google-sheets-error.log');

require_once __DIR__ . '/google-api-php-client/vendor/autoload.php';

// Get the raw input
$raw_input = file_get_contents('php://input');
error_log("Raw input received: " . $raw_input);

// Decode the JSON data
$formData = json_decode($raw_input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("JSON decode error: " . json_last_error_msg());
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON']);
    exit;
}

// Set up Google Sheets API client
$client = new Google_Client();
$client->setAuthConfig(__DIR__ . '/google-oauth/client_secret_33609460872-v16h2pbd04oubdcthb4ivrnkkpk3oh4n.apps.googleusercontent.com.json');
$client->setScopes(Google_Service_Sheets::SPREADSHEETS);

// Load previously authorized token from a file.
$tokenPath = __DIR__ . '/google-oauth/token.json';
if (file_exists($tokenPath)) {
    $accessToken = json_decode(file_get_contents($tokenPath), true);
    $client->setAccessToken($accessToken);
}

// If there is no previous token or it's expired.
if ($client->isAccessTokenExpired()) {
    // Refresh the token if possible, else fetch a new one.
    if ($client->getRefreshToken()) {
        $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
    } else {
        error_log("No valid access token found");
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'No valid access token']);
        exit;
    }
    // Save the token to a file.
    if (!file_exists(dirname($tokenPath))) {
        mkdir(dirname($tokenPath), 0700, true);
    }
    file_put_contents($tokenPath, json_encode($client->getAccessToken()));
}

$service = new Google_Service_Sheets($client);

// Your Google Sheet ID
$spreadsheetId = '1D5YSB6aUce-qdcE1_sALhmnoHKVwFt8eJH183kA_dLc';
$range = 'leads!A:I';

// Prepare the values to insert
$values = [
    [
        date('Y-m-d H:i:s'), // Fecha de Envío
        $formData['field1'] ?? '', // Nombre
        $formData['field2'] ?? '', // Apellido
        $formData['field3'] ?? '', // Email corporativo
        $formData['field4'] ?? '', // Teléfono
        $formData['field11'] ?? '', // Nombre de la empresa
        $formData['field8'] ?? '', // Cargo/Rol
        is_array($formData['field9']) ? implode(', ', $formData['field9']) : $formData['field9'], // Sector
        $formData['field10'] ?? '' // Consultas
    ]
];

$body = new Google_Service_Sheets_ValueRange([
    'values' => $values
]);
$params = [
    'valueInputOption' => 'USER_ENTERED'
];

try {
    $result = $service->spreadsheets_values->append($spreadsheetId, $range, $body, $params);
    error_log("Data successfully appended to sheet: " . json_encode($result->getUpdates()));
    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Data added to sheet']);
} catch (Exception $e) {
    error_log('Google Sheets API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to add data to sheet: ' . $e->getMessage()]);
}