<?php
include 'db_connect.php';

$sql = "CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'alert') DEFAULT 'info',
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'notifications' created successfully.";
} else {
    echo "Error creating table: " . $conn->error;
}

// Add a test notification for the current logged in user if available
// Since this script runs via CLI or browser, session might not be active, but let's try safely.
if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $conn->query("INSERT INTO notifications (user_id, title, message, type) VALUES ($uid, 'Welcome!', 'This is your first notification.', 'success')");
    echo "\nAdded test notification.";
}

$conn->close();
?>
