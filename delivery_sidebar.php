<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<style>
    /* SIDEBAR STYLES - Consistent with Customer Sidebar */
    :root {
        --primary-color: #27ae60;
        --brand-green: #008000;
        --sidebar-width: 280px;
        --sidebar-collapsed-width: 80px;
    }

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
        margin: 0;
        color: #fff;
        font-weight: 400;
        letter-spacing: 1px;
    }

    .sidebar.collapsed .sidebar-logo h1 { display: none; }
    .sidebar.collapsed .sidebar-logo::after { content: 'HB'; font-family: 'Lemon', serif; font-size: 1.5rem; }

    .toggle-btn {
        background: rgba(255,255,255,0.1);
        border: none;
        color: white;
        width: 32px;
        height: 32px;
        border-radius: 8px;
        cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        transition: background 0.2s;
    }
    .toggle-btn:hover { background: rgba(255,255,255,0.2); }

    .nav-links {
        list-style: none;
        padding: 0;
        margin: 0;
        flex-grow: 1;
    }

    .nav-links li { margin-bottom: 8px; }

    .nav-links a {
        display: flex;
        align-items: center;
        padding: 12px 16px;
        color: rgba(255,255,255,0.85);
        text-decoration: none;
        border-radius: 12px;
        transition: all 0.3s ease;
        font-weight: 500;
        font-size: 0.95rem;
        gap: 15px;
    }

    .nav-links a:hover {
        background-color: rgba(255,255,255,0.1);
        color: #fff;
        transform: translateX(4px);
    }

    .nav-links a.active {
        background-color: rgba(255,255,255,0.2);
        color: #fff;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        font-weight: 600;
    }

    .nav-links a i {
        width: 24px;
        text-align: center;
        font-size: 1.1rem;
    }

    .sidebar-footer {
        padding-top: 20px;
        border-top: 1px solid rgba(255,255,255,0.1);
    }
</style>

<aside class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <h1>Homely Bites</h1>
        </div>
        <button class="toggle-btn" onclick="toggleSidebar()">
            <i class="fa-solid fa-bars"></i>
        </button>
    </div>

    <ul class="nav-links">
        <li>
            <a href="delivery_dashboard.php" class="<?php echo ($current_page == 'delivery_dashboard.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-gauge-high"></i> <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="delivery_orders.php" class="<?php echo ($current_page == 'delivery_orders.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-clipboard-list"></i> <span>Available Orders</span>
            </a>
        </li>
        <li>
            <a href="delivery_history.php" class="<?php echo ($current_page == 'delivery_history.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-clock-rotate-left"></i> <span>History</span>
            </a>
        </li>
        <li>
            <a href="delivery_earnings.php" class="<?php echo ($current_page == 'delivery_earnings.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-wallet"></i> <span>Earnings</span>
            </a>
        </li>
        <li>
            <a href="delivery_profile.php" class="<?php echo ($current_page == 'delivery_profile.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-user-gear"></i> <span>Profile</span>
            </a>
        </li>
        <li>
            <a href="notifications.php?view=delivery" class="<?php echo ($current_page == 'notifications.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-bell"></i> <span>Notifications</span>
            </a>
        </li>
    </ul>

    <ul class="nav-links sidebar-footer">
        <?php if (isset($_SESSION['seller_approved']) && $_SESSION['seller_approved'] == 1): ?>
        <li>
            <a href="seller_dashboard.php">
                <i class="fa-solid fa-store"></i> <span>Seller Dashboard</span>
            </a>
        </li>
        <?php endif; ?>
        <li>
            <a href="customer_dashboard.php">
                <i class="fa-solid fa-arrow-left"></i> <span>Back to Customer</span>
            </a>
        </li>
        <li>
            <a href="logout.php">
                <i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span>
            </a>
        </li>
    </ul>
</aside>
