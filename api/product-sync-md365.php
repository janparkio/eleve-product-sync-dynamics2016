<?php
// product-sync-md365.php

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuration
$config = [
    'old_api_url' => 'http://190.128.233.147:8088/api/ContactOPP/',
    'new_api_url' => 'https://portal.americana.edu.py:7219/api/Leads',
    // Add any necessary authentication details here
];

// Function to log messages for debugging
function debug_log($message, $verbose = false)
{
    if (!$verbose || (defined('DEBUG_VERBOSE') && DEBUG_VERBOSE)) {
        echo date('[Y-m-d H:i:s] ') . $message . PHP_EOL;
    }
}

// New function to load and match product from products.json
function load_and_match_product($md_id_carrera)
{
    $products = load_json_file('products.json');
    foreach ($products as $product) {
        if ($product['md_id_carrera'] === $md_id_carrera) {
            return $product;
        }
    }
    return null;
}

// Updated make_api_request function with reduced logging
function make_api_request($url, $method = 'GET', $data = null)
{
    $ch = curl_init();

    if ($method === 'GET' && $data) {
        $url .= '?' . http_build_query($data);
    }

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Only for testing. Remove in production.
    curl_setopt($ch, CURLOPT_VERBOSE, true);

    $verbose = fopen('php://temp', 'w+');
    curl_setopt($ch, CURLOPT_STDERR, $verbose);

    $headers = ['Accept: application/json'];

    if ($method === 'POST' || $method === 'PATCH') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            $headers[] = 'Content-Type: application/json';
        }
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    debug_log("Making $method request to: $url");
    debug_log("Headers: " . json_encode($headers), true);
    if ($data) {
        debug_log("Data: " . json_encode($data), true);
    }

    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($result === FALSE) {
        debug_log('cURL Error: ' . curl_error($ch));
        curl_close($ch);
        return null;
    }

    curl_close($ch);

    debug_log("Response Code: $http_code");
    debug_log("Response Body: " . substr($result, 0, 100) . "...", true); // Log only the first 100 characters

    if ($http_code >= 400) {
        debug_log("HTTP Error: $http_code. Response: $result");
        return null;
    }

    return json_decode($result, true);
}

// Function to load JSON file
function load_json_file($filename)
{
    $json_data = file_get_contents($filename);
    return json_decode($json_data, true);
}

// Function to match city
function match_city($id_ciudad, $cities)
{
    foreach ($cities as $city) {
        if ($city['id_ciudad'] === $id_ciudad) {
            return $city['new_ciudadesid'];
        }
    }
    return null;
}

// Function to match origin
function match_origin($codigo_origen, $origins)
{
    foreach ($origins as $origin) {
        if ($origin['new_codigo'] === $codigo_origen) {
            return $origin['new_origenesid'];
        }
    }
    return null;
}

// Function to get programs from new API
function get_programs()
{
    global $config;
    return make_api_request($config['new_api_url'] . '/consulta-programas');
}

// Function to get cities from new API
function get_cities()
{
    global $config;
    return make_api_request($config['new_api_url'] . '/consulta-ciudades');
}

// Function to get origins from new API
function get_origins()
{
    global $config;
    return make_api_request($config['new_api_url'] . '/consulta-origenes');
}

// Updated sync_lead_new function with reduced logging
function sync_lead_new($lead_data)
{
    global $config;

    $url = $config['new_api_url'] . '/sync-lead';

    // Initialize cURL session
    $ch = curl_init($url);

    // Set cURL options
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => "GET",  // Use GET method as per API specification
        CURLOPT_POSTFIELDS => json_encode($lead_data),  // Send data in the body
        CURLOPT_RETURNTRANSFER => true,  // Return the response as a string
        CURLOPT_HTTPHEADER => [
            'accept: */*',
            'Content-Type: application/json'
        ],
        CURLOPT_SSL_VERIFYPEER => false,  // Disable SSL verification (remove this in production)
        CURLOPT_VERBOSE => true  // Enable verbose output for debugging
    ]);

    // Capture verbose output
    $verbose = fopen('php://temp', 'w+');
    curl_setopt($ch, CURLOPT_STDERR, $verbose);

    // Execute the request
    $response = curl_exec($ch);

    // Check for errors
    if (curl_errno($ch)) {
        debug_log("cURL Error: " . curl_error($ch));
        curl_close($ch);
        return null;
    }

    // Get the HTTP status code
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Close cURL resource
    curl_close($ch);

    // Log verbose output
    rewind($verbose);
    $verboseLog = stream_get_contents($verbose);
    debug_log("Verbose curl output: " . $verboseLog, true);

    debug_log("API Response (HTTP $http_code): " . substr($response, 0, 100) . "...", true);

    // Check if the response is valid JSON
    $result = json_decode($response, true);

    if (json_last_error() === JSON_ERROR_NONE) {
        debug_log("Successfully synced with new API.");
        return $result;
    } else {
        debug_log("Failed to sync with new API. Invalid JSON response.");
        return null;
    }
}

