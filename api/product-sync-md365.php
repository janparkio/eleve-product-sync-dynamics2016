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
function debug_log($message)
{
    echo date('[Y-m-d H:i:s] ') . $message . PHP_EOL;
}

// Updated function to make API requests
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
    debug_log("Headers: " . json_encode($headers));
    if ($data) {
        debug_log("Data: " . json_encode($data));
    }

    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    rewind($verbose);
    $verboseLog = stream_get_contents($verbose);
    debug_log("Verbose information:\n" . $verboseLog);

    if ($result === FALSE) {
        debug_log('cURL Error: ' . curl_error($ch));
        curl_close($ch);
        return null;
    }

    curl_close($ch);

    debug_log("Response Code: $http_code");
    debug_log("Response Body: $result");

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

// Function to sync lead with new API (seems that only accepts GET and the parameters)
// Comment: It's likely that the API expects the data to be sent in a specific format in the request body, even for a GET request (which is unusual but not impossible).
// function sync_lead_new($lead_data)
// {
//     global $config;

//     // Try GET request first
//     debug_log("Attempting GET request for sync-lead");
//     $result = make_api_request($config['new_api_url'] . '/sync-lead', 'GET', $lead_data);

//     if ($result === null) {
//         // If GET fails, try POST
//         debug_log("GET request failed. Attempting POST request for sync-lead");
//         $result = make_api_request($config['new_api_url'] . '/sync-lead', 'POST', $lead_data);
//     }

//     return $result;
// }

function sync_lead_new($lead_data) {
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
    debug_log("Verbose curl output: " . $verboseLog);
    
    debug_log("API Response (HTTP $http_code): $response");
    
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

// Function to map old product data to new program data
function map_product_to_program($old_product, $programs)
{
    foreach ($programs as $program) {
        if ($program['new_codigoprograma'] === $old_product['md_codigo_carrera']) {
            return [
                'new_programaid' => $program['new_programaid'],
                'new_clasificacion' => $program['new_clasificacion'],
                // Add any other fields you need
            ];
        }
    }
    return null;
}

// Main function to handle product synchronization
function sync_product($product_data)
{
    // Sync with old API
    // $old_result = sync_lead_old($old_lead_data);
    // if ($old_result) {
    //     debug_log("Successfully synced with old API.");
    // } else {
    //     debug_log("Failed to sync with old API.");
    // }

    debug_log("Starting product sync for: " . $product_data['hs_nombre_producto']);

    // Get programs from new API
    $programs = get_programs();
    if (!$programs) {
        debug_log("Failed to fetch programs. Aborting sync.");
        return;
    }

    // Get origins from new API
    $origins = get_origins();
    if (!$origins) {
        debug_log("Failed to fetch origins. Aborting sync.");
        return;
    }

    // Load cities from JSON file
    $cities = load_json_file('cities.json');
    if (!$cities) {
        debug_log("Failed to load cities. Aborting sync.");
        return;
    }

    // Map old product to new program
    $mapped_program = map_product_to_program($product_data, $programs);
    if (!$mapped_program) {
        debug_log("Failed to map product to program. Aborting sync.");
        return;
    }

    // Match city
    $new_ciudad_value = match_city($product_data['id_ciudad'], $cities);
    if (!$new_ciudad_value) {
        debug_log("Failed to match city. Using default value.");
        $new_ciudad_value = $product_data['_new_ciudad_value']; // Use default value if matching fails
    }

    // Match origin
    $new_origen_value = match_origin($product_data['fuente_origen'], $origins);
    if (!$new_origen_value) {
        debug_log("Failed to match origin. Using default value.");
        $new_origen_value = '1ef5b097-d2f1-ee11-a1fe-002248371974'; // Use default value if matching fails
    }

    // In the sync_product function, update the $new_lead_data array to match the Swagger documentation:
    $new_lead_data = [
        "leadid" => "", // Leave empty for new leads
        "firstname" => $product_data['firstname'],
        "lastname" => $product_data['lastname'],
        "mobilephone" => $product_data['phone'],
        "new_identificacion" => $product_data['numero_de_cedula'],
        "emailaddress1" => $product_data['email'],
        "_new_ciudad_value" => $new_ciudad_value,
        "_new_origen_value" => $new_origen_value,
        "crd9f_fuenteoriginal" => $product_data['fuente_origen'],
        "_new_programa_value" => $mapped_program['new_programaid'],
        "_new_sede_value" => "4a893dd3-373e-ef11-a316-002248e133a1", // You may want to make this dynamic
        "new_idhubspot" => $product_data['id_hubspot'],
        "crd9f_clasificaciondeproducto" => $mapped_program['new_clasificacion'],
        "preferredcontactmethodcode" => 1,
        "statecode" => 0,
        "statuscode" => 100000002,
        "ownerid" => "" // You may need to provide a valid owner ID
    ];


    // Sync with new API
    $new_result = sync_lead_new($new_lead_data);
    if ($new_result) {
        debug_log("Successfully synced with new API.");
        debug_log("New API response: " . json_encode($new_result));
    } else {
        debug_log("Failed to sync with new API.");
    }
}

// Example usage
$product_data = [
    'hs_slug_code' => 'gd-digr',
    'md_codigo_carrera' => 'DIG',
    'hs_nombre_producto' => 'Grado - Diseño Grafico',
    'md_id_carrera' => '5d1e9fce-2c4d-ec11-b945-00505689be00',
    'md_landing_value' => 'Diseño Gráfico',
    'modality' => 'Presencial',
    'firstname' => 'John',
    'lastname' => 'Leads Prueba',
    'phone' => '595123456789',
    'email' => 'john.doe@example.com',
    'numero_de_cedula' => '123456789',
    'origen' => '000000',
    'fuente_origen' => 'OL-5585',
    'id_hubspot' => '000001',
    'ciudad' => 'Borja',
    'id_ciudad' => 'BO16',
    '_new_ciudad_value' => '3a361e6e-1a24-ef11-840b-000d3ac19288', // Default value
    'new_prefered_contact_channel' => '1',
];

sync_product($product_data);