<?php
include 'db_connect.php';
$res = $conn->query('SHOW COLUMNS FROM orders');
while($row = $res->fetch_assoc()) { echo $row['Field'] . "\n"; }
?>
