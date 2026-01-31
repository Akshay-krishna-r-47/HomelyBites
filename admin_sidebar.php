<?php
// Get the current page filename
$current_page = basename($_SERVER['PHP_SELF']);
?>
<style>
    /* SIDEBAR STYLES - Centralized */
    :root {
        --primary-color: #27ae60;
        --brand-green: #008000;
        --sidebar-width: 280px;
        --sidebar-collapsed-width: 80px;
        --card-bg: #FFFFFF;
        --text-dark: #2c3e50;
        --text-muted: #7f8c8d;
        --bg-body: #fdfbf7;
    }

    /* Basic Resets included here for standalone safety */
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
    body { background-color: var(--bg-body); color: var(--text-dark); display: flex; min-height: 100vh; }

    .sidebar {
        width: var(--sidebar-width);
        background-color: var(--brand-green);
        color: #fff;
        position: sticky;
        top: 0;
        height: 100vh;
        display: flex;
        flex-direction: column;
        padding: 20px;
        z-index: 1000;
        flex-shrink: 0;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        overflow: hidden;
        white-space: nowrap;
        box-shadow: 4px 0 20px rgba(0,0,0,0.05);
    }

    .sidebar.collapsed {
        width: var(--sidebar-collapsed-width);
        padding: 20px 10px;
    }

    .sidebar-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 40px;
        min-height: 40px;
    }

    .sidebar-logo h1 {
        font-family: 'Lemon', serif;
        font-size: 1.4rem;
        color: #fff;
        margin: 0;
    }

    .sidebar.collapsed .sidebar-logo h1 { display: none; }

    .sidebar-toggle-btn {
        background: none;
        border: none;
        color: white;
        font-size: 1.3rem;
        cursor: pointer;
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
    }

    .sidebar-toggle-btn:hover { background-color: rgba(255,255,255,0.1); }
    .sidebar.collapsed .sidebar-header { justify-content: center; }

    .nav-links {
        list-style: none;
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 8px;
        padding: 0; 
        margin: 0;
    }

    .nav-links li { width: 100%; }

    .nav-links a {
        text-decoration: none;
        color: rgba(255, 255, 255, 0.85);
        display: flex;
        align-items: center;
        padding: 14px 15px;
        border-radius: 12px;
        transition: all 0.3s;
        font-weight: 500;
        font-size: 0.95rem;
    }

    .nav-links a i {
        min-width: 24px;
        text-align: center;
        font-size: 1.2rem;
        margin-right: 15px;
    }

    .sidebar.collapsed .nav-links a { justify-content: center; }
    .sidebar.collapsed .nav-links a i { margin-right: 0; font-size: 1.3rem; }
    .sidebar.collapsed .nav-links span { display: none; }

    .nav-links a:hover, .nav-links a.active {
        background-color: rgba(255, 255, 255, 0.15);
        color: #fff;
        font-weight: 700;
    }

    .logout-link {
        border-top: 1px solid rgba(255,255,255,0.2);
        padding-top: 20px;
        margin-top: 20px;
    }

    .logout-link a {
        color: #ffcccc;
        font-weight: 600;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 10px;
         padding: 14px 15px;
    }

    .logout-link a:hover {
        background-color: rgba(255, 59, 48, 0.1);
        color: #fff;
    }

    .sidebar.collapsed .logout-link a { justify-content: center; }

    /* Badge Styles */
    .badge {
        background-color: #e74c3c;
        color: white;
        font-size: 0.75rem;
        padding: 2px 8px;
        border-radius: 12px;
        margin-left: auto;
        font-weight: 700;
    }
    .sidebar.collapsed .badge { display: none; }
</style>
<?php
// Calculate counts
include_once 'db_connect.php'; 
// Check Pending Requests
$req_count_sql = "SELECT COUNT(*) as count FROM seller_applications WHERE status='Pending'";
$req_result = $conn->query($req_count_sql);
$pending_requests_count = ($req_result && $row = $req_result->fetch_assoc()) ? $row['count'] : 0;

// Check Total Orders
$order_count_sql = "SELECT COUNT(*) as count FROM orders";
$order_result = $conn->query($order_count_sql);
$total_orders_count = ($order_result && $row = $order_result->fetch_assoc()) ? $row['count'] : 0;
$total_orders_count = ($order_result && $row = $order_result->fetch_assoc()) ? $row['count'] : 0;

// Check Pending Delivery Requests
$del_req_count_sql = "SELECT COUNT(*) as count FROM delivery_applications WHERE status='Pending'";
$del_req_result = $conn->query($del_req_count_sql);
$pending_delivery_count = ($del_req_result && $row = $del_req_result->fetch_assoc()) ? $row['count'] : 0;
?>
<!-- Left Sidebar -->
<aside class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <h1>Homely Bites</h1>
        </div>
        <button class="sidebar-toggle-btn" onclick="toggleSidebar()">
            <i class="fa-solid fa-bars"></i>
        </button>
    </div>
    
    <ul class="nav-links">
        <li>
            <a href="admin_dashboard.php" class="<?php echo ($current_page == 'admin_dashboard.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-table-columns"></i> <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="admin_users.php" class="<?php echo ($current_page == 'admin_users.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-users"></i> <span>Manage Users</span>
            </a>
        </li>
        <li>
            <a href="admin_requests.php" class="<?php echo ($current_page == 'admin_requests.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-store"></i> <span>Seller Requests</span>
                <?php if ($pending_requests_count > 0): ?>
                    <span class="badge"><?php echo $pending_requests_count; ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li>
            <a href="admin_orders.php" class="<?php echo ($current_page == 'admin_orders.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-clipboard-list"></i> <span>Orders</span>
                <?php if ($total_orders_count > 0): ?>
                    <span class="badge"><?php echo $total_orders_count; ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li>
            <a href="admin_broadcast.php" class="<?php echo ($current_page == 'admin_broadcast.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-bullhorn"></i> <span>Broadcasts</span>
            </a>
        </li>
        <li>
            <a href="admin_settings.php" class="<?php echo ($current_page == 'admin_settings.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-gear"></i> <span>Settings</span>
            </a>
        </li>
        <li>
            <a href="admin_delivery_requests.php" class="<?php echo ($current_page == 'admin_delivery_requests.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-truck-fast"></i> <span>Delivery Requests</span>
                <?php if (isset($pending_delivery_count) && $pending_delivery_count > 0): ?>
                    <span class="badge" style="background-color: #f39c12;"><?php echo $pending_delivery_count; ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li>
            <a href="notifications.php?view=admin" class="<?php echo ($current_page == 'notifications.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-bell"></i> <span>Notifications</span>
            </a>
        </li>
    </ul>
    <div class="logout-link">
        <a href="#" onclick="location.replace('logout.php'); return false;">
            <i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span>
        </a>
    </div>
</aside>
