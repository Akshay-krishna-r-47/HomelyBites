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
    <title>Admin Dashboard - Homely Bites</title>
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
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            width: 0;
            transition: all 0.4s ease;
        }

        /* Top Header */
        header {
            height: var(--header-height);
            background-color: var(--card-bg);
            padding: 0 40px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            position: sticky;
            top: 0;
            z-index: 900;
            border-bottom: 1px solid rgba(0,0,0,0.06);
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .profile-pic {
            width: 42px;
            height: 42px;
            background: linear-gradient(135deg, var(--brand-green), #27ae60);
            border-radius: 50%;
            display: flex; /* Flexbox needed for centering */
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.1rem;
        }

        .content-container {
            padding: 40px 50px;
            max-width: 1600px;
            margin: 0 auto;
            width: 100%;
        }

        .welcome-section { margin-bottom: 40px; }
        .welcome-section h2 { font-family: 'Playfair Display', serif; font-size: 2.2rem; margin-bottom: 8px; }

        /* Dashboard Box */
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
            background: #fff3e0;
            color: #f39c12;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .btn-action {
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.2s;
            font-size: 0.85rem;
        }

        .btn-approve { background-color: #e8f5e9; color: #2e7d32; margin-right: 8px; }
        .btn-reject { background-color: #ffebee; color: #c62828; }
        
        .btn-action:hover { opacity: 0.8; }
        
        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .sidebar-logo, .nav-links span { display: none; }
            .main-content { margin-left: 0; }
        }
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
                <div class="profile-pic">
                    <i class="fa-solid fa-user-shield"></i>
                </div>
            </div>
        </header>

        <div class="content-container">
            <div class="welcome-section">
                <h2>Welcome, Admin!</h2>
                <p style="color: #7f8c8d;">Here is an overview of the platform status.</p>
            </div>

            <!-- Dashboard Summary -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px;">
                
                <!-- Pending Requests Card -->
                <div class="dashboard-box" style="text-align: center; padding: 40px;">
                    <div style="width: 80px; height: 80px; background-color: #ffebee; color: #c62828; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 2rem;">
                        <i class="fa-solid fa-store"></i>
                    </div>
                    <h3 style="font-size: 3rem; margin-bottom: 10px; color: #2c3e50;"><?php echo $pending_requests_count; ?></h3>
                    <p style="color: #7f8c8d; font-weight: 600;">Pending Seller Requests</p>
                    <a href="admin_requests.php" style="display: inline-block; margin-top: 20px; color: var(--primary-color); font-weight: 700; text-decoration: none;">View Requests &rarr;</a>
                </div>

                <!-- Pending Delivery Requests Card -->
                <div class="dashboard-box" style="text-align: center; padding: 40px;">
                    <div style="width: 80px; height: 80px; background-color: #fff3e0; color: #f39c12; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 2rem;">
                        <i class="fa-solid fa-truck"></i>
                    </div>
                    <?php
                        // Need to fetch count here if not using sidebar includes variable in scope? 
                        // Usually sidebar is included first, but dashboard content is after header. 
                        // Wait, sidebar is included at line 150. Variables from it might be available if scope allows.
                        // admin_sidebar maps variables in global scope of included file? Yes.
                        // But let's be safe and re-query or check if set. 
                        // Actually, dashboard code runs after sidebar include.
                        if (!isset($pending_delivery_count)) {
                            $del_req_count_sql = "SELECT COUNT(*) as count FROM delivery_applications WHERE status='Pending'";
                            $del_req_result = $conn->query($del_req_count_sql);
                            $pending_delivery_count = ($del_req_result && $row = $del_req_result->fetch_assoc()) ? $row['count'] : 0;
                        }
                    ?>
                    <h3 style="font-size: 3rem; margin-bottom: 10px; color: #2c3e50;"><?php echo $pending_delivery_count; ?></h3>
                    <p style="color: #7f8c8d; font-weight: 600;">Pending Delivery Requests</p>
                    <a href="admin_delivery_requests.php" style="display: inline-block; margin-top: 20px; color: var(--primary-color); font-weight: 700; text-decoration: none;">View Requests &rarr;</a>
                </div>

                <!-- Total Orders Card -->
                <div class="dashboard-box" style="text-align: center; padding: 40px;">
                    <div style="width: 80px; height: 80px; background-color: #e8f5e9; color: #2e7d32; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 2rem;">
                        <i class="fa-solid fa-clipboard-check"></i>
                    </div>
                    <h3 style="font-size: 3rem; margin-bottom: 10px; color: #2c3e50;"><?php echo $total_orders_count; ?></h3>
                    <p style="color: #7f8c8d; font-weight: 600;">Total Orders</p>
                    <a href="admin_orders.php" style="display: inline-block; margin-top: 20px; color: var(--primary-color); font-weight: 700; text-decoration: none;">View Orders &rarr;</a>
                </div>

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