// Function to sync lead with old API (keep original functionality)
function sync_lead_old($lead_data)
{
    global $config;
    return make_api_request($config['old_api_url'], 'POST', $lead_data);
}

// Updated function to map product data to new program data
function map_product_to_program($product, $programs)
{
    foreach ($programs as $program) {
        if ($program['new_codigoprograma'] === $product['md_codigo_carrera']) {
            return [
                'new_programaid' => $program['new_programaid'],
                'new_clasificacion' => $program['new_clasificacion'],
                'new_name' => $program['new_name'],
                // Add any other fields you need
            ];
        }
    }
    return null;
}

// Function to process HubSpot data
// dateadded: 05/08/2024 11:20PM ASU
function process_hubspot_data($request_body)
{
    $data = json_decode($request_body, true);
    $properties = $data['properties'] ?? [];

    $hubspot_data = [
        'hs_latest_source' => $properties['hs_latest_source']['value'] ?? null,
        'hs_object_id' => $properties['hs_object_id']['value'] ?? null,
        'tmp_product_interest_dynamics_landing' => $properties['tmp_product_interest_dynamics_landing']['value'] ?? null,
        'hs_latest_source_data_1' => $properties['hs_latest_source_data_1']['value'] ?? null,
        'observations__dynamics_' => $properties['observations__dynamics_']['value'] ?? null,
        'firstname' => $properties['firstname']['value'] ?? "Sin Nombre",
        'lastname' => $properties['lastname']['value'] ?? "Sin Apellido",
        'email' => $properties['email']['value'] ?? null,
        'phone' => $properties['phone']['value'] ?? "111",
        'md_city' => $properties['md_city']['value'] ?? "AS8",
        'numero_de_cedula' => $properties['numero_de_cedula']['value'] ?? null,
        'modalidad_de_estudio' => $properties['modalidad_de_estudio']['value'] ?? null,
        'product_id_dynamics' => $properties['product_id_dynamics']['value'] ?? null,
        'gd_student_type_antiquity' => $properties['gd_student_type_antiquity']['value'] ?? "NUEVO",
        'campaign_attribution' => $properties['campaign_attribution']['value'] ?? "UNKNOWN",
        'prefered_contact_channel' => $properties['prefered_contact_channel']['value'] ?? null,
        'tmp_md_first_json_sent' => $properties['tmp_md_first_json_sent']['value'] ?? null,
        'tmp_md_object_id' => $properties['md_object_id']['value'] ?? null,
        'codigo_origen' => str_replace(' ', '', $properties['codigo_origen']['value'] ?? "OL-ODH"),
    ];

    // Validate numero_de_cedula
    if (!$hubspot_data['numero_de_cedula'] || $hubspot_data['numero_de_cedula'] < 500000 || $hubspot_data['numero_de_cedula'] > 9999999) {
        $hubspot_data['numero_de_cedula'] = $hubspot_data['hs_object_id'];
    }

    return $hubspot_data;
}

