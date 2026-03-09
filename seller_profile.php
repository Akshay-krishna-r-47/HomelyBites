<?php
include 'role_check.php';
check_role_access('seller');
include 'db_connect.php';
include_once 'helpers.php';

$seller_id = $_SESSION['user_id'];
$message = "";
$message_type = "";

// Handle Profile Update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $business_name = isset($_POST['business_name']) ? trim($_POST['business_name']) : '';
    
    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE user_id = ?");
    $stmt->bind_param("ssi", $name, $email, $seller_id);
    
    if ($stmt->execute()) {
        if (!empty($business_name)) {
            $stmt2 = $conn->prepare("UPDATE seller_applications SET business_name = ? WHERE user_id = ? AND status = 'Approved'");
            $stmt2->bind_param("si", $business_name, $seller_id);
            $stmt2->execute();
            $stmt2->close();
        }
    
        log_activity($conn, $seller_id, 'Seller Profile Updated', 'Seller updated their profile details.');
        $_SESSION['name'] = $name; // Update session
        $message = "Profile updated successfully.";
        $message_type = "success";
    } else {
        $message = "Error updating profile.";
        $message_type = "error";
    }
    $stmt->close();
}

// Fetch Current Data
$stmt = $conn->prepare("SELECT u.name, u.email, u.created_at, sa.business_name FROM users u LEFT JOIN seller_applications sa ON u.user_id = sa.user_id AND sa.status = 'Approved' WHERE u.user_id = ?");
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$stmt->bind_result($name, $email, $joined_at, $business_name);
$stmt->fetch();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Profile - Homely Bites</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary-color: #27ae60; --brand-green: #0a8f08; --bg-body: #f8f8f8; --card-bg: #FFFFFF; --text-dark: #222; --text-muted: #666; --header-height: 80px; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: var(--bg-body); color: var(--text-dark); display: flex; min-height: 100vh; }
        .main-content { flex: 1; display: flex; flex-direction: column; width: 0; transition: all 0.4s; }
        header { height: var(--header-height); background-color: var(--card-bg); padding: 0 40px; display: flex; align-items: center; justify-content: flex-end; position: sticky; top: 0; z-index: 900; border-bottom: 1px solid rgba(0,0,0,0.06); }
        .content-container { padding: 40px 50px; width: 100%; max-width: 1000px; margin: 0 auto; }
        
        .profile-card { background: var(--card-bg); padding: 40px; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.05); }
        .profile-header { display: flex; align-items: center; gap: 20px; margin-bottom: 40px; padding-bottom: 30px; border-bottom: 1px solid #f5f5f5; }
        .profile-avatar { width: 100px; height: 100px; background: #e8f5e9; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--brand-green); font-size: 3rem; }
        .profile-title h2 { font-weight: 700; font-size: 2rem; margin-bottom: 5px; }
        .profile-title p { color: #888; }
        
        /* Modal Styles */
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.6); z-index: 1000;
            display: none; justify-content: center; align-items: center;
            backdrop-filter: blur(4px);
        }
        .modal-content {
            background: white; width: 90%; max-width: 400px;
            padding: 30px; border-radius: 20px; 
            box-shadow: 0 20px 50px rgba(0,0,0,0.2);
            text-align: center; animation: slideDown 0.3s ease-out;
        }
        @keyframes slideDown { from { transform: translateY(-40px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

        .form-group { margin-bottom: 25px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 700; color: #444; }
        .form-group input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem; outline: none; transition: border-color 0.3s; }
        .form-group input:focus { border-color: var(--primary-color); }
        
        .btn-save { background: var(--primary-color); color: white; border: none; padding: 12px 30px; border-radius: 8px; font-weight: 700; cursor: pointer; font-size: 1rem; }
        .btn-save:hover { background: #219150; }
        
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <?php include 'seller_sidebar.php'; ?>
    <div class="main-content">
        <header>
            <div style="text-align: right; margin-right: 15px;">
                <p style="font-weight: 700; margin-bottom: 2px;"><?php echo htmlspecialchars($_SESSION['name']); ?></p>
                <span style="font-size: 0.8rem; color: #888;">Seller Panel</span>
            </div>
            <div style="width: 40px; height: 40px; background: #eee; border-radius: 50%; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                <?php 
                $header_name = formatName($_SESSION['name']);
                $header_initials = getAvatarInitials($header_name);
                $header_img = getProfileImage($_SESSION['user_id'], $conn);
                if ($header_img): ?>
                    <img src="<?php echo $header_img; ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                <?php else: ?>
                    <span style="font-weight: 600; color: #555;"><?php echo $header_initials; ?></span>
                <?php endif; ?>
            </div>
            <div style="width: 40px; height: 40px; background: #ddd; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                <i class="fa-solid fa-store"></i>
            </div>
        </header>

        <div class="content-container">
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
            <?php endif; ?>

            <div class="profile-card">
                <div class="profile-header">
                    <div class="profile-avatar"><i class="fa-solid fa-user"></i></div>
                    <div class="profile-title">
                        <h2>My Profile</h2>
                        <p>Manage your account settings</p>
                    </div>
                </div>
                
                <form method="POST">
                    <div class="form-group">
                        <label>Display Name (Personal Name)</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                    </div>
                    <?php if (isset($business_name)): ?>
                    <div class="form-group">
                        <label>Kitchen / Business Name</label>
                        <input type="text" name="business_name" value="<?php echo htmlspecialchars($business_name); ?>" required>
                    </div>
                    <?php endif; ?>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Member Since</label>
                        <input type="text" value="<?php echo date('F j, Y', strtotime($joined_at)); ?>" disabled style="background: #f9f9f9; color: #777;">
                    </div>
                    
                    <button type="submit" class="btn-save">Save Changes</button>
                </form>

                <div style="margin-top: 40px; border-top: 1px solid #ffebee; padding-top: 30px;">
                    <h3 style="color: #d32f2f; font-size: 1.2rem; margin-bottom: 10px;">Danger Zone</h3>
                    <p style="color: #666; font-size: 0.9rem; margin-bottom: 15px;">Deactivate your seller account. All your food items will be marked as unavailable.</p>
                    <button type="button" onclick="showDeactivateModal()" style="background: #fff; color: #d32f2f; border: 1px solid #d32f2f; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s;" onmouseover="this.style.background='#ffebee'" onmouseout="this.style.background='#fff'">
                        <i class="fa-solid fa-store-slash"></i> Deactivate Seller Account
                    </button>
                    
                    <form id="deactivateSellerForm" action="account_actions.php" method="POST" style="display: none;">
                        <input type="hidden" name="action" value="deactivate_seller">
                    </form>
                </div>

                <!-- Custom Deactivate Confirmation Modal -->
                <div id="deactivateConfirmModal" class="modal-overlay">
                    <div class="modal-content">
                        <div style="width: 70px; height: 70px; background: #fff3cd; color: #f39c12; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 20px;">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>
                        <h3 style="font-size: 1.5rem; color: #333; margin-bottom: 10px; font-weight: 700;">Deactivate Account?</h3>
                        <p style="color: #666; font-size: 0.95rem; margin-bottom: 25px; line-height: 1.5;">Are you sure you want to deactivate your seller account? Your food items will become unavailable, but you will still remain a regular customer.</p>
                        
                        <div style="display: flex; gap: 15px; justify-content: center;">
                            <button type="button" onclick="closeDeactivateModal()" style="padding: 12px 24px; border-radius: 8px; border: 1px solid #ddd; background: #fff; color: #555; font-weight: 600; cursor: pointer; flex: 1; transition: 0.2s;">Cancel</button>
                            <button type="button" onclick="confirmDeactivateSeller()" style="padding: 12px 24px; border-radius: 8px; border: none; background: #f39c12; color: white; font-weight: 600; cursor: pointer; flex: 1; box-shadow: 0 4px 12px rgba(243,156,18,0.2); transition: 0.2s;">Deactivate</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        function toggleSidebar(){document.querySelector('.sidebar').classList.toggle('collapsed');}
        
        // Modal Logic for Deactivate Account
        const deactivateModal = document.getElementById('deactivateConfirmModal');
        
        function showDeactivateModal() { deactivateModal.style.display = 'flex'; }
        function closeDeactivateModal() { deactivateModal.style.display = 'none'; }
        function confirmDeactivateSeller() { document.getElementById('deactivateSellerForm').submit(); }
        
        window.onclick = function(event) {
            if (event.target == deactivateModal) {
                closeDeactivateModal();
            }
        }
    </script>
</body>
</html>
