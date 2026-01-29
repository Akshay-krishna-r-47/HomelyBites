<?php
// role_check.php: Centralized session and role validation logic
include_once 'helpers.php';

/**
 * Validates the user's role and redirects if invalid.
 * Uses lowercase role names: 'customer', 'seller', 'delivery', 'admin'.
 *
 * @param string $required_role The role required for this page (e.g., 'customer').
 */
function check_role_access($required_role) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // 0. Cache Control to prevent back-button access
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
        // Use require to ensure file exists, use include_once logic manually if needed 
        // or just rely on require returning the vars if not function scoped (but db_connect is global code).
        // Best approach: just require it, db_connect.php usually connects immediately.
        require 'db_connect.php'; 
    }

    // Admin Exception: Admins are not stored in the DB (based on legacy logic)
    // If the session says Admin, we trust it for now, but prevent them from accessing other dashboards
    // Lowercase comparison for consistency
    if (isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin') {
        if ($required_role !== 'admin') {
            header("Location: admin_dashboard.php");
            exit();
        }
        return; // Access Granted for Admin
    }

    // 3. Re-check Role from Database (Session Refresh Safety)
    $user_id = $_SESSION['user_id'];
    
    // Check if $conn is valid object now
    if ($conn instanceof mysqli) {
        $stmt = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->bind_result($db_role);
            if ($stmt->fetch()) {
                // Determine current role (normalize to lowercase)
                $current_role = strtolower($db_role);
                $session_role = isset($_SESSION['role']) ? strtolower($_SESSION['role']) : '';

                // 4. Update Session if Role Changed
                if ($current_role !== $session_role) {
                     $_SESSION['role'] = $db_role; // Keep original casing in session for display if needed
                }

                // 5. Access Control / Redirection
                // Check if current role matches the required role for this page
                if ($current_role !== $required_role) {
                    $redirect = "login.php"; // Default fallback
                    
                    switch ($current_role) {
                        case 'customer':
                            $redirect = "customer_dashboard.php";
                            break;
                        case 'seller':
                            $redirect = "seller_dashboard.php";
                            break;
                        case 'delivery':
                            $redirect = "delivery_dashboard.php";
                            break;
                        case 'admin':
                            $redirect = "admin_dashboard.php";
                            break;
                    }
                    
                    header("Location: " . $redirect);
                    exit();
                }
            } else {
                // User not found in DB? Logout.
                header("Location: logout.php");
                exit();
            }
            $stmt->close();
        } else {
            // Statement preparation failed
            die("Error preparing statement: " . $conn->error);
        }
    } else {
        die("Database connection invalid.");
    }
    
    // Role Matches -> Access Granted
}
?>
