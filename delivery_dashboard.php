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
            
            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fa-solid fa-box"></i></div>
                    <div class="stat-info">
                        <h3>0</h3>
                        <p>Active Orders</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fa-solid fa-check-double"></i></div>
                    <div class="stat-info">
                        <h3>0</h3>
                        <p>Completed</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fa-solid fa-indian-rupee-sign"></i></div>
                    <div class="stat-info">
                        <h3>0.00</h3>
                        <p>Total Earnings</p>
                    </div>
                </div>
            </div>

            <!-- Active Orders -->
            <div class="section-header">
                <h3 class="section-title">Current Assignments</h3>
            </div>

            <div class="empty-state">
                <i class="fa-solid fa-bicycle" style="font-size: 3rem; color: #ddd; margin-bottom: 15px;"></i>
                <p>No active delivery assignments. Check available orders!</p>
            </div>

        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('collapsed');
        }
    </script>
</body>
</html>
