<?php
include 'db_connect.php';
echo "Querying Users...\n";
$res = $conn->query("SELECT user_id, name, email, role, seller_approved, delivery_approved FROM users");
while ($row = $res->fetch_assoc()) {
    echo "ID: " . $row['user_id'] . " | Name: " . $row['name'] . " | Role: " . $row['role'] . " | SellerAppr: " . $row['seller_approved'] . " | DelAppr: " . $row['delivery_approved'] . "\n";
}
echo "Done.\n";
?>
