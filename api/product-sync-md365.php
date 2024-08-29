<?php
// product-sync-md365.php

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start output buffering at the very beginning
ob_start();

// Send the Content-Type header 
header('Content-Type: application/json');

// Configuration
$config = [
    'old_api_url' => 'http://190.128.233.147:8088/api/ContactOPP/',
    'new_api_url' => 'https://portal.americana.edu.py:7219/api/Leads',
    'auth_method' => 'basic',  // or 'bearer', 'api_key', 'custom', 'digest'
    'username' => 'APICRM',
    'password' => 'API_DYNAMICs*24'
    // Add other auth-related fields as needed: 'token', 'api_key', 'custom_auth_header'
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

// Updated make_api_request function with basic and plain authentication
function make_api_request($url, $method = 'GET', $data = null, $retries = 3)
{
    global $config;
    $attempt = 0;

    $auth_methods = [
        'basic_raw' => function($ch) use ($config) {
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, $config['username'] . ':' . $config['password']);
        },
        'basic_encoded' => function($ch) use ($config) {
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, $config['username'] . ':' . urlencode($config['password']));
        },
        'header_auth' => function($ch) use ($config) {
            $auth = base64_encode($config['username'] . ':' . $config['password']);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $auth]);
        }
    ];

    foreach ($auth_methods as $method_name => $auth_method) {
        $attempt = 0;
        while ($attempt < $retries) {
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

            $headers = [
                'Accept: application/json',
                'Content-Type: application/json'
            ];

            $auth_method($ch);

            if ($method === 'POST' || $method === 'PATCH') {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
                if ($data) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
            }

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            debug_log("Attempt " . ($attempt + 1) . " with $method_name: Making $method request to: $url");
            debug_log("Headers: " . json_encode(curl_getinfo($ch, CURLINFO_HEADER_OUT)), true);

            $result = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($result === FALSE) {
                debug_log('cURL Error: ' . curl_error($ch));
                debug_log('cURL Error Number: ' . curl_errno($ch));
                curl_close($ch);
                $attempt++;
                continue;
            }

            curl_close($ch);

            debug_log("Response Code: $http_code");
            debug_log("Response Body: " . $result, true);

            if ($http_code < 400) {
                return json_decode($result, true);
            }

            $attempt++;
        }
        debug_log("Failed after $retries attempts with $method_name");
    }

    debug_log("All authentication methods failed");
    return null;
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


// Updated get_programs function with error handling
function get_programs()
{
    global $config;
    $programs = make_api_request($config['new_api_url'] . '/consulta-programas');
    if (!$programs) {
        throw new Exception("Failed to fetch programs from the API");
    }
    return $programs;
}

// Updated get_cities function. not in use cities directly matched at ./cities.json
function get_cities()
{
    global $config;
    return make_api_request($config['new_api_url'] . '/consulta-ciudades');
}

// Updated get_origins function
function get_origins()
{
    global $config;
    return make_api_request($config['new_api_url'] . '/consulta-origenes');
}

// Updated sync_lead_new function
function sync_lead_new($lead_data)
{
    global $config;
    $url = $config['new_api_url'] . '/sync-lead';
    return make_api_request($url, 'POST', $lead_data);
}

