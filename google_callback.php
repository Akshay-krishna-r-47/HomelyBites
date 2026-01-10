<?php
// google_callback.php
session_start();
include 'db_connect.php';

// Configuration
require_once 'config.php';

$client_id = GOOGLE_CLIENT_ID;
$client_secret = GOOGLE_CLIENT_SECRET;
$redirect_uri = GOOGLE_REDIRECT_URI;

if (!isset($_GET['code'])) {
    header("Location: login.php?error=no_code");
    exit();
}

$code = $_GET['code'];

// 1. Exchange Auth Code for Access Token
$token_url = "https://oauth2.googleapis.com/token";
$post_fields = [
    'code' => $code,
    'client_id' => $client_id,
    'client_secret' => $client_secret,
    'redirect_uri' => $redirect_uri,
    'grant_type' => 'authorization_code'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $token_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_fields));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For localhost dev only
$response = curl_exec($ch);

if (curl_errno($ch)) {
    die("Curl error (Token): " . curl_error($ch));
}
curl_close($ch);

$data = json_decode($response, true);

if (!isset($data['access_token'])) {
    // Log error or display it
    die("Error fetching token: " . ($data['error_description'] ?? 'Unknown Error'));
}

$access_token = $data['access_token'];

// 2. Fetch User Profile
$user_info_url = "https://www.googleapis.com/oauth2/v2/userinfo";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $user_info_url);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $access_token"]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For localhost dev only
$user_info_response = curl_exec($ch);

if (curl_errno($ch)) {
    die("Curl error (UserInfo): " . curl_error($ch));
}
curl_close($ch);

$user_info = json_decode($user_info_response, true);

if (!isset($user_info['email'])) {
    die("Error: Could not retrieve email from Google.");
}

// 3. Database Logic
$email = $conn->real_escape_string($user_info['email']);
$name = $conn->real_escape_string($user_info['name']);
$google_id = $conn->real_escape_string($user_info['id']);

$check_sql = "SELECT * FROM users WHERE email = '$email'";
$result = $conn->query($check_sql);

if ($result->num_rows > 0) {
    // User exists - Update Google ID if needed
    $user = $result->fetch_assoc();
    $user_id = $user['user_id'];
    $role = $user['role']; // Keep DB role case (e.g. 'Customer')
    
    // Update login type
    $update_sql = "UPDATE users SET google_id = '$google_id', login_type = 'Google' WHERE user_id = $user_id";
    $conn->query($update_sql);

} else {
    // New User - Insert as 'customer' (lowercase as per new standard)
    $role = 'customer'; 
    $stmt = $conn->prepare("INSERT INTO users (name, email, google_id, login_type, role, status) VALUES (?, ?, ?, 'Google', ?, 'Active')");
    $stmt->bind_param("ssss", $name, $email, $google_id, $role);
    
    if ($stmt->execute()) {
        $user_id = $conn->insert_id;
    } else {
        die("Database Error: " . $conn->error);
    }
    $stmt->close();
}

// 4. Set Session
$_SESSION['user_id'] = $user_id;
$_SESSION['name'] = $name;
$_SESSION['email'] = $email;
$_SESSION['role'] = $role; //role_check.php will normalize this to lowercase if needed, but safer to match

// 5. Redirect based on role
$redirect_target = "customer_dashboard.php"; // Default

$r = strtolower($role);
if ($r === 'seller') {
    $redirect_target = "seller_dashboard.php";
} elseif ($r === 'delivery') {
    $redirect_target = "delivery_dashboard.php";
} elseif ($r === 'admin') {
    $redirect_target = "admin_dashboard.php";
}

header("Location: " . $redirect_target);
exit();
?>
