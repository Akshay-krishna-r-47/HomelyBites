<?php
include 'db_connect.php';

$sql = "SELECT user_id, street, city, pincode FROM users WHERE (latitude IS NULL OR longitude IS NULL) AND street IS NOT NULL AND street != ''";
$result = $conn->query($sql);

$updated = 0;

while ($row = $result->fetch_assoc()) {
    $user_id = $row['user_id'];
    $address = $row['street'] . ', ' . $row['city'] . ', ' . $row['pincode'];
    $address_encoded = urlencode($address);

    $url = "https://nominatim.openstreetmap.org/search?format=json&q={$address_encoded}";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'HomelyBitesServer/1.0 (admin@homelybites.com)');
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code == 200 && $response) {
        $data = json_decode($response, true);
        if (!empty($data) && isset($data[0]['lat']) && isset($data[0]['lon'])) {
            $lat = $data[0]['lat'];
            $lon = $data[0]['lon'];

            $update_sql = "UPDATE users SET latitude = ?, longitude = ? WHERE user_id = ?";
            $stmt = $conn->prepare($update_sql);
            if ($stmt) {
                $stmt->bind_param("ddi", $lat, $lon, $user_id);
                $stmt->execute();
                $stmt->close();
                $updated++;
            }
        }
    }
    sleep(1); // Sleep 1 second to respect Nominatim usage policy (max 1 request/s)
}

echo "Updated $updated users' coordinates.\n";
?>
