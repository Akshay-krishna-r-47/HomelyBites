<?php
session_start();
// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}
$admin_name = htmlspecialchars($_SESSION['name']);
include_once 'db_connect.php';

// Fetch All Orders
// Using LEFT JOIN to ensure we get orders even if user was deleted (though ideally shouldn't happen)
$orders_sql = "SELECT o.*, u.name as user_name, u.email FROM orders o LEFT JOIN users u ON o.user_id = u.user_id ORDER BY o.order_id DESC";
$orders_result = $conn->query($orders_sql);
$orders_count = $orders_result ? $orders_result->num_rows : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Homely Bites</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Lemon&family=Lato:wght@300;400;700&family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #27ae60;
            --brand-green: #008000;
            --bg-body: #fdfbf7;
            --card-bg: #FFFFFF;
            --text-dark: #2c3e50;
            --text-muted: #7f8c8d;
            --header-height: 80px;
            --border-radius: 16px;
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.04);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Lato', sans-serif; }
        body { background-color: var(--bg-body); color: var(--text-dark); display: flex; min-height: 100vh; }
        
        /* Main Content */
        .main-content { flex: 1; display: flex; flex-direction: column; width: 0; transition: all 0.4s ease; }

        /* Top Header */
        header {
            height: var(--header-height);
            background-color: var(--card-bg);
            padding: 0 40px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            position: sticky; top: 0; z-index: 900;
            border-bottom: 1px solid rgba(0,0,0,0.06);
        }

        .admin-profile { display: flex; align-items: center; gap: 15px; }
        .profile-pic { width: 42px; height: 42px; background: linear-gradient(135deg, var(--brand-green), #27ae60); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.1rem; }

        .content-container { padding: 40px 50px; max-width: 1600px; margin: 0 auto; width: 100%; }

        .dashboard-box {
            background-color: var(--card-bg);
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
            border: 1px solid rgba(0,0,0,0.06);
        }
        
        .box-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.4rem;
            margin-bottom: 25px;
            color: var(--text-dark);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

         /* Table Styles */
        table { width: 100%; border-collapse: separate; border-spacing: 0; }
        th { text-align: left; padding: 15px; color: var(--text-muted); font-weight: 600; font-size: 0.8rem; text-transform: uppercase; border-bottom: 2px solid #f0f0f0; }
        td { padding: 20px 15px; font-size: 0.95rem; border-bottom: 1px solid #f5f5f5; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .status-delivered { background: #e8f5e9; color: #2e7d32; }
        .status-preparing { background: #fff3e0; color: #f39c12; }
        .status-scheduled { background: #e3f2fd; color: #1565c0; }
        .status-cancelled { background: #ffebee; color: #c62828; }

        .btn-action {
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            background-color: #f5f5f5; color: #333;
            font-size: 0.85rem;
        }
        .btn-action:hover { background-color: #e0e0e0; }

    </style>
</head>
<body>

    <?php include 'admin_sidebar.php'; ?>

    <div class="main-content">
        <header>
            <div class="admin-profile">
                <div style="text-align: right;">
                    <p style="font-weight: 700; font-size: 0.95rem; margin-bottom: 2px;"><?php echo $admin_name; ?></p>
                    <span style="font-size: 0.75rem; color: #7f8c8d; font-weight: 500; text-transform: uppercase;">Administrator</span>
                </div>
                <div class="profile-pic"><i class="fa-solid fa-user-shield"></i></div>
            </div>
        </header>

        <div class="content-container">
            <h2 style="font-family: 'Playfair Display', serif; font-size: 2.2rem; margin-bottom: 30px;">Manage Orders</h2>

            <div class="dashboard-box">
                <h3 class="box-title">
                    All Orders
                    <span style="font-size: 0.9rem; background: var(--primary-color); color: white; padding: 2px 8px; border-radius: 4px;"><?php echo $orders_count; ?> Total</span>
                </h3>
                
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($orders_count > 0): ?>
                            <?php while($row = $orders_result->fetch_assoc()): ?>
                            <?php
                                $status_class = 'status-preparing'; // Default
                                $s = strtolower($row['status']);
                                if (strpos($s, 'delivered') !== false) $status_class = 'status-delivered';
                                elseif (strpos($s, 'scheduled') !== false) $status_class = 'status-scheduled';
                                elseif (strpos($s, 'cancelled') !== false) $status_class = 'status-cancelled';
                            ?>
                            <tr>
                                <td><strong>#<?php echo $row['order_id']; ?></strong></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['user_name']); ?></strong><br>
                                    <span style="font-size: 0.8rem; color: #888;"><?php echo htmlspecialchars($row['email']); ?></span>
                                </td>
                                <td>$<?php echo number_format($row['total_amount'], 2); ?></td>
                                <td><span class="status-badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
                                <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align:center; color: #999; padding: 40px;">No orders found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script>function toggleSidebar(){document.querySelector('.sidebar').classList.toggle('collapsed');}</script>
</body>
</html>
