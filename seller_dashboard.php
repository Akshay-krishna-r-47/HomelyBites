<?php
session_start();
// ACCESS CONTROL: Strict check for Seller Approval
if (!isset($_SESSION['user_id']) || !isset($_SESSION['seller_approved']) || $_SESSION['seller_approved'] != 1) {
    header("Location: customer_dashboard.php");
    exit();
}

// We do NOT use role_check.php here because it enforces exclusive roles.
// Users are always 'Customer' in the database role column.

include 'db_connect.php';
include_once 'helpers.php'; 

$seller_id = $_SESSION['user_id'];
$formatted_name = formatName($_SESSION['name']);
$seller_name = htmlspecialchars($formatted_name);
$seller_initials = getAvatarInitials($formatted_name);

// 1. Total Menu Items
$stmt = $conn->prepare("SELECT COUNT(*) FROM foods WHERE seller_id = ?");
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$stmt->bind_result($total_menu_items);
$stmt->fetch();
$stmt->close();

// 2. Today's Orders (Assuming orders table has created_at)
// Since orders structure is complex, we will assume for now we count orders where this seller is involved
// For simplicity in this phase, if 'orders' doesn't link seller directly yet, we might need a join. 
// However, Plan assumed basic structure. Let's try to verify if 'orders' has seller_id or we use a simple placeholder logic 
// until 'seller_orders.php' logic establishes the link. 
// Wait, user provided requirement: "Display orders related ONLY to logged-in seller".
// I'll assume for now `orders` might have `seller_id` OR we use `foods` linkage.
// Let's check if we can rely on `foods` to find orders. 
// A robust way: SELECT COUNT(DISTINCT o.order_id) FROM orders o JOIN order_items oi ON o.order_id = oi.order_id JOIN foods f ON oi.food_id = f.id WHERE f.seller_id = ?
// But `order_items` structure isn't fully known. 
// Let's assume a simpler direct approach or just set to 0 if table structure is limiting, 
// BUT user want "Use real database queries". 
// I'll assume `order_items` has `food_id` or similar. I'll stick to a simpler count query if possible or just 0 for now to prevent errors if joining is risky without schema view.
// Let's use a safe placeholder query 0 and add a TODO comment if schema is unknown.
// actually, I'll attempt a standard query assuming a join path exists or create one.
// Let's just Count Foods for now as guaranteed. Earnings and Orders might need `seller_orders` logic first to be perfect.
// I'll implement standard counts.

// 2. Today's Orders
$todays_orders = 0;
// We define Today's Orders as any order placed today containing at least one item from this seller
$date_today = date('Y-m-d');
$sql_today = "SELECT COUNT(DISTINCT o.order_id) 
              FROM orders o
              JOIN order_items oi ON o.order_id = oi.order_id
              JOIN foods f ON oi.food_id = f.id
              WHERE f.seller_id = ? AND DATE(o.created_at) = ?";
$stmt = $conn->prepare($sql_today);
$stmt->bind_param("is", $seller_id, $date_today);
$stmt->execute();
$stmt->bind_result($todays_orders);
$stmt->fetch();
$stmt->close();

// 3. Pending Orders (Preparing, Out for Delivery - anything not Delivered or Cancelled)
$pending_orders = 0;
// Note: Adjust status checks based on exact DB values used. Assuming 'Delivered' and 'Cancelled' are final.
$sql_pending = "SELECT COUNT(DISTINCT o.order_id) 
                FROM orders o
                JOIN order_items oi ON o.order_id = oi.order_id
                JOIN foods f ON oi.food_id = f.id
                WHERE f.seller_id = ? AND o.status NOT IN ('Delivered', 'Cancelled', 'Completed')";
$stmt = $conn->prepare($sql_pending);
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$stmt->bind_result($pending_orders);
$stmt->fetch();
$stmt->close();

// 4. Total Earnings
$total_earnings = 0.00;
// Sum of total_amount for all non-cancelled orders for this seller.
// Limitation: If order has items from multiple sellers, total_amount is global. 
// Ideally we sum (price * quantity) from order_items. Let's do that for accuracy.
$sql_earnings = "SELECT SUM(oi.price * oi.quantity) 
                 FROM order_items oi
                 JOIN foods f ON oi.food_id = f.id
                 JOIN orders o ON oi.order_id = o.order_id
                 WHERE f.seller_id = ? AND o.status IN ('Delivered', 'Completed')";
