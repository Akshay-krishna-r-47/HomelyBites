<?php
include 'db_connect.php';

// Add seller_approved
$sql1 = "ALTER TABLE users ADD COLUMN seller_approved TINYINT(1) DEFAULT 0";
$conn->query($sql1);
if ($conn->error && strpos($conn->error, 'Duplicate column') === false) {
    echo "Error adding seller_approved: " . $conn->error . "\n";
} else {
    echo "seller_approved column check/add completed.\n";
}

// Add delivery_approved
$sql2 = "ALTER TABLE users ADD COLUMN delivery_approved TINYINT(1) DEFAULT 0";
$conn->query($sql2);
if ($conn->error && strpos($conn->error, 'Duplicate column') === false) {
    echo "Error adding delivery_approved: " . $conn->error . "\n";
} else {
    echo "delivery_approved column check/add completed.\n";
}
?>
