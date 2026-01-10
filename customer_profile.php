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
$user_email = isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : 'Not Available';

// Fetch Stats
$total_orders = 0;
$active_orders = 0;
$scheduled_orders = 0;
$total_spent = 0;

// Total Orders
$stmt = $conn->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($total_orders);
$stmt->fetch();
$stmt->close();

// Active Orders (Preparing or Out for Delivery)
$stmt = $conn->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND status IN ('Preparing', 'Out for Delivery')");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($active_orders);
$stmt->fetch();
$stmt->close();

// Scheduled Orders
$stmt = $conn->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND status = 'Scheduled'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($scheduled_orders);
$stmt->fetch();
$stmt->close();

// Total Spent
$stmt = $conn->prepare("SELECT SUM(total_amount) FROM orders WHERE user_id = ? AND status = 'Delivered'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($total_spent);
$stmt->fetch();
$stmt->close();

if ($total_spent === null) $total_spent = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Homely Bites</title>
    <link href="https://fonts.googleapis.com/css2?family=Lemon&family=Lato:wght@300;400;700&family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary-color: #27ae60; --brand-green: #008000; --bg-body: #fdfbf7; --card-bg: #FFFFFF; --text-dark: #2c3e50; --text-muted: #7f8c8d; --sidebar-width: 280px; --sidebar-collapsed-width: 80px; --header-height: 80px; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Lato', sans-serif; }
        body { background-color: var(--bg-body); color: var(--text-dark); display: flex; min-height: 100vh; }
        @import url('assets/css/style.css');
        /* Essential Sidebar/Layout Styles are in included sidebar file or style.css, repeating minimal for safety */
        .sidebar { width: var(--sidebar-width); background-color: var(--brand-green); color: #fff; position: sticky; top: 0; height: 100vh; display: flex; flex-direction: column; padding: 20px; z-index: 1000; flex-shrink: 0; transition: all 0.4s; overflow: hidden; white-space: nowrap; }
        .sidebar.collapsed { width: var(--sidebar-collapsed-width); padding: 20px 10px; }
        /* ... existing sidebar styles ... */
        
        .main-content { flex: 1; display: flex; flex-direction: column; width: 0; transition: all 0.4s; }
        header { height: var(--header-height); background-color: var(--card-bg); padding: 0 40px; display: flex; align-items: center; justify-content: flex-end; position: sticky; top: 0; z-index: 900; border-bottom: 1px solid rgba(0,0,0,0.06); }
        .content-container { padding: 40px 50px; width: 100%; max-width: 1600px; margin: 0 auto; }
        
        .page-header h2 { font-family: 'Playfair Display', serif; font-size: 2rem; margin-bottom: 20px; }
        
        /* Stats Grid Copied from Dashboard */
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 25px;
            margin-bottom: 50px;
        }

        .summary-card {
            background-color: var(--card-bg);
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            border: 1px solid rgba(0,0,0,0.06);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: all 0.3s ease;
        }

        .summary-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 32px rgba(0,0,0,0.08);
        }

        .summary-icon {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            background-color: rgba(39, 174, 96, 0.08);
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
        }

        .summary-info h3 {
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 2px;
            color: var(--text-dark);
        }

        .summary-info p { color: var(--text-muted); font-size: 0.9rem; font-weight: 500; }

        .profile-card { background: #fff; padding: 30px; border-radius: 16px; border: 1px solid rgba(0,0,0,0.06); max-width: 800px; margin: 0 auto; }
        .profile-header { display: flex; align-items: center; gap: 20px; margin-bottom: 30px; }
        .profile-avatar { width: 80px; height: 80px; background: #eee; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: #888; }
        .profile-form .form-group { margin-bottom: 20px; }
        .profile-form label { display: block; margin-bottom: 8px; font-weight: 700; color: var(--text-dark); }
        .profile-form input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem; }
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
            <div class="page-header"><h2>My Overview</h2></div>
            
            <!-- Moved from Dashboard -->
            <div class="summary-grid">
                <div class="summary-card">
                    <div class="summary-icon"><i class="fa-solid fa-box"></i></div>
                    <div class="summary-info">
                        <h3><?php echo $total_orders; ?></h3>
                        <p>Total Orders</p>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="summary-icon"><i class="fa-solid fa-clock"></i></div>
                    <div class="summary-info">
                        <h3><?php echo $active_orders; ?></h3>
                        <p>Active Orders</p>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="summary-icon"><i class="fa-solid fa-calendar-days"></i></div>
                    <div class="summary-info">
                        <h3><?php echo $scheduled_orders; ?></h3>
                        <p>Scheduled</p>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="summary-icon"><i class="fa-solid fa-wallet"></i></div>
                    <div class="summary-info">
                        <h3>₹<?php echo number_format($total_spent); ?></h3>
                        <p>Total Spent</p>
                    </div>
                </div>
            </div>

            <div class="page-header"><h2>Profile Details</h2></div>
            <div class="profile-card">
                <div class="profile-header">
                    <div class="profile-avatar"><i class="fa-solid fa-user"></i></div>
                    <div>
                        <h3><?php echo $user_name; ?></h3>
                        <p style="color: #7f8c8d;">Customer</p>
                    </div>
                </div>
                <form class="profile-form">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" value="<?php echo $user_name; ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" value="<?php echo $user_email; ?>" disabled>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>function toggleSidebar(){document.querySelector('.sidebar').classList.toggle('collapsed');}</script>
</body>
</html>
