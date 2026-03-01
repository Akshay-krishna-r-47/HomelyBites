<?php
include 'db_connect.php';

$sql1 = "ALTER TABLE seller_applications MODIFY COLUMN status ENUM('Pending','Approved','Rejected','Deactivated') DEFAULT 'Pending'";
if ($conn->query($sql1) === TRUE) {
    echo "Seller applications enum updated successfully.<br>";
} else {
    echo "Error updating seller applications: " . $conn->error . "<br>";
}

$sql2 = "ALTER TABLE delivery_applications MODIFY COLUMN status ENUM('Pending','Approved','Rejected','Deactivated') DEFAULT 'Pending'";
if ($conn->query($sql2) === TRUE) {
    echo "Delivery applications enum updated successfully.<br>";
} else {
    echo "Error updating delivery applications: " . $conn->error . "<br>";
}
?>
