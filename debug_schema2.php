<?php
include 'db_connect.php';

$res = $conn->query("SHOW COLUMNS FROM seller_applications LIKE 'status'");
print_r($res->fetch_assoc());

$res2 = $conn->query("SHOW COLUMNS FROM delivery_applications LIKE 'status'");
print_r($res2->fetch_assoc());
?>
