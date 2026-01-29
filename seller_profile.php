<?php
include 'role_check.php';
check_role_access('seller');
include 'db_connect.php';

$seller_id = $_SESSION['user_id'];
$message = "";
$message_type = "";

// Handle Profile Update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    // Note: Password update usually requires old password check, skipping for brevity unless requested.
    // Also, email update might require verification, but implementing simple update here.
    
    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE user_id = ?");
    $stmt->bind_param("ssi", $name, $email, $seller_id);
    
    if ($stmt->execute()) {
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
$stmt = $conn->prepare("SELECT name, email, created_at FROM users WHERE user_id = ?");
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$stmt->bind_result($name, $email, $joined_at);
$stmt->fetch();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Profile - Homely Bites</title>
    <link href="https://fonts.googleapis.com/css2?family=Lemon&family=Lato:wght@300;400;700&family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary-color: #27ae60; --brand-green: #008000; --bg-body: #fdfbf7; --card-bg: #FFFFFF; --text-dark: #2c3e50; --header-height: 80px; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Lato', sans-serif; }
        body { background-color: var(--bg-body); color: var(--text-dark); display: flex; min-height: 100vh; }
        .main-content { flex: 1; display: flex; flex-direction: column; width: 0; transition: all 0.4s; }
        header { height: var(--header-height); background-color: var(--card-bg); padding: 0 40px; display: flex; align-items: center; justify-content: flex-end; position: sticky; top: 0; z-index: 900; border-bottom: 1px solid rgba(0,0,0,0.06); }
        .content-container { padding: 40px 50px; width: 100%; max-width: 1000px; margin: 0 auto; }
        
        .profile-card { background: var(--card-bg); padding: 40px; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.05); }
        .profile-header { display: flex; align-items: center; gap: 20px; margin-bottom: 40px; padding-bottom: 30px; border-bottom: 1px solid #f5f5f5; }
        .profile-avatar { width: 100px; height: 100px; background: #e8f5e9; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--brand-green); font-size: 3rem; }
        .profile-title h2 { font-family: 'Playfair Display', serif; font-size: 2rem; margin-bottom: 5px; }
        .profile-title p { color: #888; }
        
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
                        <label>Display Name / Shop Name</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                    </div>
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
            </div>
        </div>
    </div>
    <script>function toggleSidebar(){document.querySelector('.sidebar').classList.toggle('collapsed');}</script>
</body>
</html>
