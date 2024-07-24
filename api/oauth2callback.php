<?php
require_once __DIR__ . '/vendor/autoload.php';

session_start();

$client = new Google_Client();
$client->setAuthConfig(__DIR__ . '/google-oauth/client_secret_33609460872-v16h2pbd04oubdcthb4ivrnkkpk3oh4n.apps.googleusercontent.com.json');
$client->setRedirectUri('https://leadwise.pro/eleve/eleve-plugins/api/oauth2callback.php');
$client->addScope(Google_Service_Sheets::SPREADSHEETS);

if (!isset($_GET['code'])) {
    $auth_url = $client->createAuthUrl();
    header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
    exit;
} else {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    $client->setAccessToken($token);

    // Store the token to a file
    if (!file_exists(__DIR__ . '/google-oauth')) {
        mkdir(__DIR__ . '/google-oauth', 0700, true);
    }
    file_put_contents(__DIR__ . '/google-oauth/token.json', json_encode($client->getAccessToken()));

    // Redirect back to the original page
    header('Location: ' . filter_var('https://leadwise.pro/eleve/eleve-plugins/api/googlesheets-haizea.php', FILTER_SANITIZE_URL));
}