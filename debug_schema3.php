<?php
include 'db_connect.php';

$res = $conn->query("SHOW COLUMNS FROM users LIKE 'status'");
print_r($res->fetch_assoc());
?>
