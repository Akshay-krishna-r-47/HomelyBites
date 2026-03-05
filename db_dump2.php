<?php
include 'db_connect.php';
$res = $conn->query("SHOW TABLES");
$tables = [];
while ($row = $res->fetch_array()) {
    $tables[] = $row[0];
}

$schema = [];
foreach ($tables as $table) {
    if ($table === 'orders' || $table === 'scheduled_deliveries') {
        $res = $conn->query("DESCRIBE $table");
        $cols = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $cols[] = $row;
            }
        }
        $schema[$table] = $cols;
    }
}
file_put_contents('schema.json', json_encode($schema, JSON_PRETTY_PRINT));
?>
