<?php
session_start();
include_once 'role_check.php';
// We only allow customers to submit reviews on their orders
check_role_access('customer');
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $order_id = intval($_POST['order_id']);
    $user_id = $_SESSION['user_id'];
    
    // Validate order ownership and status
    $sql_check = "SELECT status FROM orders WHERE order_id = ? AND user_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ii", $order_id, $user_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if ($result_check->num_rows === 0) {
        // Order doesn't belong to layout or doesn't exist
        header("Location: customer_orders.php");
        exit();
    }
    
    $order_data = $result_check->fetch_assoc();
    $stmt_check->close();
    
    if ($order_data['status'] !== 'Delivered') {
        // Can only review completed items
        header("Location: order_details.php?id=$order_id&msg=not_delivered");
        exit();
    }
    
    // Process Seller Rating
    $s_rating = isset($_POST['seller_rating']) ? intval($_POST['seller_rating']) : null;
    $s_review = isset($_POST['seller_review']) ? trim($_POST['seller_review']) : "";
    
    // Process Delivery Rating
    $d_rating = isset($_POST['delivery_rating']) ? intval($_POST['delivery_rating']) : null;
    $d_review = isset($_POST['delivery_review']) ? trim($_POST['delivery_review']) : "";
    
    $update_order = "UPDATE orders SET seller_rating = ?, seller_review = ?, delivery_rating = ?, delivery_review = ? WHERE order_id = ?";
    $stmt_upd = $conn->prepare($update_order);
    $stmt_upd->bind_param("isisi", $s_rating, $s_review, $d_rating, $d_review, $order_id);
    $stmt_upd->execute();
    $stmt_upd->close();
    
    // Process Food Ratings (Arrays based on order_item_id or food_id)
    if (isset($_POST['food_ratings']) && is_array($_POST['food_ratings'])) {
        $update_item = "UPDATE order_items SET food_rating = ?, food_review = ? WHERE order_id = ? AND food_id = ?";
        $stmt_item = $conn->prepare($update_item);
        
        foreach ($_POST['food_ratings'] as $food_id => $rating_val) {
            $f_rating = intval($rating_val);
            $f_review_text = "";
            if (isset($_POST['food_reviews']) && is_array($_POST['food_reviews']) && isset($_POST['food_reviews'][$food_id])) {
                $f_review_text = trim($_POST['food_reviews'][$food_id]);
            }
            if($f_rating > 0) {
                $stmt_item->bind_param("isii", $f_rating, $f_review_text, $order_id, $food_id);
                $stmt_item->execute();
            }
        }
        $stmt_item->close();
    }
    
    // Add success message and redirect
    header("Location: order_details.php?id=$order_id&msg=review_submitted");
    exit();
} else {
    header("Location: customer_orders.php");
    exit();
}
?>
