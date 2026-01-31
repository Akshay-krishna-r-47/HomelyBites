<?php
include 'role_check.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Allow ANY logged in user (Admin, Customer, Seller, Delivery)
// We avoid check_role_access('customer') because it redirects Admins to dashboard.
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
// We override role checks below for specific sidebars if needed, 
// but role_check with 'customer' essentially just validates session login for non-banned users.

include 'db_connect.php';
include_once 'helpers.php';

$user_id = $_SESSION['user_id'];

// Determine which sidebar to include based on context
// Since this page is shared, we should ideally know the "mode" the user is in.
// However, the prompt implies global access. 
// Heuristic: If referrer is seller_, use seller sidebar.
// Simpler: Check if user is seller/delivery approved and default to 'highest' role or use a parameter.
// Let's use a standard priority: Admin > Seller > Delivery > Customer if ambiguity, 
// OR better: use the REFERER specific logic or just default to Customer if not viewing from a specific dashboard link?
// Actually, to make it seamless, we can check a GET parameter ?role=seller, or just rely on session.
// Let's default to Customer Sidebar, and if they come from Seller Dashboard (we can't easily know), 
// maybe we should just duplicate the link in all sidebars with ?view=seller etc?
// Proposed: Add ?view=role to the URL in the sidebar links.

$view_role = isset($_GET['view']) ? $_GET['view'] : 'customer';

// Fetch Notifications
$sql = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_id); // Changed to 's' to handle 'admin' string safely
$stmt->execute();
$result = $stmt->get_result();
$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}
$stmt->close();

// Mark all as read (Simple implementation)
// Mark all as read
$update_sql = "UPDATE notifications SET is_read = 1 WHERE user_id = ?";
$stmt = $conn->prepare($update_sql);
$stmt->bind_param("s", $user_id); // Bind as string to handle 'admin' or int IDs safely
$stmt->execute();
$stmt->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Homely Bites</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary-color: #fc8019; --brand-green: #0a8f08; --bg-body: #f8f8f8; --card-bg: #FFFFFF; --text-dark: #222; --text-muted: #666; --header-height: 80px; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: var(--bg-body); color: var(--text-dark); display: flex; min-height: 100vh; }
        .main-content { flex: 1; display: flex; flex-direction: column; width: 0; transition: all 0.4s; }
        header { height: var(--header-height); background-color: var(--card-bg); padding: 0 40px; display: flex; align-items: center; justify-content: flex-end; position: sticky; top: 0; z-index: 900; border-bottom: 1px solid rgba(0,0,0,0.06); }
        .content-container { padding: 40px 100px; width: 100%; max-width: 1200px; margin: 0 auto; }
        
        .page-header { margin-bottom: 30px; }
        .page-header h2 { font-weight: 700; font-size: 2rem; color: var(--text-dark); }
        
        .notification-list { display: flex; flex-direction: column; gap: 15px; }
        .notif-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); display: flex; gap: 20px; border-left: 5px solid #ddd; transition: 0.2s; }
        .notif-card:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.08); }
        
        .notif-card.info { border-left-color: #2196f3; }
        .notif-card.success { border-left-color: #4caf50; }
        .notif-card.warning { border-left-color: #ff9800; }
        .notif-card.alert { border-left-color: #f44336; }
        
        .notif-icon { min-width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; background: #f5f5f5; color: #555; }
        .info .notif-icon { background: #e3f2fd; color: #1976d2; }
        .success .notif-icon { background: #e8f5e9; color: #2e7d32; }
        .warning .notif-icon { background: #fff3e0; color: #f57c00; }
        .alert .notif-icon { background: #ffebee; color: #c62828; }
        
        .notif-content { flex: 1; }
        .notif-title { font-weight: 600; font-size: 1.05rem; margin-bottom: 5px; color: #333; }
        .notif-msg { color: #666; font-size: 0.95rem; line-height: 1.5; }
        .notif-time { font-size: 0.75rem; color: #999; margin-top: 8px; text-align: right; }
        
        .empty-state { text-align: center; padding: 50px; color: #999; font-style: italic; }
        
        @media (max-width: 768px) { .content-container { padding: 30px 20px; } }
    </style>
</head>
<body>
    <?php 
        // Dynamic Sidebar Inclusion
        if ($view_role == 'seller') {
            include 'seller_sidebar.php';
        } elseif ($view_role == 'delivery') {
            include 'delivery_sidebar.php';
        } elseif ($view_role == 'admin') {
            include 'admin_sidebar.php';
        } else {
            include 'customer_sidebar.php';
        }
    ?>
    <div class="main-content">
        <header>
            <div style="text-align: right; margin-right: 15px;">
                <p style="font-weight: 700; margin-bottom: 2px; color: #333;"><?php echo htmlspecialchars($_SESSION['name']); ?></p>
                <span style="font-size: 0.8rem; color: #888; text-transform: capitalize;"><?php echo $view_role; ?> Panel</span>
            </div>
            <div style="width: 40px; height: 40px; background: #eee; border-radius: 50%; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                <?php 
                // Fetch profile logic inline or ensure variables are set
                // logic similar to customer dashboard
                $formatted_name = formatName($_SESSION['name']);
                $initials = getAvatarInitials($formatted_name);
                $profile_img = getProfileImage($_SESSION['user_id'], $conn);
                if ($profile_img): ?>
                    <img src="<?php echo $profile_img; ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                <?php else: ?>
                    <span style="font-weight: 600; color: #555;"><?php echo $initials; ?></span>
                <?php endif; ?>
            </div>
        </header>

        <div class="content-container">
            <div class="page-header">
                <h2>Notifications</h2>
            </div>
            
            <div class="notification-list">
                <?php if (count($notifications) > 0): ?>
                    <?php foreach($notifications as $notif): ?>
                    <div class="notif-card <?php echo $notif['type']; ?>">
                        <div class="notif-icon">
                            <?php if($notif['type'] == 'success') echo '<i class="fa-solid fa-check"></i>'; ?>
                            <?php if($notif['type'] == 'warning') echo '<i class="fa-solid fa-triangle-exclamation"></i>'; ?>
                            <?php if($notif['type'] == 'alert') echo '<i class="fa-solid fa-circle-exclamation"></i>'; ?>
                            <?php if($notif['type'] == 'info') echo '<i class="fa-solid fa-info"></i>'; ?>
                        </div>
                        <div class="notif-content">
                            <div class="notif-title"><?php echo htmlspecialchars($notif['title']); ?></div>
                            <div class="notif-msg"><?php echo htmlspecialchars($notif['message']); ?></div>
                            <div class="notif-time"><?php echo date('M d, h:i A', strtotime($notif['created_at'])); ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fa-regular fa-bell-slash" style="font-size: 3rem; margin-bottom: 20px;"></i>
                        <p>No new notifications.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script>
        function toggleSidebar(){
             // Sidebar variable might vary by file included, assume .sidebar class
            document.querySelector('.sidebar').classList.toggle('collapsed');
        }
    </script>
</body>
</html>
