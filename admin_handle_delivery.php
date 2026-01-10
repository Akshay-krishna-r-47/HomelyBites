<?php
session_start();
include 'db_connect.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'];
    $application_id = intval($_POST['application_id']);
    $applicant_user_id = intval($_POST['applicant_user_id']);

    if ($action === 'approve') {
        // Start Transaction
        $conn->begin_transaction();
        try {
            // 1. Update Application Status
            $update_app = $conn->prepare("UPDATE delivery_applications SET status = 'Approved' WHERE id = ?");
            $update_app->bind_param("i", $application_id);
            $update_app->execute();

            // 2. Update User Role
            $update_user = $conn->prepare("UPDATE users SET role = 'Delivery' WHERE user_id = ?");
            $update_user->bind_param("i", $applicant_user_id);
            $update_user->execute();
            
            $conn->commit();
            $_SESSION['message'] = "Delivery Partner Approved successfully! User role updated.";
            $_SESSION['message_type'] = "success";
            
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['message'] = "Error approving application: " . $e->getMessage();
            $_SESSION['message_type'] = "error";
        }
    } elseif ($action === 'reject') {
        $update_app = $conn->prepare("UPDATE delivery_applications SET status = 'Rejected' WHERE id = ?");
        $update_app->bind_param("i", $application_id);
        
        if ($update_app->execute()) {
             $_SESSION['message'] = "Application Rejected.";
             $_SESSION['message_type'] = "success"; // Or info/warning color
        } else {
             $_SESSION['message'] = "Error rejecting application.";
             $_SESSION['message_type'] = "error";
        }
    }
}

header("Location: admin_delivery_requests.php");
exit();
?>
