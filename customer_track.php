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
$formatted_name = formatName($_SESSION['name']);
$user_name = htmlspecialchars($formatted_name);
$user_initials = getAvatarInitials($formatted_name);
$user_profile_image = getProfileImage($_SESSION['user_id'], $conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Order - Homely Bites</title>
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
        
        .page-header h2 { font-size: 28px; font-weight: 700; margin-bottom: 20px; color: #222; }
        
        .empty-state { text-align: center; padding: 60px 20px; width: 100%; background: #fff; border-radius: 16px; box-shadow: var(--shadow-card); }
        .empty-state i { font-size: 4rem; color: #eee; margin-bottom: 20px; }
        .empty-state p { color: var(--text-muted); font-size: 1.1rem; margin-bottom: 20px; }
        .btn-browse { color: var(--brand-green); font-weight: 600; text-decoration: none; border: 1px solid var(--brand-green); padding: 10px 20px; border-radius: 8px; transition: 0.2s; }
        .btn-browse:hover { background: var(--brand-green); color: white; }

        @media (max-width: 768px) { header { padding: 0 20px; } .content-container { padding: 20px; } }
    </style>
</head>
<body>
    <?php include 'customer_sidebar.php'; ?>
    <div class="main-content">
        <header>
            <div class="search-container">
                <i class="fa-solid fa-search"></i>
                <input type="text" placeholder="Track order...">
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
            <div class="page-header"><h2>Track Your Order</h2></div>
            <div class="empty-state">
                <i class="fa-solid fa-map-location-dot" style="font-size: 4rem; color: #eee; margin-bottom: 20px;"></i>
                <p style="color: #7f8c8d; font-size: 1.1rem; margin-bottom: 20px;">You have no active orders to track.</p>
                <a href="customer_dashboard.php" class="btn-browse">Place an Order</a>
            </div>
        </div>
    </div>
    <script>function toggleSidebar(){document.querySelector('.sidebar').classList.toggle('collapsed');}</script>
</body>
</html>
