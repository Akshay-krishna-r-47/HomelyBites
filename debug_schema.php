<?php
include 'db_connect.php';

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "DESCRIBE foods";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row["Field"] . "</td>";
        echo "<td>" . $row["Type"] . "</td>";
        echo "<td>" . $row["Null"] . "</td>";
        echo "<td>" . $row["Key"] . "</td>";
        echo "<td>" . $row["Default"] . "</td>";
        echo "<td>" . $row["Extra"] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "0 results";
}
$conn->close();
?>
