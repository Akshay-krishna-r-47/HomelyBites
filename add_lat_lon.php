<?php
include 'db_connect.php';

// Add latitude and longitude to orders table
$sql1 = "ALTER TABLE orders ADD COLUMN IF NOT EXISTS latitude DECIMAL(10,8) NULL";
$sql2 = "ALTER TABLE orders ADD COLUMN IF NOT EXISTS longitude DECIMAL(11,8) NULL";

if ($conn->query($sql1) === TRUE && $conn->query($sql2) === TRUE) {
    echo "Latitude and Longitude columns added to orders table successfully.";
} else {
    echo "Error: " . $conn->error;
}
?>
