<?php
/**
 * google_login.php
 * Handles redirection to Google OAuth 2.0 consent screen.
 */

// Configuration - Use constants from config.php
require_once 'config.php';

$client_id = GOOGLE_CLIENT_ID;
$redirect_uri = GOOGLE_REDIRECT_URI;
$scope = "email profile";
$response_type = "code";

// Construct the Google Auth URL
$auth_url = "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
    'client_id' => $client_id,
    'redirect_uri' => $redirect_uri,
    'scope' => $scope,
    'response_type' => $response_type,
    'access_type' => 'offline',
    'prompt' => 'select_account'
]);

// Redirect the user
header("Location: " . $auth_url);
exit();
?>
