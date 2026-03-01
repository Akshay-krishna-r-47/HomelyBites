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

        // Validate Food Item and Get Stock
        $valid_check = $conn->prepare("SELECT id, stock FROM foods WHERE id = ? AND is_deleted = 0 AND status = 'Available'");
        $valid_check->bind_param("i", $food_id);
        $valid_check->execute();
        $food_res = $valid_check->get_result();
        
        if ($food_res->num_rows === 0) {
             // Item invalid or deleted
             header("Location: customer_dashboard.php?error=item_unavailable");
             exit();
        }
        
        $food_data = $food_res->fetch_assoc();
        $available_stock = (int)$food_data['stock'];
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
            
            // Check Stock limit
            if ($new_qty > $available_stock) {
                header("Location: customer_dashboard.php?error=stock_limit");
                exit();
            }
            
            $update_sql = "UPDATE cart SET quantity = ? WHERE id = ?";
            $stmt_up = $conn->prepare($update_sql);
            $stmt_up->bind_param("ii", $new_qty, $row['id']);
            $stmt_up->execute();
        } else {
            // Check Stock limit
            if ($quantity > $available_stock) {
                 header("Location: customer_dashboard.php?error=stock_limit");
                 exit();
            }
            
            // Insert new item
            $insert_sql = "INSERT INTO cart (user_id, food_id, quantity) VALUES (?, ?, ?)";
            $stmt_in = $conn->prepare($insert_sql);
            $stmt_in->bind_param("iii", $user_id, $food_id, $quantity);
            $stmt_in->execute();
        }

    } elseif ($action === 'update') {
        $cart_id = intval($_POST['cart_id']);
        $change = intval($_POST['change']);
        
        // Get current quantity and available stock
        $stmt = $conn->prepare("SELECT c.quantity, c.food_id, f.stock FROM cart c JOIN foods f ON c.food_id = f.id WHERE c.id = ? AND c.user_id = ?");
        $stmt->bind_param("ii", $cart_id, $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $new_qty = $row['quantity'] + $change;
            $available_stock = (int)$row['stock'];
            
            if ($new_qty <= 0) {
                // Remove item if quantity becomes 0 or less
                $stmt_del = $conn->prepare("DELETE FROM cart WHERE id = ?");
                $stmt_del->bind_param("i", $cart_id);
                $stmt_del->execute();
            } else {
                // Prevent exceeding stock
                if ($new_qty > $available_stock) {
                     $new_qty = $available_stock;
                     // Optional: Redirect with error, or silently max it out. We'll max it out.
                }
                
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
