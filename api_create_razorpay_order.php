<?php
session_start();
include 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Receive the raw POST data
$data = json_decode(file_get_contents('php://input'), true);
$amount = isset($data['amount']) ? floatval($data['amount']) : 0;

if ($amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid amount']);
    exit();
}

$key_id = "rzp_test_SNsFr03hSQ6fXl";
$key_secret = "mZIV1x3xx4tq549AAzwmIT0c";

// Razorpay requires amount in paise (multiply by 100)
$amount_in_paise = round($amount * 100);
$receipt_id = "rcptid_" . time() . "_" . $_SESSION['user_id'];

// Create the JSON payload
$payload = json_encode([
    'amount' => $amount_in_paise,
    'currency' => 'INR',
    'receipt' => $receipt_id,
    'payment_capture' => 1 // Auto capture
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.razorpay.com/v1/orders');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_USERPWD, $key_id . ':' . $key_secret);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

$response = curl_exec($ch);
if (curl_errno($ch)) {
    echo json_encode(['success' => false, 'message' => 'cURL Error: ' . curl_error($ch)]);
    curl_close($ch);
    exit();
}
curl_close($ch);

$result = json_decode($response, true);

if (isset($result['id'])) {
    // Return the generated Razorpay Order ID to the frontend
    echo json_encode([
        'success' => true, 
        'order_id' => $result['id'], 
        'amount' => $result['amount'],
        'currency' => $result['currency'],
        'key' => $key_id
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to create Razorpay order',
        'razorpay_error' => $result
    ]);
}
?>
