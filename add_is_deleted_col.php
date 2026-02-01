<?php
include 'db_connect.php';

// Add is_deleted column if it doesn't exist
try {
    $check = $conn->query("SHOW COLUMNS FROM foods LIKE 'is_deleted'");
    if ($check->num_rows == 0) {
        $sql = "ALTER TABLE foods ADD COLUMN is_deleted TINYINT(1) DEFAULT 0 AFTER status";
        if ($conn->query($sql)) {
            echo "Successfully added 'is_deleted' column to 'foods' table.<br>";
        } else {
            echo "Error adding column: " . $conn->error . "<br>";
        }
    } else {
        echo "Column 'is_deleted' already exists.<br>";
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage();
}

// Optional: View structure
$result = $conn->query("DESCRIBE foods");
echo "<pre>";
while($row = $result->fetch_assoc()) {
    print_r($row);
}
echo "</pre>";
?>
