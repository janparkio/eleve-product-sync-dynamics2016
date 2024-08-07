<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuration (replace with your actual HubSpot access token and product-sync-md365.php URL)
$config = [
    'hubspot_access_token' => 'pat-na1-b334a101-ec22-4a9d-9686-4676b369473a',
    'product_sync_url' => 'http://127.0.0.1:8000/api/product-sync-md365.php', // Use a relative path
];

// Function to fetch HubSpot contact by ID (using v3 API)
function get_hubspot_contact_by_id($contactId)
{
    global $config;
    $url = "https://api.hubapi.com/crm/v3/objects/contacts/$contactId";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $config['hubspot_access_token'],
        'Content-Type: application/json'
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

// Function to post data to product-sync-md365.php with verbose output
function post_to_product_sync($data)
{
    global $config;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $config['product_sync_url']);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    // Enable verbose output
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    $verbose = fopen('php://temp', 'w+');
    curl_setopt($ch, CURLOPT_STDERR, $verbose);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Get verbose output
    rewind($verbose);
    $verbose_log = stream_get_contents($verbose);
    fclose($verbose);

    curl_close($ch);

    echo "Verbose output from product-sync-md365.php:\n";
    echo $verbose_log; // Print the verbose output

    echo "Response from product-sync-md365.php (HTTP code $http_code):\n";
    echo $response; // Print the response

    return json_decode($response, true);
}

// Function to simulate the product sync process
function simulate_product_sync($contactId)
{
    $contact = get_hubspot_contact_by_id($contactId);
    if ($contact && isset($contact['properties'])) {
        // Prepare data for product-sync-md365.php
        $data = ['properties' => $contact['properties']];

        // Post data to product-sync-md365.php
        $response = post_to_product_sync($data);

        echo "Posted data to product-sync-md365.php for contact ID: $contactId\n";
        // print_r($response); // Print the response from product-sync-md365.php (now handled in post_to_product_sync)
    } else {
        echo "Contact not found for contact ID: $contactId\n";
    }
}

// Get contact ID from command-line argument
$contactId = $argv[1] ?? null;

if ($contactId) {
    simulate_product_sync($contactId);
} else {
    echo "Please provide a contact ID as a command-line argument.\n";
    echo "Example: php test_sync_api.php 123456789\n";
}