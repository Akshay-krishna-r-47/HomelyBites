<?php
// api_cron_scheduled.php
// This script checks for 'Scheduled' orders that are due within the next 45 minutes
// and automatically moves them to 'Pending' so the seller gets notified to start preparing.

require_once 'db_connect.php';

// Prevent direct access from browser unless needed, but for simplicity we rely on CORS/session or just make it silent
// You can add a secret key check here if you run it from a real cron tab, e.g., ?key=SECRET
// if (!isset($_GET['key']) || $_GET['key'] !== 'my_cron_secret') { die('Unauthorized'); }

header('Content-Type: application/json');

try {
    // Current time
    $now = date('Y-m-d H:i:s');
    
    // Time 45 minutes from now
    $warning_time = date('Y-m-d H:i:s', strtotime('+45 minutes'));

    // Find orders that are 'Scheduled' and their delivery_date is between now and 45 minutes from now
    // We also check delivery_date <= warning_time and delivery_date >= now to avoid activating passed missed orders prematurely if they were somehow missed, 
    // although catching them is usually better. Let's just catch anything Scheduled with a date <= 45 mins from now.
    $sql = "UPDATE orders SET status = 'Pending' WHERE status = 'Scheduled' AND delivery_date IS NOT NULL AND delivery_date <= ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $warning_time);
    
    if ($stmt->execute()) {
        $affected_rows = $stmt->affected_rows;
        echo json_encode(['success' => true, 'message' => "Successfully activated $affected_rows scheduled orders."]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
