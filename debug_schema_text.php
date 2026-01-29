<?php
include 'db_connect.php';

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "DESCRIBE foods";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "Field: " . $row["Field"] . " | Type: " . $row["Type"] . "\n";
    }
} else {
    echo "0 results";
}
$conn->close();
?>
