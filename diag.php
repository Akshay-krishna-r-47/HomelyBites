<?php
session_start();
include 'db_connect.php';
$user_id = $_SESSION['user_id'] ?? 0;
echo "User ID: $user_id<br>";
echo "Seller Approved in Session: " . ($_SESSION['seller_approved'] ?? 'null') . "<br>";
echo "Delivery Approved in Session: " . ($_SESSION['delivery_approved'] ?? 'null') . "<br>";

$stmt = $conn->prepare("SELECT seller_approved, delivery_approved FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($sa, $da);
$stmt->fetch();
$stmt->close();
echo "Seller Approved in DB: $sa<br>";
echo "Delivery Approved in DB: $da<br>";

echo "Seller Apps:<br>";
$res1 = $conn->query("SELECT application_id, status FROM seller_applications WHERE user_id = $user_id ORDER BY created_at DESC");
while($r = $res1->fetch_assoc()) {
    echo $r['application_id'] . " - " . $r['status'] . "<br>";
}

echo "Delivery Apps:<br>";
$res2 = $conn->query("SELECT id, status FROM delivery_applications WHERE user_id = $user_id ORDER BY created_at DESC");
while($r = $res2->fetch_assoc()) {
    echo $r['id'] . " - " . $r['status'] . "<br>";
}
?>
