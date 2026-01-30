<?php
include 'db_connect.php';

// 1. Create Foods Table (if not exists)
$sql_foods = "CREATE TABLE IF NOT EXISTS foods (
    id INT PRIMARY KEY AUTO_INCREMENT,
    seller_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    category VARCHAR(100),
    image VARCHAR(255),
    is_veg TINYINT(1) DEFAULT 1,
    status ENUM('Available', 'Out of Stock') DEFAULT 'Available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES users(user_id)
)";
if ($conn->query($sql_foods)) {
    echo "Table 'foods' checks out.<br>";
} else {
    echo "Error checking 'foods': " . $conn->error . "<br>";
}

// 2. Create Orders Table (if not exists)
// Note: 'items' column is kept for backward compatibility with customer_orders.php
$sql_orders = "CREATE TABLE IF NOT EXISTS orders (
    order_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    items TEXT, 
    total_amount DECIMAL(10,2) NOT NULL,
    status VARCHAR(50) DEFAULT 'Pending', 
    delivery_date DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
)";
if ($conn->query($sql_orders)) {
    echo "Table 'orders' checks out.<br>";
} else {
    echo "Error checking 'orders': " . $conn->error . "<br>";
}

// 3. Create Order Items Table (The Missing Link)
$sql_items = "CREATE TABLE IF NOT EXISTS order_items (
    item_id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    food_id INT NOT NULL,
    quantity INT DEFAULT 1,
    price DECIMAL(10,2), 
    FOREIGN KEY (order_id) REFERENCES orders(order_id),
    FOREIGN KEY (food_id) REFERENCES foods(id)
)";
if ($conn->query($sql_items)) {
    echo "Table 'order_items' checks out.<br>";
} else {
    echo "Error checking 'order_items': " . $conn->error . "<br>";
}

// 4. Seed Dummy Data (only if empty)
// Ensure we have a dummy food item
$check_food = $conn->query("SELECT id FROM foods LIMIT 1");
if ($check_food->num_rows == 0 && isset($_SESSION['user_id'])) {
    // Insert a dummy food for the current user (assuming they are a seller)
    // We can't easily guess a valid seller_id without session, skipping auto-seed if not convenient
}
?>
Schema check complete. Please delete this file or ignore.
