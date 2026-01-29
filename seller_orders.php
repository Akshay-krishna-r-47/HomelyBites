<?php
include 'role_check.php';
check_role_access('seller');
include 'db_connect.php';

$seller_id = $_SESSION['user_id'];
$message = "";

// Handle Status Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['order_id']) && isset($_POST['status'])) {
    // In a multi-seller system, 'orders.status' is usually global.
    // If we want to support partial fulfillment, we need 'order_items.status'.
    // For this prompt, I will update the main order status but check if the seller is relevant.
    // LIMITATION: Changing global order status might affect other items if mixed.
    // Assumption: One order = One Seller OR Seller updates their part (but DB might not support item-level status).
    // Let's update the global status for now as per common simple implementation request.
    
    $order_id = intval($_POST['order_id']);
    $new_status = $_POST['status'];
    
    // Verify this order contains items from this seller
    $check_stmt = $conn->prepare("SELECT 1 FROM order_items oi JOIN foods f ON oi.food_id = f.id WHERE oi.order_id = ? AND f.seller_id = ?");
    $check_stmt->bind_param("ii", $order_id, $seller_id);
    $check_stmt->execute();
    if ($check_stmt->fetch()) {
        $check_stmt->close();
        
        $update_stmt = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
        $update_stmt->bind_param("si", $new_status, $order_id);
        if ($update_stmt->execute()) {
            $message = "Order #HB-" . (1000 + $order_id) . " status updated to " . $new_status;
        } else {
            $message = "Error updating status.";
        }
        $update_stmt->close();
    } else {
        $check_stmt->close();
        $message = "Unauthorized order access.";
    }
}

// Fetch Orders for this Seller
// Grouping by Order ID to show one row per order, with items concatenated
$sql = "
    SELECT 
        o.order_id, 
        o.created_at, 
        o.status, 
        o.total_amount, 
        u.name as customer_name,
        GROUP_CONCAT(CONCAT(f.name, ' (x', oi.quantity, ')') SEPARATOR ', ') as items_summary
    FROM orders o
    JOIN users u ON o.user_id = u.user_id
    JOIN order_items oi ON o.order_id = oi.order_id
    JOIN foods f ON oi.food_id = f.id
    WHERE f.seller_id = ?
    GROUP BY o.order_id
    ORDER BY o.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$result = $stmt->get_result();
