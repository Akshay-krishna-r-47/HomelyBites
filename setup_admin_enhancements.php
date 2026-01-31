<?php
include 'db_connect.php';

// Create system_settings table
$sql = "CREATE TABLE IF NOT EXISTS system_settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value TEXT NOT NULL,
    description VARCHAR(255)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table system_settings created successfully.\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}

// Insert default values if they don't exist
$defaults = [
    'commission_percentage' => ['10', 'Percentage of order value taken as commission'],
    'delivery_fee_base' => ['5.00', 'Base delivery fee amount'],
    'maintenance_mode' => ['0', 'Set to 1 to enable maintenance mode'],
    'support_email' => ['support@homelybites.com', 'Contact email for support']
];

foreach ($defaults as $key => $values) {
    $val = $values[0];
    $desc = $values[1];
    
    // Check if exists
    $check = $conn->query("SELECT setting_key FROM system_settings WHERE setting_key = '$key'");
    if ($check->num_rows == 0) {
        $stmt = $conn->prepare("INSERT INTO system_settings (setting_key, setting_value, description) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $key, $val, $desc);
        if ($stmt->execute()) {
            echo "Inserted setting: $key\n";
        } else {
            echo "Error inserting $key: " . $stmt->error . "\n";
        }
    } else {
        echo "Setting $key already exists.\n";
    }
}

$conn->close();
?>
