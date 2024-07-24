<?php
// googlesheets-haizea.php

// Ensure that this script only responds to POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method Not Allowed');
}

// Get the raw POST data
$json = file_get_contents('php://input');

// Decode the JSON data
$data = json_decode($json, true);

// Verify that the data was successfully decoded
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    die('Invalid JSON');
}

// Google Sheets API endpoint
$sheetId = '1D5YSB6aUce-qdcE1_sALhmnoHKVwFt8eJH183kA_dLc';
$range = 'leads!A:I'; // Adjust this if your sheet name is different
$apiKey = 'AIzaSyByhePTuulO2ma-3Jnb-6CNTx3VTjrc-XM'; // Replace with your actual API key

$url = "https://sheets.googleapis.com/v4/spreadsheets/{$sheetId}/values/{$range}:append?valueInputOption=USER_ENTERED&key={$apiKey}";

// Prepare the data for Google Sheets
$values = [
    [
        date('Y-m-d H:i:s'), // Fecha de Envío
        $data['field1'] ?? '', // Nombre
        $data['field2'] ?? '', // Apellido
        $data['field3'] ?? '', // Email corporativo
        $data['field4'] ?? '', // Teléfono
        $data['field11'] ?? '', // Nombre de la empresa
        $data['field8'] ?? '', // Cargo/Rol
        $data['field9'] ?? '', // Sector
        $data['field10'] ?? '' // Consultas
    ]
];

$body = json_encode([
    'values' => $values
]);

// Initialize cURL session
$ch = curl_init($url);

// Set cURL options
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($body)
]);

// Execute cURL request
$response = curl_exec($ch);

// Check for errors
if (curl_errno($ch)) {
    http_response_code(500);
    die('Curl error: ' . curl_error($ch));
}

// Close cURL session
curl_close($ch);

// Decode the response
$result = json_decode($response, true);

// Check if the update was successful
if (isset($result['updates'])) {
    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Data added to sheet']);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to add data to sheet']);
}
?>