<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

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
    <title>My Reviews - Homely Bites</title>
    <link href="https://fonts.googleapis.com/css2?family=Lemon&family=Lato:wght@300;400;700&family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary-color: #27ae60; --brand-green: #008000; --bg-body: #fdfbf7; --card-bg: #FFFFFF; --text-dark: #2c3e50; --text-muted: #7f8c8d; --sidebar-width: 280px; --sidebar-collapsed-width: 80px; --header-height: 80px; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Lato', sans-serif; }
        body { background-color: var(--bg-body); color: var(--text-dark); display: flex; min-height: 100vh; }
        @import url('assets/css/style.css');
        /* Essential Sidebar/Layout Styles */
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
        header { height: var(--header-height); background-color: var(--card-bg); padding: 0 40px; display: flex; align-items: center; justify-content: flex-end; position: sticky; top: 0; z-index: 900; border-bottom: 1px solid rgba(0,0,0,0.06); }
        .content-container { padding: 40px 50px; width: 100%; max-width: 1600px; margin: 0 auto; }
        
        .page-header h2 { font-family: 'Playfair Display', serif; font-size: 2rem; margin-bottom: 20px; }
        .empty-state { text-align: center; padding: 50px; background: #fff; border-radius: 16px; border: 1px solid rgba(0,0,0,0.06); }
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
            <div class="page-header"><h2>My Reviews</h2></div>
            <div class="empty-state">
                <i class="fa-solid fa-star" style="font-size: 4rem; color: #eee; margin-bottom: 20px;"></i>
                <p style="color: #7f8c8d; font-size: 1.1rem;">You haven't posted any reviews yet.</p>
            </div>
        </div>
    </div>
    <script>function toggleSidebar(){document.querySelector('.sidebar').classList.toggle('collapsed');}</script>
</body>
</html>
