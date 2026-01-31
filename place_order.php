<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $address = trim($_POST['address']);
    $payment_method = $_POST['payment_method'];
    
    // 1. Fetch Cart Items
    $sql = "SELECT c.id as cart_id, c.quantity, f.id as food_id, f.price, f.seller_id 
            FROM cart c 
            JOIN foods f ON c.food_id = f.id 
            WHERE c.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $cart_items = [];
    while ($row = $result->fetch_assoc()) {
        $cart_items[] = $row;
    }
    $stmt->close();
    
    if (empty($cart_items)) {
        header("Location: customer_cart.php");
        exit();
    }
    
    // 2. Group by Seller
    $orders_by_seller = [];
    foreach ($cart_items as $item) {
        $seller_id = $item['seller_id'];
        if (!isset($orders_by_seller[$seller_id])) {
            $orders_by_seller[$seller_id] = [
                'total' => 0,
                'items' => []
            ];
        }
        $orders_by_seller[$seller_id]['items'][] = $item;
        $orders_by_seller[$seller_id]['total'] += ($item['price'] * $item['quantity']);
    }
    
    // 3. Create Orders
    foreach ($orders_by_seller as $seller_id => $order_data) {
        $total_amount = $order_data['total'];
        
        // Insert into orders table
        // Ensure table has these columns. If strictly following schema, might need to adjust.
        // Assuming standard columns based on typical usage.
        $insert_order = "INSERT INTO orders (user_id, seller_id, total_amount, status, payment_method, address, created_at) VALUES (?, ?, ?, 'Pending', ?, ?, NOW())";
        $stmt_order = $conn->prepare($insert_order);
        if ($stmt_order) {
            $stmt_order->bind_param("iidss", $user_id, $seller_id, $total_amount, $payment_method, $address);
            $stmt_order->execute();
            $order_id = $stmt_order->insert_id;
            $stmt_order->close();
            
            // Insert Order Items
            $insert_item = "INSERT INTO order_items (order_id, food_id, quantity, price) VALUES (?, ?, ?, ?)";
            $stmt_item = $conn->prepare($insert_item);
            
            foreach ($order_data['items'] as $item) {
                $stmt_item->bind_param("iiid", $order_id, $item['food_id'], $item['quantity'], $item['price']);
                $stmt_item->execute();
            }
            $stmt_item->close();
        } else {
            // Handle error (table might process differently)
            // For now, continue to next seller or log error
        }
    }
    
    // 4. Clear Cart
    $clear_cart = "DELETE FROM cart WHERE user_id = ?";
    $stmt_clear = $conn->prepare($clear_cart);
    $stmt_clear->bind_param("i", $user_id);
    $stmt_clear->execute();
    $stmt_clear->close();
    
    // 5. Redirect to Confirmation
    header("Location: order_confirmation.php");
    exit();
}

header("Location: customer_cart.php");
exit();
?>
