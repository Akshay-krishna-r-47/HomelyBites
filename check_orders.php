<?php
include 'db_connect.php';

echo "All Orders statuses:\n";
$res = $conn->query("SELECT order_id, status, delivery_partner_id FROM orders ORDER BY order_id DESC LIMIT 5");
while($row = $res->fetch_assoc()) {
    echo "Order " . $row['order_id'] . " - Status: " . $row['status'] . " - Delivery Partner: " . ($row['delivery_partner_id'] ?? 'NULL') . "\n";
}
?>