$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Orders - Homely Bites</title>
    <link href="https://fonts.googleapis.com/css2?family=Lemon&family=Lato:wght@300;400;700&family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary-color: #27ae60; --brand-green: #008000; --bg-body: #fdfbf7; --card-bg: #FFFFFF; --text-dark: #2c3e50; --text-muted: #7f8c8d; --sidebar-width: 280px; --header-height: 80px; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Lato', sans-serif; }
        body { background-color: var(--bg-body); color: var(--text-dark); display: flex; min-height: 100vh; }
        .main-content { flex: 1; display: flex; flex-direction: column; width: 0; transition: all 0.4s; }
        header { height: var(--header-height); background-color: var(--card-bg); padding: 0 40px; display: flex; align-items: center; justify-content: flex-end; position: sticky; top: 0; z-index: 900; border-bottom: 1px solid rgba(0,0,0,0.06); }
        .content-container { padding: 40px 50px; width: 100%; max-width: 1600px; margin: 0 auto; }
        
        .page-header { margin-bottom: 30px; }
        .page-header h2 { font-family: 'Playfair Display', serif; font-size: 2.2rem; }
        
        .order-card { background: #fff; padding: 25px; border-radius: 16px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.05); }
        .order-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px; border-bottom: 1px solid #f5f5f5; padding-bottom: 15px; }
        .order-id { font-size: 1.1rem; font-weight: 700; color: var(--text-dark); }
        .order-date { font-size: 0.85rem; color: var(--text-muted); }
        .customer-info { font-weight: 600; color: #555; font-size: 0.95rem; }
        
        .order-items { margin-bottom: 20px; font-size: 0.95rem; line-height: 1.5; color: #444; }
        
        .order-footer { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .total-price { font-size: 1.1rem; font-weight: 700; color: var(--brand-green); }
        
        .status-actions { display: flex; gap: 10px; align-items: center; }
        .current-status { padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; margin-right: 10px; }
        .status-pending { background: #fff3e0; color: #f57c00; }
        .status-preparing { background: #e3f2fd; color: #1565c0; }
        .status-ready { background: #e1bee7; color: #7b1fa2; }
        .status-completed { background: #e8f5e9; color: #2e7d32; }
        
        .btn-update { padding: 8px 16px; border-radius: 6px; border: none; background: var(--primary-color); color: white; cursor: pointer; font-weight: 600; font-size: 0.9rem; }
        .btn-update:hover { background: #219150; }
        
        select.status-select { padding: 8px; border-radius: 6px; border: 1px solid #ddd; outline: none; margin-right: 10px; }
    </style>
</head>
<body>
    <?php include 'seller_sidebar.php'; ?>
    <div class="main-content">
        <header>
            <div style="text-align: right; margin-right: 15px;">
                <p style="font-weight: 700; margin-bottom: 2px;"><?php echo htmlspecialchars($_SESSION['name']); ?></p>
                <span style="font-size: 0.8rem; color: #888;">Seller Panel</span>
            </div>
            <div style="width: 40px; height: 40px; background: #ddd; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                <i class="fa-solid fa-store"></i>
            </div>
        </header>

        <div class="content-container">
            <?php if ($message): ?>
                <div style="padding: 15px; background: #d4edda; color: #155724; border-radius: 8px; margin-bottom: 20px;"><?php echo $message; ?></div>
            <?php endif; ?>

            <div class="page-header">
                <h2>Customer Orders</h2>
            </div>
            
            <?php if (count($orders) > 0): ?>
                <?php foreach($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <div class="order-id">Order #HB-<?php echo 1000 + $order['order_id']; ?></div>
                            <div class="order-date"><i class="fa-regular fa-clock"></i> <?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></div>
                        </div>
                        <div style="text-align: right;">
                            <div class="customer-info"><i class="fa-solid fa-user"></i> <?php echo htmlspecialchars($order['customer_name']); ?></div>
                        </div>
                    </div>
                    
                    <div class="order-items">
                        <strong>Items:</strong> <?php echo htmlspecialchars($order['items_summary']); ?>
                    </div>
                    
                    <div class="order-footer">
                        <div class="total-price">Total: ₹<?php echo number_format($order['total_amount'], 2); ?></div>
                        
                        <div class="status-actions">
                            <?php
                                $s = strtolower($order['status']);
                                $class = 'status-pending';
                                if(strpos($s, 'prepar') !== false) $class = 'status-preparing';
                                if(strpos($s, 'ready') !== false) $class = 'status-ready';
                                if(strpos($s, 'complet') !== false || strpos($s, 'deliver') !== false) $class = 'status-completed';
                            ?>
                            <span class="current-status <?php echo $class; ?>"><?php echo htmlspecialchars($order['status']); ?></span>
                            
                            <form method="POST" style="display: flex; align-items: center;">
                                <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                <select name="status" class="status-select">
                                    <option value="Preparing" <?php if($order['status']=='Preparing') echo 'selected'; ?>>Preparing</option>
                                    <option value="Ready" <?php if($order['status']=='Ready') echo 'selected'; ?>>Ready to Pickup</option>
                                    <option value="Completed" <?php if($order['status']=='Completed') echo 'selected'; ?>>Completed</option>
                                </select>
                                <button type="submit" class="btn-update">Update</button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align: center; color: #999; padding: 40px; font-style: italic;">No orders found yet.</div>
            <?php endif; ?>
        </div>
    </div>
    <script>function toggleSidebar(){document.querySelector('.sidebar').classList.toggle('collapsed');}</script>
</body>
</html>
