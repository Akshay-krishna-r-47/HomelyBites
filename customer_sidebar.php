<?php
// Get the current page filename
$current_page = basename($_SERVER['PHP_SELF']);

// Check Seller Application Status if user is logged in
// Check Seller Status
$seller_link_text = "Become Seller";
$seller_link_icon = "fa-solid fa-store";
$is_seller_pending = false;
$is_seller_approved = (isset($_SESSION['seller_approved']) && $_SESSION['seller_approved'] == 1);

if (isset($_SESSION['user_id']) && !$is_seller_approved) {
    include_once 'db_connect.php'; 
    $check_sidebar_stmt = $conn->prepare("SELECT status FROM seller_applications WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
    if ($check_sidebar_stmt) {
        $check_sidebar_stmt->bind_param("i", $_SESSION['user_id']);
        $check_sidebar_stmt->execute();
        $check_sidebar_stmt->bind_result($app_status);
        if ($check_sidebar_stmt->fetch()) {
             if ($app_status == 'Pending') {
                $seller_link_text = "Application Pending";
                $seller_link_icon = "fa-solid fa-hourglass-half";
                $is_seller_pending = true;
             } elseif ($app_status == 'Approved') {
                 // Fallback if session wasn't updated yet
                 $is_seller_approved = true;
                 $_SESSION['seller_approved'] = 1; // Auto-sync session
             }
        }
        $check_sidebar_stmt->close();
    }
}

if ($is_seller_approved) {
    $seller_link_text = "Seller Dashboard";
    $seller_link_icon = "fa-solid fa-store";
}


// Check Delivery Status
$delivery_link_text = "Become Delivery Partner";
$delivery_link_icon = "fa-solid fa-truck";
$is_delivery_pending = false;
$is_delivery_approved = (isset($_SESSION['delivery_approved']) && $_SESSION['delivery_approved'] == 1);

if (isset($_SESSION['user_id']) && !$is_delivery_approved) {
    $check_delivery_stmt = $conn->prepare("SELECT status FROM delivery_applications WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
    if ($check_delivery_stmt) {
        $check_delivery_stmt->bind_param("i", $_SESSION['user_id']);
        $check_delivery_stmt->execute();
        $check_delivery_stmt->bind_result($del_app_status);
        if ($check_delivery_stmt->fetch()) {
             if ($del_app_status == 'Pending') {
                $delivery_link_text = "Application Pending";
                $delivery_link_icon = "fa-solid fa-hourglass-half";
                $is_delivery_pending = true;
             } elseif ($del_app_status == 'Approved') {
                 $is_delivery_approved = true;
                 $_SESSION['delivery_approved'] = 1; // Auto-sync session
             }
        }
        $check_delivery_stmt->close();
    }
}

if ($is_delivery_approved) {
    $delivery_link_text = "Delivery Dashboard";
    $delivery_link_icon = "fa-solid fa-truck";
}
?>
<!-- Setup Global Fonts for Sidebar Consistency -->
<link href="https://fonts.googleapis.com/css2?family=Lemon&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
    /* SIDEBAR STYLES - Centralized */
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
        white-space: normal;
        line-height: 1.2;
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

    .sidebar.collapsed .logout-link span { display: none; }
    .sidebar.collapsed .logout-link i { margin-right: 0; }
    .sidebar.collapsed .logout-link a { justify-content: center; }
</style>
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
            <a href="customer_dashboard.php" class="<?php echo ($current_page == 'customer_dashboard.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-house"></i> <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="customer_cart.php" class="<?php echo ($current_page == 'customer_cart.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-cart-shopping"></i> <span>Cart</span>
            </a>
        </li>
        <li>
            <a href="customer_orders.php" class="<?php echo ($current_page == 'customer_orders.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-bag-shopping"></i> <span>My Orders</span>
            </a>
        </li>
        <li>
            <a href="customer_scheduled.php" class="<?php echo ($current_page == 'customer_scheduled.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-calendar-check"></i> <span>Scheduled Deliveries</span>
            </a>
        </li>
        <li>
            <a href="customer_track.php" class="<?php echo ($current_page == 'customer_track.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-location-dot"></i> <span>Track Order</span>
            </a>
        </li>
        <li>
            <a href="customer_reviews.php" class="<?php echo ($current_page == 'customer_reviews.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-star"></i> <span>Reviews</span>
            </a>
        </li>
        <li>
            <a href="customer_profile.php" class="<?php echo ($current_page == 'customer_profile.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-user"></i> <span>Profile</span>
            </a>
        </li>
        <li>
            <a href="notifications.php?view=customer" class="<?php echo ($current_page == 'notifications.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-bell"></i> <span>Notifications</span>
            </a>
        </li>
        <!-- Seller Link -->
        <li>
            <a href="<?php echo $is_seller_approved ? 'seller_dashboard.php' : 'become_seller.php'; ?>" class="<?php echo ($current_page == 'become_seller.php' || $current_page == 'seller_dashboard.php') ? 'active' : ''; ?>" <?php echo $is_seller_pending ? 'style="color: #f39c12;"' : ''; ?>>
                <i class="<?php echo $seller_link_icon; ?>"></i> <span><?php echo $seller_link_text; ?></span>
            </a>
        </li>
        
        <!-- Delivery Link -->
        <li>
            <a href="<?php echo $is_delivery_approved ? 'delivery_dashboard.php' : 'become_delivery_partner.php'; ?>" class="<?php echo ($current_page == 'become_delivery_partner.php' || $current_page == 'delivery_dashboard.php') ? 'active' : ''; ?>" <?php echo $is_delivery_pending ? 'style="color: #f39c12;"' : ''; ?>>
                <i class="<?php echo $delivery_link_icon; ?>"></i> <span><?php echo $delivery_link_text; ?></span>
            </a>
        </li>
    </ul>
    <div class="logout-link">
        <a href="#" onclick="location.replace('logout.php'); return false;">
            <i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span>
        </a>
    </div>
</aside>
