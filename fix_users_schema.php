<?php
include 'db_connect.php';

$sql1 = "ALTER TABLE users MODIFY COLUMN status ENUM('Active','Inactive','Banned','Deleted') DEFAULT 'Active'";
if ($conn->query($sql1) === TRUE) {
    echo "Users enum updated successfully.<br>";
} else {
    echo "Error updating users enum: " . $conn->error . "<br>";
}

$sql2 = "UPDATE users SET status = 'Deleted' WHERE status = ''";
if ($conn->query($sql2) === TRUE) {
    echo "Fixed empty user apps.<br>";
}
?>
