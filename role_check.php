<?php
// role_check.php: Centralized session and role validation logic
include_once 'helpers.php';

/**
 * Validates the user's role and redirects if invalid.
 * Supports additive roles (customer + seller + delivery).
 *
 * @param string $required_role The role required for this page ('customer', 'seller', 'delivery', 'admin').
 */
function check_role_access($required_role) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // 0. Cache Control
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");

    // 1. Basic Login Check
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }

    // 2. Ensure Database Connection
    global $conn;
    if (!isset($conn) || $conn === null) {
        require 'db_connect.php'; 
    }

    $user_id = $_SESSION['user_id'];
    
    // 3. Admin Exception (Legacy)
    if (isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin') {
        if ($required_role !== 'admin') {
            header("Location: admin_dashboard.php");
            exit();
        }
        return; // Admin access granted
    }

    // 4. Fetch User Access Flags
    // We re-fetch from DB to ensure security (session might be stale)
    if ($conn instanceof mysqli) {
        // Note: Using `role` col for Admin detection mainly, others use flags
        $stmt = $conn->prepare("SELECT role, seller_approved, delivery_approved FROM users WHERE user_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->bind_result($db_role, $seller_approved, $delivery_approved);
            
            if ($stmt->fetch()) {
                // Determine Access
                $access_granted = false;

                switch (strtolower($required_role)) {
                    case 'admin':
                        if (strtolower($db_role) === 'admin') $access_granted = true;
                        break;
                    case 'seller':
                        if ($seller_approved == 1) $access_granted = true;
                        break;
                    case 'delivery':
                        if ($delivery_approved == 1) $access_granted = true;
                        break;
                    case 'customer':
                        // All logged-in users are customers by default (unless admin)
                        if (strtolower($db_role) !== 'admin') $access_granted = true;
                        break;
                }

                if (!$access_granted) {
                    // Redirect Logic: Send them to their "highest" available dashboard or default customer
                    if (strtolower($db_role) === 'admin') {
                         header("Location: admin_dashboard.php");
                    } else {
                         header("Location: customer_dashboard.php");
                    }
                    exit();
                }

                // Sync Session (Optional helper)
                $_SESSION['seller_approved'] = $seller_approved;
                $_SESSION['delivery_approved'] = $delivery_approved;

            } else {
                header("Location: logout.php"); // User deleted
                exit();
            }
            $stmt->close();
        } else {
            die("Error preparing statement: " . $conn->error);
        }
    } else {
        die("Database connection invalid.");
    }
    
    // Access Granted
}
?>
