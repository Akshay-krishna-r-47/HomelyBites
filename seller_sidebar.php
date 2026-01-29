<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<style>
    :root {
        --primary-color: #27ae60;
        --brand-green: #008000;
        --sidebar-width: 280px;
        --sidebar-collapsed-width: 80px;
        --text-color: #fff;
    }

    .sidebar {
        width: var(--sidebar-width);
        background-color: var(--brand-green);
        color: var(--text-color);
        position: sticky;
        top: 0;
        height: 100vh;
        display: flex;
        flex-direction: column;
        padding: 20px;
        z-index: 1000;
        flex-shrink: 0;
        transition: all 0.4s;
        overflow: hidden;
        white-space: nowrap;
    }

    .sidebar.collapsed { width: var(--sidebar-collapsed-width); padding: 20px 10px; }

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
    
    .sidebar.collapsed .sidebar-header { justify-content: center; }

    .nav-links {
        list-style: none;
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .nav-links a {
        text-decoration: none;
        color: rgba(255, 255, 255, 0.85);
        display: flex;
        align-items: center;
        padding: 14px 15px;
        border-radius: 12px;
        transition: all 0.3s;
        font-weight: 500;
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
    
    .sidebar.collapsed .logout-link span { display: none; }
    .sidebar.collapsed .logout-link i { margin-right: 0; }
    .sidebar.collapsed .logout-link a { justify-content: center; }
</style>

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
            <a href="seller_dashboard.php" class="<?php echo ($current_page == 'seller_dashboard.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-table-columns"></i> <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="seller_menu.php" class="<?php echo ($current_page == 'seller_menu.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-utensils"></i> <span>Manage Menu</span>
            </a>
        </li>
        <li>
            <a href="seller_orders.php" class="<?php echo ($current_page == 'seller_orders.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-bell-concierge"></i> <span>Orders</span>
            </a>
        </li>
        <li>
            <a href="seller_earnings.php" class="<?php echo ($current_page == 'seller_earnings.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-wallet"></i> <span>Earnings</span>
            </a>
        </li>
        <li>
            <a href="seller_profile.php" class="<?php echo ($current_page == 'seller_profile.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-store"></i> <span>Profile</span>
            </a>
        </li>
    </ul>

    <div class="logout-link">
        <a href="#" onclick="location.replace('logout.php'); return false;">
            <i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span>
        </a>
    </div>
</aside>
