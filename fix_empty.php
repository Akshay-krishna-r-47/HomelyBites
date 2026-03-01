<?php
include 'db_connect.php';

$sql1 = "UPDATE seller_applications SET status = 'Deactivated' WHERE status = ''";
if ($conn->query($sql1) === TRUE) {
    echo "Fixed empty seller apps.<br>";
}

$sql2 = "UPDATE delivery_applications SET status = 'Deactivated' WHERE status = ''";
if ($conn->query($sql2) === TRUE) {
    echo "Fixed empty delivery apps.<br>";
}
?>
