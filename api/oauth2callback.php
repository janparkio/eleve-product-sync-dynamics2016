<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/haizea-google-sheets-oauth-error.log');

require_once __DIR__ . '/google-api-php-client/vendor/autoload.php';

session_start();

try {
    $client = new Google_Client();
    $client->setAuthConfig(__DIR__ . '/google-oauth/client_secret_33609460872-v16h2pbd04oubdcthb4ivrnkkpk3oh4n.apps.googleusercontent.com.json');
    $client->setRedirectUri('https://leadwise.pro/eleve/eleve-plugins/api/oauth2callback.php');
    $client->addScope(Google_Service_Sheets::SPREADSHEETS);

    if (!isset($_GET['code'])) {
        $auth_url = $client->createAuthUrl();
        error_log("No auth code, redirecting to auth URL: " . $auth_url);
        header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
        exit;
    } else {
        error_log("Received auth code: " . $_GET['code']);
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        error_log("Fetched access token: " . json_encode($token));
        $client->setAccessToken($token);

        // Store the token to a file
        if (!file_exists(__DIR__ . '/google-oauth')) {
            mkdir(__DIR__ . '/google-oauth', 0700, true);
        }
        $tokenPath = __DIR__ . '/google-oauth/token.json';
        file_put_contents($tokenPath, json_encode($client->getAccessToken()));
        error_log("Stored token in file: " . $tokenPath);

        // Store the token in session
        $_SESSION['access_token'] = $token;
        error_log("Stored token in session");

        // Redirect back to the original page
        $redirect_uri = 'https://leadwise.pro/eleve/eleve-plugins/api/googlesheets-haizea.php';
        error_log("Redirecting to: " . $redirect_uri);
        header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
        exit;
    }
} catch (Exception $e) {
    error_log('OAuth error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    http_response_code(500);
    echo "An error occurred during authentication: " . $e->getMessage();
}