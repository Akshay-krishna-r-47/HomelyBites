<?php
session_start();
include_once 'helpers.php';
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// ACCESS CONTROL: Strict check for Delivery Approval
if (!isset($_SESSION['user_id']) || !isset($_SESSION['delivery_approved']) || $_SESSION['delivery_approved'] != 1) {
    header("Location: customer_dashboard.php");
    exit();
}
include 'db_connect.php';
$formatted_name = formatName($_SESSION['name']);
$user_name = htmlspecialchars($formatted_name);
$user_name = htmlspecialchars($formatted_name);
$user_initials = getAvatarInitials($formatted_name);
$profile_img = getProfileImage($_SESSION['user_id'], $conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Dashboard - Homely Bites</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Lemon&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* DASHBOARD STYLES */
        :root {
            --brand-green: #008000;
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

        .header-title h2 { font-size: 1.5rem; font-weight: 700; color: #333; }
        .header-title span { font-size: 0.9rem; color: #888; }

        .user-info { display: flex; align-items: center; gap: 15px; text-align: right; }
        .profile-pic { width: 40px; height: 40px; background: #eee; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.9rem; color: #555; overflow: hidden; }
        
        /* Content Layout */
        .content-container { padding: 40px 60px; max-width: 1400px; margin: 0 auto; width: 100%; }

        /* Stats Grid */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 24px; margin-bottom: 40px; }
        
        .stat-card {
            background: white;
            padding: 24px;
            border-radius: 16px;
            box-shadow: var(--shadow-card);
            border: 1px solid rgba(0,0,0,0.03);
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .stat-icon {
            width: 60px; height: 60px;
            border-radius: 12px;
            background: #e8f5e9;
            color: #27ae60;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.8rem;
        }

        .stat-info h3 { font-size: 2rem; font-weight: 700; color: #333; margin-bottom: 2px; }
        .stat-info p { color: #888; font-size: 0.9rem; font-weight: 500; }

        /* Available Orders Section */
        .section-header { margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .section-title { font-size: 1.4rem; font-weight: 700; color: #333; }
        
        .empty-state { text-align: center; padding: 60px 20px; background: white; border-radius: 16px; box-shadow: var(--shadow-card); }
        .empty-state p { font-size: 1.1rem; color: #888; margin-top: 15px; }

    </style>
</head>
<body>

    <?php include 'delivery_sidebar.php'; ?>

    <div class="main-content">
        <!-- Header -->
        <header>
            <div class="header-title">
                <h2>Overview</h2>
                <span>Welcome back, Delivery Partner!</span>
            </div>

            <div class="user-info">
                <div>
                    <p style="font-weight: 700; font-size: 0.95rem; margin-bottom: 2px; color: var(--text-dark);"><?php echo $user_name; ?></p>
                    <div style="display:flex; justify-content:end; align-items:center; gap:5px;">
                        <span style="font-size: 0.75rem; color: #fff; background-color: #27ae60; padding: 2px 6px; border-radius: 4px; font-weight: 600; text-transform: uppercase;">Delivery</span>
                    </div>
                </div>
                <div class="profile-pic">
                    <?php if ($profile_img): ?>
                        <img src="<?php echo $profile_img; ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        <?php echo $user_initials; ?>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <div class="content-container">
            
        <div class="content-container">
            <?php
            // Active Orders Count (Accepted or Out for delivery)
            $active_count = 0;
            $stmt1 = $conn->prepare("SELECT COUNT(order_id) as c FROM orders WHERE delivery_partner_id = ? AND status IN ('Accepted by Delivery', 'Out for Delivery')");
            $stmt1->bind_param("i", $_SESSION['user_id']);
            $stmt1->execute();
            $res1 = $stmt1->get_result();
            if ($res1->num_rows > 0) { $active_count = $res1->fetch_assoc()['c']; }
            
            // Completed Count
            $completed_count = 0;
            $stmt2 = $conn->prepare("SELECT COUNT(order_id) as c FROM orders WHERE delivery_partner_id = ? AND status = 'Delivered'");
            $stmt2->bind_param("i", $_SESSION['user_id']);
            $stmt2->execute();
            $res2 = $stmt2->get_result();
            if ($res2->num_rows > 0) { $completed_count = $res2->fetch_assoc()['c']; }
            
            // Total Earnings
            $total_earned = 0.00;
            $stmt3 = $conn->prepare("SELECT SUM(amount) as t FROM delivery_earnings WHERE delivery_partner_id = ?");
            $stmt3->bind_param("i", $_SESSION['user_id']);
            $stmt3->execute();
            $res3 = $stmt3->get_result();
            if ($res3->num_rows > 0) { 
                $row3 = $res3->fetch_assoc();
                if ($row3['t']) { $total_earned = $row3['t']; }
            }
            ?>
            
            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fa-solid fa-box"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $active_count; ?></h3>
                        <p>Active Orders</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fa-solid fa-check-double"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $completed_count; ?></h3>
                        <p>Completed</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fa-solid fa-indian-rupee-sign"></i></div>
                    <div class="stat-info">
                        <h3><?php echo number_format($total_earned, 2); ?></h3>
                        <p>Total Earnings</p>
                    </div>
                </div>
            </div>

            <!-- Active Orders Quick Link -->
            <div class="section-header">
                <h3 class="section-title">Current Assignments</h3>
            </div>

            <?php if ($active_count > 0): ?>
                <div class="empty-state" style="padding: 40px 20px;">
                    <i class="fa-solid fa-motorcycle" style="font-size: 3rem; color: var(--brand-green); margin-bottom: 15px;"></i>
                    <p style="color: #333; font-weight: 600;">You have <?php echo $active_count; ?> active delivery in progress.</p>
                    <a href="delivery_active.php" style="display: inline-block; margin-top: 15px; padding: 10px 25px; background: var(--brand-green); color: white; text-decoration: none; border-radius: 8px; font-weight: 600;">Track Active Deliveries</a>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fa-solid fa-bicycle" style="font-size: 3rem; color: #ddd; margin-bottom: 15px;"></i>
                    <p>No active delivery assignments. Check available orders!</p>
                    <a href="delivery_orders.php" style="display: inline-block; margin-top: 15px; padding: 10px 25px; background: #3b82f6; color: white; text-decoration: none; border-radius: 8px; font-weight: 600;">Find Orders</a>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('collapsed');
        }
    </script>
</body>
</html>
