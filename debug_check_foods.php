<?php
include 'db_connect.php';

echo "<h1>Debug Food Table</h1>";

// Check if connection is alive
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 1. Total Count
$result = $conn->query("SELECT COUNT(*) as count FROM foods");
$row = $result->fetch_assoc();
echo "Total Rows in 'foods': " . $row['count'] . "<br><hr>";

// 2. Dump Rows
$sql = "SELECT id, seller_id, name, status, image FROM foods";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<table border='1'><tr><th>ID</th><th>Seller ID</th><th>Name</th><th>Status</th><th>Image</th></tr>";
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row["id"] . "</td>";
        echo "<td>" . $row["seller_id"] . "</td>";
        echo "<td>" . $row["name"] . "</td>";
        echo "<td>" . $row["status"] . "</td>"; // Check for exact string
        echo "<td>[" . $row["image"] . "]</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "0 results";
}

// 3. Check 'active' status specifically
$sql_active = "SELECT COUNT(*) as count FROM foods WHERE status = 'active'";
$res_active = $conn->query($sql_active);
$row_active = $res_active->fetch_assoc();
echo "<hr>Rows with status='active': " . $row_active['count'];

$conn->close();
?>
