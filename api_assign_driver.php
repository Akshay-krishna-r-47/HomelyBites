<?php
// This API handles finding an online driver and assigning them a ping.
// Called internally when seller clicks "Ready for Pickup", or when a previous driver rejects.

include_once 'db_connect.php';

function triggerDriverAssignment($conn, $order_id) {
    // 1. Find all online drivers who haven't rejected or timed out on this order
    // Order by active_orders ASC to balance load
    $sql = "SELECT u.user_id 
            FROM users u
            WHERE u.role = 'Delivery' 
            AND u.is_online = 1 
            AND u.status = 'Active'
            AND u.user_id NOT IN (
                SELECT delivery_partner_id FROM delivery_requests 
                WHERE order_id = ? AND status IN ('Rejected', 'Timeout', 'Accepted')
            )
            ORDER BY u.active_orders ASC, RAND() LIMIT 1";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $driver = $result->fetch_assoc();
        $target_driver_id = $driver['user_id'];
        
        // 2. Create the Delivery Request Ping (expires in 20 seconds)
        // Set the status of the order so we know it's queued
        // The popup timer handles the countdown.
        
        $insert = $conn->prepare("INSERT INTO delivery_requests (order_id, delivery_partner_id, status, created_at, expires_at) 
                                  VALUES (?, ?, 'Pending', NOW(), DATE_ADD(NOW(), INTERVAL 20 SECOND))");
        $insert->bind_param("ii", $order_id, $target_driver_id);
        if($insert->execute()) {
            return true;
        }
    } else {
        // No drivers available right now. 
        // Order remains in purely 'Ready for Pickup' state without assignment ping.
        return false;
    }
}

// Can be called via HTTP GET for manual trigger, mostly internally included
if (isset($_GET['order_id'])) {
    $res = triggerDriverAssignment($conn, intval($_GET['order_id']));
    if(!headers_sent()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => $res]);
        exit;
    }
}
?>