// Main function to handle product synchronization
function sync_product($hubspot_data)
{
    debug_log("Starting product sync for HubSpot object ID: " . $hubspot_data['hs_object_id']);

    // Load and match product from products.json
    $matched_product = load_and_match_product($hubspot_data['product_id_dynamics']);
    if (!$matched_product) {
        debug_log("Failed to match product in products.json. Aborting sync.");
        return null;
    }
    debug_log("Matched product: " . $matched_product['md_nombre_carrera']);

    // Get programs from new API
    $programs = get_programs();
    if (!$programs) {
        debug_log("Failed to fetch programs. Aborting sync.");
        return null;
    }

    // Map product to new program
    $mapped_program = map_product_to_program($matched_product, $programs);
    if (!$mapped_program) {
        debug_log("Failed to map product to program. Aborting sync.");
        return null;
    }
    debug_log("Mapped to program: " . $mapped_program['new_name']);

    // Get origins from new API
    $origins = get_origins();
    if (!$origins) {
        debug_log("Failed to fetch origins. Aborting sync.");
        return null;
    }

    // Load cities from JSON file
    $cities = load_json_file('cities.json');
    if (!$cities) {
        debug_log("Failed to load cities. Aborting sync.");
        return null;
    }

    // Match city
    $new_ciudad_value = match_city($hubspot_data['md_city'], $cities);
    if (!$new_ciudad_value) {
        debug_log("Failed to match city. Using default value.");
        $new_ciudad_value = $hubspot_data['md_city']; // Use original value if matching fails
    }

    // Match origin
    $new_origen_value = match_origin($hubspot_data['codigo_origen'], $origins);
    if (!$new_origen_value) {
        debug_log("Failed to match origin. Using default value.");
        $new_origen_value = '1ef5b097-d2f1-ee11-a1fe-002248371974'; // Use default value if matching fails
    }

    // Prepare lead data for new API
    $new_lead_data = [
        "leadid" => "", //optional
        "firstname" => $hubspot_data['firstname'],
        "lastname" => $hubspot_data['lastname'],
        "mobilephone" => $hubspot_data['phone'],
        "new_identificacion" => $hubspot_data['numero_de_cedula'], //optional
        "emailaddress1" => $hubspot_data['email'],
        "_new_ciudad_value" => $new_ciudad_value, //optional
        "_new_origen_value" => $new_origen_value, //optional
        "crd9f_fuenteoriginal" => $hubspot_data['hs_latest_source'], //optional
        "_new_programa_value" => $mapped_program['new_programaid'], //optional
        // "_new_sede_value" => "4a893dd3-373e-ef11-a316-002248e133a1", //optional
        "new_idhubspot" => $hubspot_data['hs_object_id'], //optional
        "crd9f_clasificaciondeproducto" => $mapped_program['new_clasificacion'], //optional
        "preferredcontactmethodcode" => 1, //$hubspot_data['prefered_contact_channel'] -> not ready yet
        "statecode" => 0,
        "statuscode" => 100000002,

        "ownerid" => "" // You may need to provide a valid owner ID
    ];

    // Sync with new API
    $new_result = sync_lead_new($new_lead_data);
    if ($new_result) {
        debug_log("Successfully synced with new API.");
        debug_log("New API response: " . json_encode($new_result), true);
    } else {
        debug_log("Failed to sync with new API.");
        return null;
    }

    // Prepare response similar to the old script
    $response_data = [
        'hs_execution_state' => 'complete',
        'hs_status_code' => 200,
        'hs_server_response' => $new_result,
        'id_carrera' => $matched_product['md_id_carrera'],
        'md_object_id' => $hubspot_data['tmp_md_object_id'],
        'tmp_json_sent_md365' => json_encode([
            'data_sent' => $new_lead_data,
            'server_response' => $new_result,
        ], JSON_UNESCAPED_UNICODE),
    ];

    return $response_data;
}

// Main execution
$request_body = file_get_contents('php://input');
$hubspot_data = process_hubspot_data($request_body);

// Set to true to enable verbose logging
define('DEBUG_VERBOSE', false);

$result = sync_product($hubspot_data);

if ($result) {
    header('Content-Type: application/json');
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
} else {
    $error_response = [
        'hs_execution_state' => 'error',
        'hs_status_code' => 500,
        'hs_server_response' => 'Failed to sync lead.',
    ];
    header('Content-Type: application/json');
    echo json_encode($error_response, JSON_UNESCAPED_UNICODE);
}

// Example usage
/*
    $product_data = [
        'md_id_carrera' => 'a51e9fce-2c4d-ec11-b945-00505689be00', // This should match an entry in products.json
        'firstname' => 'John',
        'lastname' => 'Leads Prueba',
        'phone' => '595123456789',
        'email' => 'john.doe@example.com',
        'numero_de_cedula' => '123456789',
        'fuente_origen' => 'OL-5585',
        'id_hubspot' => '000001',
        'id_ciudad' => 'BO16',
    ];
*/