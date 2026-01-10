<?php
// admin_handle_seller.php
session_start();
include 'db_connect.php';

// Check Admin Access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    die("Unauthorized access");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $application_id = intval($_POST['application_id']);
    $action = $_POST['action'];
    $applicant_user_id = intval($_POST['applicant_user_id']); // We need this to update users table

    if ($action === 'approve') {
        // Start Transaction
        $conn->begin_transaction();
        try {
             // 1. Update Application Status
            $stmt1 = $conn->prepare("UPDATE seller_applications SET status = 'Approved' WHERE application_id = ?");
            $stmt1->bind_param("i", $application_id);
            $stmt1->execute();
            $stmt1->close();

            // 2. Update User Role to Seller
            $stmt2 = $conn->prepare("UPDATE users SET role = 'Seller' WHERE user_id = ?");
            $stmt2->bind_param("i", $applicant_user_id);
            $stmt2->execute();
            $stmt2->close();

            // Commit
            $conn->commit();
            $_SESSION['message'] = "Seller approved successfully!";
            $_SESSION['message_type'] = "success";

        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['message'] = "Error approving seller: " . $e->getMessage();
            $_SESSION['message_type'] = "error";
        }
    } elseif ($action === 'reject') {
        // Just update status
        $stmt = $conn->prepare("UPDATE seller_applications SET status = 'Rejected' WHERE application_id = ?");
        $stmt->bind_param("i", $application_id);
        if ($stmt->execute()) {
             $_SESSION['message'] = "Seller application rejected.";
             $_SESSION['message_type'] = "success"; // Or info
        } else {
             $_SESSION['message'] = "Error rejecting application.";
             $_SESSION['message_type'] = "error";
        }
        $stmt->close();
    }
    
    // Redirect back to requests page
    header("Location: admin_requests.php");
    exit();
}
?>
