<?php
include 'db_connect.php';

// Add profile_image column if it doesn't exist
$sql = "SHOW COLUMNS FROM users LIKE 'profile_image'";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    $alter_sql = "ALTER TABLE users ADD COLUMN profile_image VARCHAR(255) DEFAULT NULL";
    if ($conn->query($alter_sql) === TRUE) {
        echo "Column 'profile_image' added successfully.";
    } else {
        echo "Error adding column: " . $conn->error;
    }
} else {
    echo "Column 'profile_image' already exists.";
}

$conn->close();
?>
