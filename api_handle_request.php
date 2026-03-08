<?php
session_start();
include 'db_connect.php';
include_once 'helpers.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $data = json_decode(file_get_contents('php://input'), true);
    
    $request_id = intval($data['request_id']);
    $action = $data['action']; // 'accept' or 'reject'
    
    // Verify request
    $sql = "SELECT order_id, status FROM delivery_requests WHERE request_id = ? AND delivery_partner_id = ? AND status = 'Pending' AND expires_at > NOW()";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $request_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $req = $result->fetch_assoc();
        $order_id = $req['order_id'];
        
        if ($action === 'accept') {
            // Update request status
            $updReq = $conn->prepare("UPDATE delivery_requests SET status = 'Accepted' WHERE request_id = ?");
            $updReq->bind_param("i", $request_id);
            $updReq->execute();
            
            // Assign order
            $updOrd = $conn->prepare("UPDATE orders SET delivery_partner_id = ?, status = 'Accepted by Delivery' WHERE order_id = ?");
            $updOrd->bind_param("ii", $user_id, $order_id);
            $updOrd->execute();
            
            // Update active orders count
            $updUser = $conn->prepare("UPDATE users SET active_orders = active_orders + 1 WHERE user_id = ?");
            $updUser->bind_param("i", $user_id);
            $updUser->execute();

            // Fetch info for notifications
            $info_sql = "SELECT o.user_id as customer_id, o.seller_id, d.name as driver_name 
                         FROM orders o 
                         JOIN users d ON d.user_id = ? 
                         WHERE o.order_id = ?";
            $info_stmt = $conn->prepare($info_sql);
            $info_stmt->bind_param("ii", $user_id, $order_id);
            $info_stmt->execute();
            $info = $info_stmt->get_result()->fetch_assoc();
            
            if ($info) {
                $driver_name_safe = htmlspecialchars($info['driver_name']);
                
                // Notify Customer
                send_notification($conn, $info['customer_id'], "Delivery Partner Assigned", "Driver $driver_name_safe has been assigned to Order #$order_id and will pick up your food soon.", "info");
                
                // Notify Seller
                send_notification($conn, $info['seller_id'], "Driver Assigned", "Driver $driver_name_safe has accepted the pickup for Order #$order_id.", "info");
            }

            echo json_encode(['success' => true, 'redirect' => 'delivery_active.php']);
        } elseif ($action === 'reject') {
            // Mark as rejected
            $updReq = $conn->prepare("UPDATE delivery_requests SET status = 'Rejected' WHERE request_id = ?");
            $updReq->bind_param("i", $request_id);
            $updReq->execute();
            
            // System should automatically re-run API internally, but we can return success
            // Option to trigger reassignment logic:
            // file_get_contents(SITE_URL . "/api_assign_driver.php?order_id=" . $order_id);
            echo json_encode(['success' => true]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Request expired or not found.']);
    }
}
?>
