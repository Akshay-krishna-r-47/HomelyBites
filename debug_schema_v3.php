<?php
include 'db_connect.php';

echo "USERS TABLE:\n";
$result = $conn->query("DESCRIBE users");
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . "\n";
}

echo "\nDELIVERY_APPLICATIONS TABLE:\n";
$result = $conn->query("DESCRIBE delivery_applications");
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
?>
