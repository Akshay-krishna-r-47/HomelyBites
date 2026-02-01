<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $user_id = $_SESSION['user_id'];

    if ($action === 'add') {
        $food_id = intval($_POST['food_id']);
        $quantity = 1; 

        // Validate Food Item (Ensure not deleted and is available)
        $valid_check = $conn->prepare("SELECT id FROM foods WHERE id = ? AND is_deleted = 0 AND status = 'Available'");
        $valid_check->bind_param("i", $food_id);
        $valid_check->execute();
        if ($valid_check->get_result()->num_rows === 0) {
             // Item invalid or deleted
             header("Location: customer_dashboard.php?error=item_unavailable");
             exit();
        }
        $valid_check->close();

        // Check if item already in cart
        $check_sql = "SELECT id, quantity FROM cart WHERE user_id = ? AND food_id = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("ii", $user_id, $food_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Update quantity
            $row = $result->fetch_assoc();
            $new_qty = $row['quantity'] + $quantity;
            $update_sql = "UPDATE cart SET quantity = ? WHERE id = ?";
            $stmt_up = $conn->prepare($update_sql);
            $stmt_up->bind_param("ii", $new_qty, $row['id']);
            $stmt_up->execute();
        } else {
            // Insert new item
            $insert_sql = "INSERT INTO cart (user_id, food_id, quantity) VALUES (?, ?, ?)";
            $stmt_in = $conn->prepare($insert_sql);
            $stmt_in->bind_param("iii", $user_id, $food_id, $quantity);
            $stmt_in->execute();
        }

    } elseif ($action === 'update') {
        $cart_id = intval($_POST['cart_id']);
        $change = intval($_POST['change']);
        
        // Get current quantity
        $stmt = $conn->prepare("SELECT quantity FROM cart WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $cart_id, $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $new_qty = $row['quantity'] + $change;
            
            if ($new_qty <= 0) {
                // Remove item if quantity becomes 0 or less
                $stmt_del = $conn->prepare("DELETE FROM cart WHERE id = ?");
                $stmt_del->bind_param("i", $cart_id);
                $stmt_del->execute();
            } else {
                // Update quantity
                $stmt_up = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
                $stmt_up->bind_param("ii", $new_qty, $cart_id);
                $stmt_up->execute();
            }
        }
    } elseif ($action === 'remove') {
        $cart_id = intval($_POST['cart_id']);
        $stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $cart_id, $user_id);
        $stmt->execute();
    }
    
    // Redirect back to the page they came from (Referrer)
    $redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'customer_cart.php';
    header("Location: " . $redirect_url);
    exit();
}

header("Location: customer_dashboard.php");
exit();
?>
