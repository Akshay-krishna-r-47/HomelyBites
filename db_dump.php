<?php
include 'db_connect.php';
$res = $conn->query("SHOW TABLES");
$tables = [];
while ($row = $res->fetch_array()) {
    $tables[] = $row[0];
}
echo "Tables:\n";
print_r($tables);

foreach ($tables as $table) {
    echo "\nSchema for $table:\n";
    $res = $conn->query("DESCRIBE $table");
    while ($row = $res->fetch_assoc()) {
        echo "{$row['Field']} - {$row['Type']}\n";
    }
}
?>
