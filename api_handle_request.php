<?php
session_start();
include 'db_connect.php';

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
