<?php
include 'db_connect.php';

echo "Pending Orders:\n";
$res = $conn->query("SELECT order_id, status, delivery_partner_id, seller_id FROM orders WHERE status = 'Pending'");
if ($res->num_rows > 0) {
    while($row = $res->fetch_assoc()) {
        echo print_r($row, true);
    }
} else {
    echo "No pending orders found in the database.\n";
}
?>
