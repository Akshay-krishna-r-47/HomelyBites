<?php
include 'db_connect.php';

// Fix 1: Update NULL or empty statuses to 'active'
$sql = "UPDATE foods SET status = 'Available' WHERE status IS NULL OR status = '' OR status != 'Available'"; // Reverting to 'Available' to match DB Schema

if ($conn->query($sql) === TRUE) {
    echo "Fixed food statuses: " . $conn->affected_rows . " rows updated.<br>";
} else {
    echo "Error updating records: " . $conn->error . "<br>";
}

// Fix 2: Ensure image columns are NULL if they are empty string (optional, but good for logic)
// $sql_img = "UPDATE foods SET image = NULL WHERE image = ''";
// $conn->query($sql_img);

echo "Data cleanup complete. Please check the dashboards.";
$conn->close();
?>
