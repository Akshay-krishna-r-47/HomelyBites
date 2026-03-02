<?php
include 'db_connect.php';

echo "All Orders in latest 10:\n";
$res = $conn->query("SELECT order_id, status, delivery_partner_id, seller_id FROM orders ORDER BY order_id DESC LIMIT 10");
if ($res->num_rows > 0) {
    while($row = $res->fetch_assoc()) {
        echo print_r($row, true);
    }
} else {
    echo "No orders found.\n";
}
?>