// Function to sync lead with old API (keep original functionality), currenly not used.
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
        'recent_conversion_date' => $properties['recent_conversion_date']['value'] ?? null,
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

    // Process preferred_contact_channel
    $preferred_contact_channel = $properties['prefered_contact_channel']['value'] ?? null;
    $contact_channel_mapping = [
        "whatsapp" => 4,
        "email" => 2,
        "call" => 3,
        "webchat" => 1,
        "messenger" => 1,
        "sms" => 1,
    ];
    $mapped_channels = [];
    if (is_array($preferred_contact_channel)) {
        foreach ($preferred_contact_channel as $channel) {
            if (isset($contact_channel_mapping[$channel])) {
                $mapped_channels[] = $contact_channel_mapping[$channel];
            }
        }
    }
    $mapped_channels = array_unique($mapped_channels); // Remove duplicates
    sort($mapped_channels); // Sort the numbers
    $hubspot_data['preferred_contact_channel_mapped'] = implode(",", $mapped_channels); // Join with commas

    // Ensure a default value if no mapping is found
    if (empty($hubspot_data['preferred_contact_channel_mapped'])) {
        $hubspot_data['preferred_contact_channel_mapped'] = "1"; // Default to 1
    }

    // Validate numero_de_cedula
    if (!$hubspot_data['numero_de_cedula'] || $hubspot_data['numero_de_cedula'] < 500000 || $hubspot_data['numero_de_cedula'] > 9999999) {
        $hubspot_data['numero_de_cedula'] = $hubspot_data['hs_object_id'];
    }

    return $hubspot_data;
}

// Main function to handle product synchronization

// Updated sync_product function with improved error handling
function sync_product($hubspot_data)
{
    debug_log("Starting product sync for HubSpot object ID: " . $hubspot_data['hs_object_id']);

    try {
        // Load and match product from products.json
        $matched_product = load_and_match_product($hubspot_data['product_id_dynamics']);
        if (!$matched_product) {
            throw new Exception("Failed to match product in products.json");
        }
        debug_log("Matched product: " . $matched_product['md_nombre_carrera']);

        // Get programs from new API
        $programs = get_programs();

        // Map product to new program
        $mapped_program = map_product_to_program($matched_product, $programs);
        if (!$mapped_program) {
            throw new Exception("Failed to map product to program");
        }
        debug_log("Mapped to program: " . $mapped_program['new_name']);

        // Get origins from new API
        $origins = get_origins();
        if (!$origins) {
            throw new Exception("Failed to fetch origins");
        }

        // Load cities from JSON file
        $cities = load_json_file('cities.json');
        if (!$cities) {
            throw new Exception("Failed to load cities");
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
            "_new_sede_value" => null, //optional
            "new_idhubspot" => $hubspot_data['hs_object_id'], //optional
            "crd9f_clasificaciondeproducto" => $mapped_program['new_clasificacion'], //optional
            "preferredcontactmethodcode" => $hubspot_data['preferred_contact_channel_mapped'],
            "crd9f_recent_conversion_date" => $hubspot_data['recent_conversion_date'],
            // No longer send statecode and statuscode
            // "statecode" => 0,
            // "statuscode" => 100000002,

            "ownerid" => "" // You may need to provide a valid owner ID
        ];

        // Sync with new API
        $api_result = sync_lead_new($new_lead_data);
        if (!$api_result) {
            throw new Exception("Failed to sync with new API");
        }

        debug_log("Successfully synced with new API.");
        debug_log("New API response: " . json_encode($api_result), true);

        // Prepare response
        $response_data = [
            'hs_execution_state' => 'complete',
            'hs_status_code' => 200,
            'hs_server_response' => $api_result,
            'id_carrera' => $matched_product['md_id_carrera'],
            'md_object_id' => $hubspot_data['tmp_md_object_id'],
            'md_json_sent_md365' => json_encode([
                'data_sent' => $new_lead_data,
                'server_response' => $api_result,
            ], JSON_UNESCAPED_UNICODE),
        ];

        return $response_data;
    } catch (Exception $e) {
        debug_log("Error in sync_product: " . $e->getMessage());
        return null;
    }
}

// Main execution
$request_body = file_get_contents('php://input');
$hubspot_data = process_hubspot_data($request_body);

// Set to true to enable verbose logging
define('DEBUG_VERBOSE', true);

// Proceed with sync
$result = sync_product($hubspot_data);

if ($result) {
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
} else {
    $error_response = [
        'hs_execution_state' => 'error',
        'hs_status_code' => 500,
        'hs_server_response' => 'Failed to sync lead.',
    ];
    echo json_encode($error_response, JSON_UNESCAPED_UNICODE);
}

// Send the buffered output
ob_end_flush();

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