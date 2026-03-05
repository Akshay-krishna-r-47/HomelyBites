<?php
include 'db_connect.php';
$alter_sql = "ALTER TABLE orders MODIFY COLUMN status ENUM('Pending','Preparing','Ready for Pickup','Accepted by Delivery','Arrived at Restaurant','Picked Up','Out for Delivery','Delivered','Cancelled','Scheduled') DEFAULT 'Pending'";
if ($conn->query($alter_sql)) {
    echo "Table 'orders' altered successfully.";
} else {
    echo "Error altering table: " . $conn->error;
}
?>
