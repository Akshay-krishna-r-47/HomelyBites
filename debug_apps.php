<?php
include 'db_connect.php';
session_start();
echo "USER ID: " . $_SESSION['user_id'] . "\n\n";

echo "Seller Apps:\n";
$res1 = $conn->query("SELECT application_id, user_id, status, created_at FROM seller_applications ORDER BY created_at DESC");
while($r = $res1->fetch_assoc()) print_r($r);

echo "\nDelivery Apps:\n";
$res2 = $conn->query("SELECT id, user_id, status, created_at FROM delivery_applications ORDER BY created_at DESC");
while($r = $res2->fetch_assoc()) print_r($r);
?>
