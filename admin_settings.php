<?php
session_start();
include_once 'helpers.php';
// Prevent caching
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

// Handle Form Submission
$msg = "";
$msg_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'setting_') === 0) {
            $setting_key = substr($key, 8); // Remove 'setting_' prefix
            $setting_val = trim($value);
            
            $stmt = $conn->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->bind_param("ss", $setting_val, $setting_key);
            $stmt->execute();
        }
    }
    $msg = "Settings updated successfully!";
    $msg_type = "success";
}

// Fetch Settings
$settings = [];
$result = $conn->query("SELECT * FROM system_settings");
while ($row = $result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Platform Settings - Homely Bites</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary-color: #27ae60; --brand-green: #008000; --bg-body: #fdfbf7; --card-bg: #FFFFFF; --text-dark: #2c3e50; --header-height: 80px; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: var(--bg-body); color: var(--text-dark); display: flex; min-height: 100vh; }
        .main-content { flex: 1; display: flex; flex-direction: column; width: 0; }
        
        header { height: var(--header-height); background-color: var(--card-bg); padding: 0 40px; display: flex; align-items: center; justify-content: flex-end; position: sticky; top: 0; z-index: 900; border-bottom: 1px solid rgba(0,0,0,0.06); gap: 15px; }
        .content-container { padding: 40px 50px; width: 100%; max-width: 1200px; margin: 0 auto; }
        
        .settings-card { background: white; padding: 40px; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
        .form-group { margin-bottom: 25px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; color: #444; }
        .form-group small { display: block; margin-bottom: 8px; color: #888; font-size: 0.85rem; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem; }
        
        .btn-save { background: var(--primary-color); color: white; border: none; padding: 12px 30px; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 1rem; transition: 0.3s; }
        .btn-save:hover { background: #219150; }
        
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; }
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
            <h2 style="margin-bottom: 30px; font-size: 2rem; font-weight: 700;">Platform Settings</h2>
            
            <?php if ($msg): ?> <div class="alert alert-<?php echo $msg_type; ?>"><?php echo $msg; ?></div> <?php endif; ?>

            <div class="settings-card">
                <form method="POST">
                    
                    <div class="form-group">
                        <label>Commission Percentage (%)</label>
                        <small><?php echo htmlspecialchars($settings['commission_percentage']['description']); ?></small>
                        <input type="number" step="0.01" name="setting_commission_percentage" value="<?php echo htmlspecialchars($settings['commission_percentage']['setting_value']); ?>">
                    </div>

                    <div class="form-group">
                        <label>Base Delivery Fee (₹)</label>
                        <small><?php echo htmlspecialchars($settings['delivery_fee_base']['description']); ?></small>
                        <input type="number" step="0.01" name="setting_delivery_fee_base" value="<?php echo htmlspecialchars($settings['delivery_fee_base']['setting_value']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Support Email</label>
                        <small><?php echo htmlspecialchars($settings['support_email']['description']); ?></small>
                        <input type="email" name="setting_support_email" value="<?php echo htmlspecialchars($settings['support_email']['setting_value']); ?>">
                    </div>

                    <div class="form-group">
                        <label>Maintenance Mode</label>
                        <small><?php echo htmlspecialchars($settings['maintenance_mode']['description']); ?></small>
                        <select name="setting_maintenance_mode">
                            <option value="0" <?php echo ($settings['maintenance_mode']['setting_value'] == '0') ? 'selected' : ''; ?>>Off (Live)</option>
                            <option value="1" <?php echo ($settings['maintenance_mode']['setting_value'] == '1') ? 'selected' : ''; ?>>On (Under Maintenance)</option>
                        </select>
                    </div>

                    <button type="submit" class="btn-save"><i class="fa-solid fa-save"></i> Save Changes</button>
                </form>
            </div>
        </div>
    </div>
    <script>function toggleSidebar(){ document.querySelector('.sidebar').classList.toggle('collapsed'); }</script>
</body>
</html>
