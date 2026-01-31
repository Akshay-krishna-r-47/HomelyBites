<?php
session_start();
include_once 'helpers.php';
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

$formatted_name = formatName($_SESSION['name']);
$admin_name = htmlspecialchars($formatted_name);
$admin_initials = getAvatarInitials($formatted_name);

$msg = "";
$msg_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $target_group = $_POST['target_group']; // 'all', 'customer', 'seller', 'delivery'
    $title = trim($_POST['title']);
    $message_body = trim($_POST['message']);
    $type = $_POST['type']; // 'info', 'success', 'warning', 'alert'

    if (empty($title) || empty($message_body)) {
        $msg = "Title and Message are required.";
        $msg_type = "error";
    } else {
        // Determine recipients
        $sql = "";
        if ($target_group == 'all') {
            $sql = "SELECT user_id FROM users";
        } elseif ($target_group == 'admin') {
             $sql = "SELECT user_id FROM users WHERE role='Admin'";
        }else {
            // For roles like seller/delivery, we check the flag in users or specific tables?
            // Users table has singular role. But we have additive flags.
            // Let's use the flags.
            if ($target_group == 'seller') {
                $sql = "SELECT user_id FROM users WHERE seller_approved = 1";
            } elseif ($target_group == 'delivery') {
                $sql = "SELECT user_id FROM users WHERE delivery_approved = 1";
            } else {
                // Customer - basically everyone who is not banned? Or just everyone.
                // Strictly speaking everyone is a customer.
                // If we want "Only Customers who are NOT sellers/delivery", that's complex.
                // Let's assume 'Customer' target means EVERYONE for now, or just 'Normal' login types?
                // Simplest: Customer = All Users.
                $sql = "SELECT user_id FROM users"; 
            }
        }

        $recipients = $conn->query($sql);
        $count = 0;
        if ($recipients) {
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
            while ($row = $recipients->fetch_assoc()) {
                $uid = $row['user_id'];
                $stmt->bind_param("isss", $uid, $title, $message_body, $type);
                $stmt->execute();
                $count++;
            }
            $stmt->close();
            $msg = "Broadcast sent successfully to $count users!";
            $msg_type = "success";
        } else {
            $msg = "Error fetching recipients.";
            $msg_type = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Broadcast Notifications - Homely Bites</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary-color: #27ae60; --brand-green: #008000; --bg-body: #fdfbf7; --card-bg: #FFFFFF; --text-dark: #2c3e50; --header-height: 80px; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: var(--bg-body); color: var(--text-dark); display: flex; min-height: 100vh; }
        .main-content { flex: 1; display: flex; flex-direction: column; width: 0; }
        
        header { height: var(--header-height); background-color: var(--card-bg); padding: 0 40px; display: flex; align-items: center; justify-content: flex-end; position: sticky; top: 0; z-index: 900; border-bottom: 1px solid rgba(0,0,0,0.06); gap: 15px; }
        .content-container { padding: 40px 50px; width: 100%; max-width: 1000px; margin: 0 auto; }
        
        .broadcast-card { background: white; padding: 40px; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
        .form-group { margin-bottom: 25px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; color: #444; }
        .form-group select, .form-group input, .form-group textarea { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem; }
        
        .btn-send { background: var(--primary-color); color: white; border: none; padding: 12px 30px; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 1rem; transition: 0.3s; width: 100%; display: flex; align-items: center; justify-content: center; gap: 10px; }
        .btn-send:hover { background: #219150; }
        
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>
    <div class="main-content">
        <header>
            <div style="text-align: right;">
                <p style="font-weight: 700; margin-bottom: 2px;"><?php echo $admin_name; ?></p>
                <span style="font-size: 0.8rem; color: #888;">Administrator</span>
            </div>
            <div style="width: 40px; height: 40px; background: #27ae60; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                <?php echo $admin_initials; ?>
            </div>
        </header>

        <div class="content-container">
            <h2 style="margin-bottom: 30px; font-size: 2rem; font-weight: 700;">Broadcast Notification</h2>
            
            <?php if ($msg): ?> <div class="alert alert-<?php echo $msg_type; ?>"><?php echo $msg; ?></div> <?php endif; ?>

            <div class="broadcast-card">
                <form method="POST">
                    <div class="form-group">
                        <label>Target Audience</label>
                        <select name="target_group">
                            <option value="all">All Users</option>
                            <option value="seller">All Sellers</option>
                            <option value="delivery">All Delivery Partners</option>
                            <option value="admin">Other Admins</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Notification Type</label>
                        <select name="type">
                            <option value="info">Information (Blue)</option>
                            <option value="success">Success (Green)</option>
                            <option value="warning">Warning (Orange)</option>
                            <option value="alert">Alert (Red)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Title</label>
                        <input type="text" name="title" placeholder="e.g. System Maintenance" required>
                    </div>

                    <div class="form-group">
                        <label>Message</label>
                        <textarea name="message" rows="5" placeholder="Enter your message here..." required></textarea>
                    </div>

                    <button type="submit" class="btn-send"><i class="fa-solid fa-paper-plane"></i> Send Broadcast</button>
                </form>
            </div>
        </div>
    </div>
    <script>function toggleSidebar(){ document.querySelector('.sidebar').classList.toggle('collapsed'); }</script>
</body>
</html>
