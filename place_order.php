<?php
session_start();
include 'db_connect.php';
include_once 'helpers.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $address = trim($_POST['address']);
    $payment_method = $_POST['payment_method'];
    $lat = isset($_POST['latitude']) && $_POST['latitude'] !== '' ? floatval($_POST['latitude']) : null;
    $lng = isset($_POST['longitude']) && $_POST['longitude'] !== '' ? floatval($_POST['longitude']) : null;
    
    $delivery_time_type = isset($_POST['delivery_time_type']) ? $_POST['delivery_time_type'] : 'now';
    $repeat_days = isset($_POST['repeat_days']) ? max(1, intval($_POST['repeat_days'])) : 1;
    $delivery_date_base = null;
    $status = 'Pending';
    if ($delivery_time_type === 'scheduled') {
        if (!empty($_POST['delivery_date']) && !empty($_POST['del_hour']) && !empty($_POST['del_minute']) && !empty($_POST['del_ampm'])) {
            $combined_datetime = $_POST['delivery_date'] . ' ' . $_POST['del_hour'] . ':' . $_POST['del_minute'] . ' ' . $_POST['del_ampm'];
            $delivery_date_base = date('Y-m-d H:i:s', strtotime($combined_datetime));
            $status = 'Scheduled';
        } else {
            // Redirect back or exit if no valid date/time is provided
            header("Location: customer_checkout.php");
            exit();
        }
    }

    // Razorpay Verification
    $rzp_payment_id = isset($_POST['razorpay_payment_id']) ? $_POST['razorpay_payment_id'] : null;
    $rzp_order_id = isset($_POST['razorpay_order_id']) ? $_POST['razorpay_order_id'] : null;
    $rzp_signature = isset($_POST['razorpay_signature']) ? $_POST['razorpay_signature'] : null;
    
    if ($payment_method !== 'COD') {
        if (empty($rzp_payment_id) || empty($rzp_order_id) || empty($rzp_signature)) {
            die("Payment verification failed. Missing Razorpay parameters.");
        }
        
        $key_secret = "mZIV1x3xx4tq549AAzwmIT0c";
        $generated_signature = hash_hmac('sha256', $rzp_order_id . "|" . $rzp_payment_id, $key_secret);
        
        if (!hash_equals($generated_signature, $rzp_signature)) {
            die("Payment verification failed. Signatures do not match. Possible tampering.");
        }
    }

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
        
        for ($i = 0; $i < $repeat_days; $i++) {
            $current_delivery_date = $delivery_date_base;
            if ($status === 'Scheduled' && $delivery_date_base !== null) {
                // Add $i days to the base date
                $current_delivery_date = date('Y-m-d H:i:s', strtotime("$delivery_date_base +$i days"));
            }

            // Insert into orders table
            $insert_order = "INSERT INTO orders (user_id, seller_id, total_amount, status, payment_method, address, latitude, longitude, delivery_date, created_at, razorpay_order_id, razorpay_payment_id, razorpay_signature) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?)";
            $stmt_order = $conn->prepare($insert_order);
            if ($stmt_order) {
                $stmt_order->bind_param("iidsssddssss", $user_id, $seller_id, $total_amount, $status, $payment_method, $address, $lat, $lng, $current_delivery_date, $rzp_order_id, $rzp_payment_id, $rzp_signature);
                $stmt_order->execute();
                $order_id = $stmt_order->insert_id;
                $stmt_order->close();
                
                // Insert Order Items
                $insert_item = "INSERT INTO order_items (order_id, food_id, quantity, price) VALUES (?, ?, ?, ?)";
                $stmt_item = $conn->prepare($insert_item);
                
                foreach ($order_data['items'] as $item) {
                    // Insert order item
                    $stmt_item->bind_param("iiid", $order_id, $item['food_id'], $item['quantity'], $item['price']);
                    $stmt_item->execute();
                    
                    // Deduct stock
                    $deduct_sql = "UPDATE foods SET stock = stock - ? WHERE id = ? AND stock >= ?";
                    $stmt_deduct = $conn->prepare($deduct_sql);
                    $stmt_deduct->bind_param("iii", $item['quantity'], $item['food_id'], $item['quantity']);
                    $stmt_deduct->execute();
                    $stmt_deduct->close();
                }
                $stmt_item->close();
            }
        }
    }
    
    // 4. Clear Cart
    $clear_cart = "DELETE FROM cart WHERE user_id = ?";
    $stmt_clear = $conn->prepare($clear_cart);
    $stmt_clear->bind_param("i", $user_id);
    $stmt_clear->execute();
    $stmt_clear->close();
    
    // Notify Sellers about their new orders
    $customer_name_stmt = $conn->prepare("SELECT name FROM users WHERE user_id = ?");
    $customer_name_stmt->bind_param("i", $user_id);
    $customer_name_stmt->execute();
    $c_result = $customer_name_stmt->get_result();
    $c_name = $c_result->fetch_assoc()['name'] ?? 'A customer';
    $customer_name_stmt->close();
    
    foreach ($orders_by_seller as $seller_id => $order_data) {
        $title = "New Order Received!";
        $message = "You have a new " . $status . " order perfectly placed by " . htmlspecialchars($c_name) . "!";
        send_notification($conn, $seller_id, $title, $message, "success");
    }

    // 5. Redirect to Confirmation
    header("Location: order_confirmation.php");
    exit();
}

header("Location: customer_cart.php");
exit();
?>
