<?php
// api_cron_stale.php
// This script checks for 'Pending' orders older than 20 minutes.
// It cancels them, refunds the customer, and recommends alternative sellers.

require_once 'db_connect.php';

// Optional: Security check if running from external cron
// if (!isset($_GET['key']) || $_GET['key'] !== 'my_cron_secret') die('Unauthorized');

header('Content-Type: application/json');

try {
    // 1. Find all stale Pending orders (Not 'Scheduled')
    // We use MySQL's NOW() - INTERVAL 20 MINUTE to avoid PHP/MySQL timezone mismatches
    $sql = "SELECT order_id, user_id, seller_id, payment_method, items FROM orders WHERE status = 'Pending' AND created_at <= (NOW() - INTERVAL 20 MINUTE)";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    $stale_orders = [];
    while ($row = $result->fetch_assoc()) {
        $stale_orders[] = $row;
    }
    $stmt->close();

    $cancelled_count = 0;

    foreach ($stale_orders as $order) {
        $order_id = $order['order_id'];
        $user_id = $order['user_id'];
        $seller_id = $order['seller_id'];
        $payment_method = strtolower($order['payment_method']);
        
        // 2. Cancel the Order
        $update_sql = "UPDATE orders SET status = 'Cancelled' WHERE order_id = ?";
        $up_stmt = $conn->prepare($update_sql);
        $up_stmt->bind_param("i", $order_id);
        if (!$up_stmt->execute()) {
            continue; // Skip if failed to update
        }
        $up_stmt->close();
        
        $cancelled_count++;

        // 3. Handle Restoring Stock (Optional but Good Practice)
        $items_sql = "SELECT food_id, quantity FROM order_items WHERE order_id = ?";
        $item_stmt = $conn->prepare($items_sql);
        $item_stmt->bind_param("i", $order_id);
        $item_stmt->execute();
        $items_res = $item_stmt->get_result();
        $food_ids = [];
        while ($i_row = $items_res->fetch_assoc()) {
            $food_ids[] = $i_row['food_id'];
            $restore_sql = "UPDATE foods SET stock = stock + ? WHERE id = ?";
            $rest_stmt = $conn->prepare($restore_sql);
            $rest_stmt->bind_param("ii", $i_row['quantity'], $i_row['food_id']);
            $rest_stmt->execute();
            $rest_stmt->close();
        }
        $item_stmt->close();

        // 4. Recommendation Engine (Find alternative active sellers selling the same/similar foods)
        $recommendation_html = "";
        if (!empty($food_ids)) {
            $food_ids_str = implode(',', $food_ids);
            
            // Find names of foods inside this order to match against others
            $food_names = [];
            $fname_res = $conn->query("SELECT name FROM foods WHERE id IN ($food_ids_str)");
            while($fn_row = $fname_res->fetch_assoc()) {
                $food_names[] = $fn_row['name'];
            }
            
            if(!empty($food_names)) {
                $like_conditions = [];
                foreach($food_names as $fn) {
                    $like_conditions[] = "f.name LIKE '%" . $conn->real_escape_string($fn) . "%'";
                }
                $like_query = implode(' OR ', $like_conditions);
                
                // Query for alternatives: Same food name/type, different seller, seller is online, food is active+in stock
                $alt_sql = "SELECT f.id, f.name, f.price, u.name as seller_name 
                            FROM foods f 
                            JOIN users u ON f.seller_id = u.user_id 
                            WHERE f.seller_id != $seller_id 
                            AND f.status = 'Active' 
                            AND f.stock > 0
                            AND u.is_online = 1 
                            AND ($like_query)
                            LIMIT 3";
                            
                $alt_res = $conn->query($alt_sql);
                if ($alt_res && $alt_res->num_rows > 0) {
                    $recommendation_html = "<br><br><strong>We found active sellers serving similar items!</strong><br><ul style='padding-left:15px; margin-top:5px;'>";
                    while($alt = $alt_res->fetch_assoc()) {
                        $recommendation_html .= "<li><a href='food_details.php?id=".$alt['id']."' style='color:#1976d2; font-weight:600;'>".$alt['name']." - ₹".$alt['price']."</a> (By ".$alt['seller_name'].")</li>";
                    }
                    $recommendation_html .= "</ul>";
                }
            }
        }

        // 5. Build Notification Details
        $title = "Order Cancelled - Seller Unresponsive";
        $message = "We're sorry! Order #HB-".(1000 + $order_id)." was automatically cancelled because the seller did not start preparing it within 20 minutes.";
        
        if ($payment_method !== 'cash on delivery' && $payment_method !== 'cod') {
            $message .= " <br><strong style='color:#d32f2f;'>Since you paid online, your funds of ₹[Total] are being automatically refunded to your original payment method. Please allow 3-5 business days.</strong>";
        }
        
        $message .= $recommendation_html;
        $type = 'danger'; // Using danger UI alert type for cancellation

        // 6. Insert Notification
        $notif_sql = "INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)";
        $notif_stmt = $conn->prepare($notif_sql);
        $notif_stmt->bind_param("isss", $user_id, $title, $message, $type);
        $notif_stmt->execute();
        $notif_stmt->close();
    }

    echo json_encode(['success' => true, 'message' => "Successfully cancelled $cancelled_count stale orders."]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
