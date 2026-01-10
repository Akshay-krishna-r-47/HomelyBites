<?php
include 'db_connect.php';
$result = $conn->query("SELECT * FROM orders");
echo "Total Orders: " . $result->num_rows . "\n";
while($row = $result->fetch_assoc()) {
    echo "ID: " . $row['order_id'] . " | Status: " . $row['status'] . "\n";
}
?>
