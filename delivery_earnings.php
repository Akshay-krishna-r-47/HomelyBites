<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['delivery_approved']) || $_SESSION['delivery_approved'] != 1) {
    header("Location: customer_dashboard.php");
    exit();
}
include 'db_connect.php';
include_once 'helpers.php';

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
    <title>Earnings - Homely Bites</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --bg-body: #f8f8f8; --card-bg: #FFFFFF; --header-height: 80px; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: var(--bg-body); color: #222; display: flex; min-height: 100vh; }
        .main-content { flex: 1; display: flex; flex-direction: column; width: 0; }
        
        header { height: var(--header-height); background-color: var(--card-bg); padding: 0 40px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 900; box-shadow: 0 2px 10px rgba(0,0,0,0.04); }
        .header-title h2 { font-size: 1.5rem; font-weight: 700; color: #333; }
        .user-info { display: flex; align-items: center; gap: 15px; text-align: right; }
        .profile-pic { width: 40px; height: 40px; background: #eee; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.9rem; color: #555; overflow: hidden; }

        .content-container { padding: 40px 60px; max-width: 1400px; margin: 0 auto; width: 100%; }
        .empty-state { text-align: center; padding: 80px 20px; background: white; border-radius: 16px; box-shadow: 0 4px 14px rgba(0,0,0,0.08); }
        .empty-state i { font-size: 3.5rem; color: #ddd; margin-bottom: 20px; }
        .empty-state h3 { font-size: 1.2rem; color: #333; margin-bottom: 8px; }
        .empty-state p { color: #888; }
        
        .overview-card { background: linear-gradient(135deg, #27ae60, #2ecc71); color: white; padding: 30px; border-radius: 16px; margin-bottom: 30px; box-shadow: 0 10px 20px rgba(39, 174, 96, 0.2); }
        .overview-card h1 { font-family: 'Poppins', sans-serif; font-size: 2.5rem; margin-top: 10px; }
    </style>
</head>
<body>
    <?php include 'delivery_sidebar.php'; ?>
    <div class="main-content">
        <header>
            <div class="header-title"><h2>Earnings</h2></div>
            <div class="user-info">
                 <div>
                    <p style="font-weight: 700; font-size: 0.95rem; margin-bottom: 2px;"><?php echo $user_name; ?></p>
                     <span style="font-size: 0.75rem; color: #fff; background-color: #27ae60; padding: 1px 6px; border-radius: 4px; text-transform: uppercase;">Delivery</span>
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
            <div class="overview-card">
                <span>Total Earnings</span>
                <h1>₹0.00</h1>
                <p style="opacity: 0.9;">No earnings recorded yet.</p>
            </div>
            
            <div class="empty-state">
                <i class="fa-solid fa-chart-line"></i>
                <h3>No Recent Transactions</h3>
                <p>Complete deliveries to start earning!</p>
            </div>
        </div>
    </div>
    <script>function toggleSidebar() { document.querySelector('.sidebar').classList.toggle('collapsed'); }</script>
</body>
</html>
