<?php
session_start();
include 'db_connect.php';
include_once 'helpers.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$action = isset($_POST['action']) ? $_POST['action'] : '';

// Function to deactivate all food items for a user
function deactivateFoods($conn, $user_id) {
    $stmt = $conn->prepare("UPDATE foods SET status = 'Unavailable' WHERE seller_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
    }
}

if ($action === 'delete_customer') {
    // Soft delete the entire user account
    $stmt = $conn->prepare("UPDATE users SET status = 'Deleted' WHERE user_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            // Deactivate all food items they might have as a seller
            deactivateFoods($conn, $user_id);
            
            // Deactivate seller and delivery applications just to be safe (optional but good for consistency)
            $conn->query("UPDATE seller_applications SET status = 'Deactivated' WHERE user_id = " . intval($user_id));
            $conn->query("UPDATE delivery_applications SET status = 'Deactivated' WHERE user_id = " . intval($user_id));
            
            log_activity($conn, $user_id, 'Account Deleted', 'User deleted their customer account and all associated profiles.');

            // Log out the user completely
            session_destroy();
            header("Location: login.php?msg=account_deleted");
            exit();
        }
        $stmt->close();
    }
} elseif ($action === 'deactivate_seller') {
    // Only deactivate the seller account
    $stmt = $conn->prepare("UPDATE seller_applications SET status = 'Deactivated' WHERE user_id = ? AND status = 'Approved'");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            // Also update the users table to revoke access
            $user_upd = $conn->prepare("UPDATE users SET seller_approved = 0 WHERE user_id = ?");
            if ($user_upd) {
                $user_upd->bind_param("i", $user_id);
                $user_upd->execute();
                $user_upd->close();
            }

            // Set their foods to Unavailable
            deactivateFoods($conn, $user_id);
            
            // Update session so they no longer have seller access during this session
            $_SESSION['seller_approved'] = 0;
            
            log_activity($conn, $user_id, 'Seller Deactivated', 'User manually deactivated their seller account.');

            // Redirect to customer dashboard with success message
            $_SESSION['profile_msg'] = "Your Seller account has been deactivated successfully.";
            header("Location: customer_dashboard.php");
            exit();
        }
        $stmt->close();
    }
} elseif ($action === 'deactivate_delivery') {
    // Only deactivate the delivery account
    $stmt = $conn->prepare("UPDATE delivery_applications SET status = 'Deactivated' WHERE user_id = ? AND status = 'Approved'");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            // Also update the users table to revoke access
            $user_upd = $conn->prepare("UPDATE users SET delivery_approved = 0 WHERE user_id = ?");
            if ($user_upd) {
                $user_upd->bind_param("i", $user_id);
                $user_upd->execute();
                $user_upd->close();
            }

            // Update session so they no longer have delivery access
            $_SESSION['delivery_approved'] = 0;
            
            log_activity($conn, $user_id, 'Delivery Deactivated', 'User manually deactivated their delivery account.');

            // Redirect to customer dashboard with success message
            $_SESSION['profile_msg'] = "Your Delivery account has been deactivated successfully.";
            header("Location: customer_dashboard.php");
            exit();
        }
        $stmt->close();
    }
} elseif ($action === 'reactivate_seller') {
    // Reactivate the seller account
    $stmt = $conn->prepare("UPDATE seller_applications SET status = 'Approved' WHERE user_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            // Restore users table access
            $user_upd = $conn->prepare("UPDATE users SET seller_approved = 1 WHERE user_id = ?");
            if ($user_upd) {
                $user_upd->bind_param("i", $user_id);
                $user_upd->execute();
                $user_upd->close();
            }

            // Restore food items
            $food_upd = $conn->prepare("UPDATE foods SET status = 'Available' WHERE seller_id = ?");
            if ($food_upd) {
                $food_upd->bind_param("i", $user_id);
                $food_upd->execute();
                $food_upd->close();
            }
            
            $_SESSION['seller_approved'] = 1;
            
            log_activity($conn, $user_id, 'Seller Reactivated', 'User reactivated their seller account from the sidebar.');

            $_SESSION['profile_msg'] = "Your Seller account has been reactivated successfully!";
            header("Location: seller_dashboard.php");
            exit();
        }
        $stmt->close();
    }
} elseif ($action === 'reactivate_delivery') {
    // Reactivate the delivery account
    $stmt = $conn->prepare("UPDATE delivery_applications SET status = 'Approved' WHERE user_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            // Restore users table access
            $user_upd = $conn->prepare("UPDATE users SET delivery_approved = 1 WHERE user_id = ?");
            if ($user_upd) {
                $user_upd->bind_param("i", $user_id);
                $user_upd->execute();
                $user_upd->close();
            }

            $_SESSION['delivery_approved'] = 1;
            
            log_activity($conn, $user_id, 'Delivery Reactivated', 'User reactivated their delivery account from the sidebar.');

            $_SESSION['profile_msg'] = "Your Delivery account has been reactivated successfully!";
            header("Location: delivery_dashboard.php");
            exit();
        }
        $stmt->close();
    }
}

// Fallback if action is unknown or fails
header("Location: customer_dashboard.php");
exit();
?>
