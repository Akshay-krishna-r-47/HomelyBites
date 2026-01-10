<?php
session_start();

// Prevent caching to ensure logout works effectively
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Redirect to login if not logged in as Customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Customer') {
    header("Location: login.php");
    exit();
}

$user_name = htmlspecialchars($_SESSION['name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Cart - Homely Bites</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lemon&family=Lato:wght@300;400;700&family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Shared CSS -->
    <style>
        /* Reuse the same CSS variables and structure as dashboard */
        /* You can move this to style.css later for better reuse */
        :root {
            --primary-color: #27ae60;
            --brand-green: #008000;
            --bg-body: #fdfbf7;
            --card-bg: #FFFFFF;
            --text-dark: #2c3e50;
            --text-muted: #7f8c8d;
            --sidebar-width: 280px;
            --sidebar-collapsed-width: 80px;
            --header-height: 80px;
            --border-radius: 16px;
            --border-light: 1px solid rgba(0,0,0,0.06);
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.04);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Lato', sans-serif; }
        body { background-color: var(--bg-body); color: var(--text-dark); display: flex; min-height: 100vh; }

        /* Import Sidebar Styles */
        @import url('assets/css/style.css'); /* Assuming some common styles exist, but defining critical ones below for safety */

        /* Copied critical Sidebar/Layout CSS to ensure it looks right immediately */
        .sidebar { width: var(--sidebar-width); background-color: var(--brand-green); color: #fff; position: sticky; top: 0; height: 100vh; display: flex; flex-direction: column; padding: 20px; z-index: 1000; flex-shrink: 0; transition: all 0.4s; overflow: hidden; white-space: nowrap; }
        .sidebar.collapsed { width: var(--sidebar-collapsed-width); padding: 20px 10px; }
        .sidebar-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 40px; min-height: 40px; }
        .sidebar-logo h1 { font-family: 'Lemon', serif; font-size: 1.4rem; color: #fff; }
        .sidebar.collapsed .sidebar-logo h1 { display: none; }
        .sidebar-toggle-btn { background: none; border: none; color: white; font-size: 1.3rem; cursor: pointer; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; border-radius: 8px; }
        .sidebar.collapsed .sidebar-header { justify-content: center; }
        .nav-links { list-style: none; flex: 1; display: flex; flex-direction: column; gap: 8px; }
        .nav-links a { text-decoration: none; color: rgba(255, 255, 255, 0.85); display: flex; align-items: center; padding: 14px 15px; border-radius: 12px; transition: all 0.3s; font-weight: 500; }
        .nav-links a i { min-width: 24px; text-align: center; font-size: 1.2rem; margin-right: 15px; }
        .sidebar.collapsed .nav-links a { justify-content: center; }
        .sidebar.collapsed .nav-links a i { margin-right: 0; font-size: 1.3rem; }
        .sidebar.collapsed .nav-links span { display: none; }
        .nav-links a:hover, .nav-links a.active { background-color: rgba(255, 255, 255, 0.15); color: #fff; }
        .logout-link { border-top: 1px solid rgba(255,255,255,0.2); padding-top: 20px; margin-top: 20px; }
        .logout-link a { color: #ffcccc; font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 10px;}
        .sidebar.collapsed .logout-link span { display: none; }
        .sidebar.collapsed .logout-link i { margin-right: 0; }
        
        .main-content { flex: 1; display: flex; flex-direction: column; width: 0; transition: all 0.4s; }
        header { height: var(--header-height); background-color: var(--card-bg); padding: 0 40px; display: flex; align-items: center; justify-content: flex-end; position: sticky; top: 0; z-index: 900; border-bottom: var(--border-light); }
        .content-container { padding: 40px 50px; width: 100%; max-width: 1600px; margin: 0 auto; }
        
        /* Placeholder Content Styles */
        .page-header h2 { font-family: 'Playfair Display', serif; font-size: 2rem; margin-bottom: 20px; }
        .empty-state { text-align: center; padding: 50px; background: #fff; border-radius: 16px; border: var(--border-light); }
        .empty-state i { font-size: 4rem; color: #eee; margin-bottom: 20px; }
        .empty-state p { color: var(--text-muted); font-size: 1.1rem; }

        @media (max-width: 768px) {
            .sidebar { width: 70px; padding: 10px 5px; position: fixed; left: 0; }
            .sidebar.collapsed { width: 70px; } /* Always mini on mobile */
            .sidebar-logo, .nav-links span { display: none; }
            .main-content { margin-left: 70px; width: calc(100% - 70px); }
            header { padding: 0 20px; }
        }
    </style>
</head>
<body>

    <?php include 'customer_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <header>
            <div class="user-profile">
                <!-- User Profile simple view -->
                <div style="text-align: right; margin-right: 15px;">
                    <p style="font-weight: 700;"><?php echo $user_name; ?></p>
                    <span style="font-size: 0.8rem; color: #888;">Customer</span>
                </div>
                <div style="width: 40px; height: 40px; background: #ddd; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                    <i class="fa-solid fa-user"></i>
                </div>
            </div>
        </header>

        <div class="content-container">
            <div class="page-header">
                <h2>My Cart</h2>
            </div>
            
            <div class="empty-state">
                <i class="fa-solid fa-cart-shopping"></i>
                <p>Your cart is currently empty.</p>
                <a href="customer_dashboard.php" style="display: inline-block; margin-top: 15px; color: var(--brand-green); font-weight: 700;">Browse Food</a>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('collapsed');
        }
    </script>
</body>
</html>