$stmt = $conn->prepare($sql_earnings);
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$stmt->bind_result($total_earnings);
$stmt->fetch();
$stmt->close();
if($total_earnings === null) $total_earnings = 0.00;


// We will refine these queries once we build `seller_orders.php` and know exactly how to link them.
// For now, let's display the layout.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Dashboard - Homely Bites</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary-color: #27ae60; --brand-green: #008000; --bg-body: #fdfbf7; --card-bg: #FFFFFF; --text-dark: #2c3e50; --text-muted: #7f8c8d; --sidebar-width: 280px; --header-height: 80px; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: var(--bg-body); color: var(--text-dark); display: flex; min-height: 100vh; }
        
        .main-content { flex: 1; display: flex; flex-direction: column; width: 0; transition: all 0.4s; }
        header { height: var(--header-height); background-color: var(--card-bg); padding: 0 40px; display: flex; align-items: center; justify-content: flex-end; position: sticky; top: 0; z-index: 900; border-bottom: 1px solid rgba(0,0,0,0.06); }
        .content-container { padding: 40px 50px; width: 100%; max-width: 1600px; margin: 0 auto; }
        
        .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 30px; margin-top: 30px; }
        .card { background: var(--card-bg); padding: 30px; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.04); border: 1px solid rgba(0,0,0,0.06); display: flex; align-items: center; gap: 20px; transition: transform 0.3s; }
        .card:hover { transform: translateY(-5px); }
        .card-icon { width: 60px; height: 60px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; }
        .card-info h3 { font-size: 2rem; margin-bottom: 5px; color: var(--text-dark); }
        .card-info p { color: var(--text-muted); font-size: 0.9rem; font-weight: 600; }
        
        /* Specific Card Colors */
        .icon-menu { background: #e8f5e9; color: #2e7d32; }
        .icon-orders { background: #e3f2fd; color: #1565c0; }
        .icon-pending { background: #fff3e0; color: #ef6c00; }
        .icon-earnings { background: #f3e5f5; color: #7b1fa2; }

    </style>
</head>
<body>
    <?php include 'seller_sidebar.php'; ?>
    <div class="main-content">
        <header>
            <div style="text-align: right; margin-right: 15px;">
                <p style="font-weight: 700; margin-bottom: 2px;"><?php echo $seller_name; ?></p>
                <span style="font-size: 0.8rem; color: #888;">Seller Panel</span>
            </div>
            <div style="width: 40px; height: 40px; background: #ddd; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; color: var(--text-dark);">
                <?php echo $seller_initials; ?>
            </div>
        </header>

        <div class="content-container">
            <h2 style="font-family: 'Poppins', sans-serif; font-size: 2.2rem; font-weight: 700;">Dashboard Overview</h2>
            <p style="color: var(--text-muted); margin-top: 5px;">Welcome back, here's what's happening with your store today.</p>

            <div class="dashboard-grid">
                <!-- Total Menu Items -->
                <div class="card">
                    <div class="card-icon icon-menu"><i class="fa-solid fa-utensils"></i></div>
                    <div class="card-info">
                        <h3><?php echo $total_menu_items; ?></h3>
                        <p>Menu Items</p>
                    </div>
                </div>

                <!-- Today's Orders -->
                <div class="card">
                    <div class="card-icon icon-orders"><i class="fa-solid fa-clipboard-list"></i></div>
                    <div class="card-info">
                        <h3><?php echo $todays_orders; ?></h3>
                        <p>Today's Orders</p>
                    </div>
                </div>

                <!-- Pending Orders -->
                <div class="card">
                    <div class="card-icon icon-pending"><i class="fa-solid fa-clock"></i></div>
                    <div class="card-info">
                        <h3><?php echo $pending_orders; ?></h3>
                        <p>Pending Orders</p>
                    </div>
                </div>

                <!-- Total Earnings -->
                <div class="card">
                    <div class="card-icon icon-earnings"><i class="fa-solid fa-indian-rupee-sign"></i></div>
                    <div class="card-info">
                        <h3>₹<?php echo number_format($total_earnings, 2); ?></h3>
                        <p>Total Earnings</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>function toggleSidebar(){document.querySelector('.sidebar').classList.toggle('collapsed');}</script>
</body>
</html>
