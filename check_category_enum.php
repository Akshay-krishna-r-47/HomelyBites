<?php
include 'db_connect.php';

$res = $conn->query("SHOW COLUMNS FROM foods LIKE 'category'");
print_r($res->fetch_assoc());
?>
