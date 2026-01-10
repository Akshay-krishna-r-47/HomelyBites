<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Customer') {
    header("Location: login.php");
    exit();
}
include 'db_connect.php';

$user_id = $_SESSION['user_id'];
$user_name = htmlspecialchars($_SESSION['name']);

// Fetch Recent Orders (excluding Scheduled)
$recent_orders = [];
$sql = "SELECT * FROM orders WHERE user_id = ? AND status != 'Scheduled' ORDER BY created_at DESC LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $recent_orders[] = $row;
}
$stmt->close();

// Fetch Upcoming Deliveries (Scheduled)
$upcoming_deliveries = [];
$sql = "SELECT * FROM orders WHERE user_id = ? AND status = 'Scheduled' ORDER BY delivery_date ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $upcoming_deliveries[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Homely Bites</title>
    <link href="https://fonts.googleapis.com/css2?family=Lemon&family=Lato:wght@300;400;700&family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary-color: #27ae60; --brand-green: #008000; --bg-body: #fdfbf7; --card-bg: #FFFFFF; --text-dark: #2c3e50; --text-muted: #7f8c8d; --sidebar-width: 280px; --sidebar-collapsed-width: 80px; --header-height: 80px; --border-radius: 16px; --border-light: 1px solid rgba(0,0,0,0.06); --shadow-sm: 0 2px 8px rgba(0,0,0,0.04); }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Lato', sans-serif; }
        body { background-color: var(--bg-body); color: var(--text-dark); display: flex; min-height: 100vh; }
        @import url('assets/css/style.css');
        /* Essential Sidebar/Layout Styles */
        .sidebar { width: var(--sidebar-width); background-color: var(--brand-green); color: #fff; position: sticky; top: 0; height: 100vh; display: flex; flex-direction: column; padding: 20px; z-index: 1000; flex-shrink: 0; transition: all 0.4s; overflow: hidden; white-space: nowrap; }
        .sidebar.collapsed { width: var(--sidebar-collapsed-width); padding: 20px 10px; }
        /* ... existing sidebar styles ... */
        
        .main-content { flex: 1; display: flex; flex-direction: column; width: 0; transition: all 0.4s; }
        header { height: var(--header-height); background-color: var(--card-bg); padding: 0 40px; display: flex; align-items: center; justify-content: flex-end; position: sticky; top: 0; z-index: 900; border-bottom: 1px solid rgba(0,0,0,0.06); }
        .content-container { padding: 40px 50px; width: 100%; max-width: 1600px; margin: 0 auto; }
        
        .page-header h2 { font-family: 'Playfair Display', serif; font-size: 2rem; margin-bottom: 20px; }
        
        /* Dashboard Rows Copied & Adapted */
        .dashboard-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            align-items: start;
        }

        .dashboard-box {
            background-color: var(--card-bg);
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
            border: var(--border-light);
            height: 100%;
        }

        .box-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.4rem;
            margin-bottom: 25px;
            color: var(--text-dark);
        }

        table { width: 100%; border-collapse: separate; border-spacing: 0; }
        th { text-align: left; padding: 15px; color: var(--text-muted); font-weight: 600; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #f0f0f0; }
        td { padding: 20px 15px; font-size: 0.95rem; border-bottom: 1px solid #f5f5f5; color: var(--text-dark); vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background-color: #fafafa; }

        .status { padding: 6px 14px; border-radius: 30px; font-size: 0.75rem; font-weight: 700; letter-spacing: 0.3px; text-transform: uppercase; white-space: nowrap; }
        .status.preparing { background: #fff3e0; color: #f57c00; }
        .status.out { background: #e3f2fd; color: #1976d2; }
        .status.delivered { background: #e8f5e9; color: #2e7d32; }
        .status.cancelled { background: #ffebee; color: #c62828; }

        .delivery-item { display: flex; align-items: center; gap: 18px; padding: 15px 0; border-bottom: 1px solid #f5f5f5; }
        .delivery-item:last-child { border-bottom: none; }
        .delivery-icon { width: 48px; height: 48px; border-radius: 12px; background-color: #f8f9fa; border: 1px solid #eee; display: flex; align-items: center; justify-content: center; color: var(--text-muted); font-size: 1.1rem; }
        .delivery-details h4 { font-size: 1rem; margin-bottom: 4px; color: var(--text-dark); font-weight: 700; }
        .delivery-details p { font-size: 0.85rem; color: var(--text-muted); }

        .empty-state { text-align: center; padding: 20px; color: #999; font-style: italic; }
        
        @media (max-width: 1024px) { .dashboard-row { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <?php include 'customer_sidebar.php'; ?>
    <div class="main-content">
        <header>
            <div style="text-align: right; margin-right: 15px;">
                <p style="font-weight: 700;"><?php echo $user_name; ?></p>
                <span style="font-size: 0.8rem; color: #888;">Customer</span>
            </div>
            <div style="width: 40px; height: 40px; background: #ddd; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                <i class="fa-solid fa-user"></i>
            </div>
        </header>
        <div class="content-container">
            <div class="page-header"><h2>My Orders</h2></div>
            
            <div class="dashboard-row">
                <!-- Recent Orders Table -->
                <div class="dashboard-box">
                    <h3 class="box-title">Recent Orders</h3>
                    <?php if (count($recent_orders) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Items</th>
                                <th>Status</th>
                                <th>Bill</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_orders as $order): ?>
                                <?php 
                                    $status_class = '';
                                    switch($order['status']) {
                                        case 'Preparing': $status_class = 'preparing'; break;
                                        case 'Out for Delivery': $status_class = 'out'; break;
                                        case 'Delivered': $status_class = 'delivered'; break;
                                        case 'Cancelled': $status_class = 'cancelled'; break;
                                    }
                                ?>
                            <tr>
                                <td>#HB-<?php echo 1000 + $order['order_id']; ?></td>
                                <td><?php echo htmlspecialchars($order['items']); ?></td>
                                <td><span class="status <?php echo $status_class; ?>"><?php echo $order['status']; ?></span></td>
                                <td>₹<?php echo number_format($order['total_amount']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                        <div class="empty-state">No recent orders found.</div>
                    <?php endif; ?>
                </div>

                <!-- Scheduled Deliveries -->
                <div class="dashboard-box">
                    <h3 class="box-title">Upcoming Deliveries</h3>
                    <?php if (count($upcoming_deliveries) > 0): ?>
                        <?php foreach ($upcoming_deliveries as $delivery): ?>
                        <div class="delivery-item">
                            <div class="delivery-icon"><i class="fa-solid fa-calendar"></i></div>
                            <div class="delivery-details">
                                <h4><?php echo htmlspecialchars($delivery['items']); ?></h4>
                                <p><?php echo date('M d, g:i A', strtotime($delivery['delivery_date'])); ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">No upcoming deliveries.</div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
    <script>function toggleSidebar(){document.querySelector('.sidebar').classList.toggle('collapsed');}</script>
</body>
</html>
