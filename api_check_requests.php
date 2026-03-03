<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['delivery_approved']) || $_SESSION['delivery_approved'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Check for any pending delivery request for this user that hasn't timed out
$sql = "SELECT dr.request_id, dr.order_id, dr.created_at, dr.expires_at,
               o.total_amount, o.address as dropoff_address, 
               s.name as seller_name, CONCAT(s.street, ', ', s.city) as pickup_address
        FROM delivery_requests dr
        JOIN orders o ON dr.order_id = o.order_id
        JOIN users s ON o.seller_id = s.user_id
        WHERE dr.delivery_partner_id = ? 
        AND dr.status = 'Pending' 
        AND dr.expires_at > NOW()
        ORDER BY dr.created_at DESC LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $request = $result->fetch_assoc();
    
    // Calculate remaining seconds
    $expires = strtotime($request['expires_at']);
    $now = time();
    $remaining = max(0, $expires - $now);
    
    if ($remaining > 0) {
        echo json_encode([
            'success' => true, 
            'has_request' => true, 
            'request' => $request,
            'time_remaining' => $remaining
        ]);
        exit();
    } else {
        // Technically this should be handled by a background cron or during check, let's mark it as timeout
        $upd = $conn->prepare("UPDATE delivery_requests SET status = 'Timeout' WHERE request_id = ?");
        $upd->bind_param("i", $request['request_id']);
        $upd->execute();
    }
} 

echo json_encode(['success' => true, 'has_request' => false]);
?>
