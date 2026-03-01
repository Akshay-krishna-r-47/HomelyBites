<?php
include 'db_connect.php';

$sql = "ALTER TABLE foods ADD COLUMN stock INT NOT NULL DEFAULT 0";
if ($conn->query($sql) === TRUE) {
    echo "Column 'stock' added to 'foods' table successfully.";
} else {
    echo "Error adding column: " . $conn->error;
}
?>
