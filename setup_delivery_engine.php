<?php
include 'db_connect.php';

// Force create delivery_earnings table
$sql2 = "CREATE TABLE IF NOT EXISTS delivery_earnings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    delivery_partner_id INT NOT NULL,
    order_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (delivery_partner_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE
)";
if ($conn->query($sql2) === TRUE) {
    echo "Table 'delivery_earnings' created successfully.<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}
?>
