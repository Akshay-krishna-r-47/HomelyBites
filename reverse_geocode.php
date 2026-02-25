<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if (!isset($_GET['lat']) || !isset($_GET['lon'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing latitude or longitude']);
    exit;
}

$lat = floatval($_GET['lat']);
$lon = floatval($_GET['lon']);

// OpenStreetMap Nominatim API
$url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$lat}&lon={$lon}";

// Initialize cURL session
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// Nominatim explicitly REQUIRES a valid User-Agent header
curl_setopt($ch, CURLOPT_USERAGENT, 'HomelyBitesServer/1.0 (admin@homelybites.com)');
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
// Attempt to bypass SSL verification issues on local XAMPP environments
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    http_response_code(500);
    echo json_encode(['error' => 'cURL Error: ' . curl_error($ch)]);
} elseif ($http_code !== 200) {
    http_response_code($http_code);
    echo json_encode(['error' => "HTTP Error {$http_code}"]);
} else {
    // Pass the raw JSON response directly back to the client
    echo $response;
}

curl_close($ch);
?>
