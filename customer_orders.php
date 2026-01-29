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
include_once 'helpers.php';

$user_id = $_SESSION['user_id'];
$formatted_name = formatName($_SESSION['name']);
$user_name = htmlspecialchars($formatted_name);
$user_initials = getAvatarInitials($formatted_name);
$user_profile_image = getProfileImage($user_id, $conn);

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
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Lemon&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* SWIGGY-STYLE DESIGN SYSTEM */
        :root {
            --primary-color: #fc8019;
            --brand-green: #0a8f08;
            --bg-body: #f8f8f8;
            --text-dark: #222;
            --text-muted: #666;
            --card-bg: #FFFFFF;
            --header-height: 80px;
            --shadow-card: 0 4px 14px rgba(0,0,0,0.08);
            --shadow-hover: 0 8px 20px rgba(0,0,0,0.12);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: var(--bg-body); color: var(--text-dark); display: flex; min-height: 100vh; }
        
        /* Layout */
        .main-content { flex: 1; display: flex; flex-direction: column; width: 0; }
        
        /* Header */
        header {
            height: var(--header-height);
            background-color: var(--card-bg);
            padding: 0 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 900;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
        }

        .search-container {
            display: flex; align-items: center; background: #f1f1f1; border-radius: 12px; padding: 12px 20px; width: 400px; transition: 0.3s;
        }
        .search-container i { color: #888; margin-right: 12px; }
        .search-container input { border: none; background: transparent; outline: none; width: 100%; font-size: 0.95rem; font-weight: 500; color: var(--text-dark); }

        .user-info { display: flex; align-items: center; gap: 15px; text-align: right; }
        .profile-pic { width: 40px; height: 40px; background: #eee; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.9rem; color: #555; overflow: hidden; object-fit: cover; }
        .profile-pic img { width: 100%; height: 100%; object-fit: cover; }

        .content-container { padding: 40px 60px; max-width: 1400px; margin: 0 auto; width: 100%; }
        
        .page-header h2 { font-size: 28px; font-weight: 700; margin-bottom: 30px; color: #222; }
        
        /* Dashboard Rows */
        .dashboard-row { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; align-items: start; }
        .dashboard-box { background-color: var(--card-bg); padding: 30px; border-radius: 16px; box-shadow: var(--shadow-card); border: 1px solid rgba(0,0,0,0.03); }
        .box-title { font-size: 20px; font-weight: 700; margin-bottom: 25px; color: var(--text-dark); }

        table { width: 100%; border-collapse: separate; border-spacing: 0; }
        th { text-align: left; padding: 15px; color: var(--text-muted); font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #f0f0f0; }
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
        .delivery-details h4 { font-size: 15px; margin-bottom: 4px; color: var(--text-dark); font-weight: 700; }
        .delivery-details p { font-size: 13px; color: var(--text-muted); }

        .empty-state { text-align: center; padding: 20px; color: #999; font-style: italic; }
        
        @media (max-width: 1024px) { .dashboard-row { grid-template-columns: 1fr; } }
        @media (max-width: 768px) { header { padding: 0 20px; } .content-container { padding: 20px; } }
    </style>
</head>
<body>
    <?php include 'customer_sidebar.php'; ?>
    <div class="main-content">
        <header>
            <div class="search-container">
                <i class="fa-solid fa-search"></i>
                <input type="text" placeholder="Search for orders...">
            </div>

            <div class="user-info">
                <div>
                    <p style="font-weight: 700; font-size: 0.95rem; margin-bottom: 2px; color: var(--text-dark);"><?php echo $user_name; ?></p>
                    <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 500; letter-spacing: 0.5px; text-transform: uppercase;">Customer</span>
                </div>
                <div class="profile-pic">
                    <?php if($user_profile_image): ?>
                        <img src="<?php echo $user_profile_image; ?>" alt="Profile">
                    <?php else: ?>
                        <?php echo $user_initials; ?>
                    <?php endif; ?>
                </div>
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
