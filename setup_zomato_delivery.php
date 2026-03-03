<?php
include 'db_connect.php';

// 1. Update Users Table for Delivery Status
$sql1 = "ALTER TABLE users 
         ADD COLUMN IF NOT EXISTS is_online TINYINT(1) DEFAULT 0,
         ADD COLUMN IF NOT EXISTS latitude DECIMAL(10, 8) DEFAULT NULL,
         ADD COLUMN IF NOT EXISTS longitude DECIMAL(11, 8) DEFAULT NULL,
         ADD COLUMN IF NOT EXISTS active_orders INT DEFAULT 0";

if ($conn->query($sql1) === TRUE) {
    echo "Users table updated with location and online status.<br>";
} else {
    echo "Error updating users table: " . $conn->error . "<br>";
}

// 2. Expand Orders Table Status Enum
// Using ALTER TABLE MODIFY COLUMN to keep existing statuses and add new ones
$sql2 = "ALTER TABLE orders 
         MODIFY COLUMN status ENUM('Pending', 'Preparing', 'Ready for Pickup', 'Accepted by Delivery', 'Arrived at Restaurant', 'Picked Up', 'Out for Delivery', 'Delivered', 'Cancelled') DEFAULT 'Pending'";

if ($conn->query($sql2) === TRUE) {
    echo "Orders table status enum expanded.<br>";
} else {
    echo "Error updating orders table: " . $conn->error . "<br>";
}

// 3. Create Delivery Requests Queue Table
$sql3 = "CREATE TABLE IF NOT EXISTS delivery_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    delivery_partner_id INT NOT NULL,
    status ENUM('Pending', 'Accepted', 'Rejected', 'Timeout') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (delivery_partner_id) REFERENCES users(user_id) ON DELETE CASCADE
)";

if ($conn->query($sql3) === TRUE) {
    echo "Delivery Requests table created successfully.<br>";
} else {
    echo "Error creating delivery requests table: " . $conn->error . "<br>";
}

echo "<br><b>Database Setup Complete!</b>";
?>
