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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Homely Bites</title>
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
            height: 400px; /* Placeholder height */
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            color: var(--text-muted);
        }
        
        .dashboard-box i { font-size: 3rem; margin-bottom: 20px; color: #ddd; }
        
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
            <h2 style="font-family: 'Playfair Display', serif; font-size: 2.2rem; margin-bottom: 30px;">Manage Users</h2>
            
            <div class="dashboard-box">
                <i class="fa-solid fa-users-gear"></i>
                <h3>User Management Module</h3>
                <p>This feature is coming soon.</p>
            </div>
        </div>
    </div>
    <script>function toggleSidebar(){document.querySelector('.sidebar').classList.toggle('collapsed');}</script>
</body>
</html>
