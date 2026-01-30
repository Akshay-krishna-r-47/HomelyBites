<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['delivery_approved']) || $_SESSION['delivery_approved'] != 1) {
    header("Location: customer_dashboard.php");
    exit();
}
include 'db_connect.php';
include_once 'helpers.php';

$user_id = $_SESSION['user_id'];
$formatted_name = formatName($_SESSION['name']);
$user_name = htmlspecialchars($formatted_name);
$user_initials = getAvatarInitials($formatted_name);
$user_email = $_SESSION['email'];

// Handle Profile Update
$update_msg = "";
$update_err = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $new_name = trim($_POST['full_name']);
    $new_phone = trim($_POST['phone']);
    $new_street = trim($_POST['street']);
    $new_city = trim($_POST['city']);
    $new_pincode = trim($_POST['pincode']);

    // Validation
    if (empty($new_name) || empty($new_phone) || empty($new_street) || empty($new_city) || empty($new_pincode)) {
        $update_err = "All fields are required.";
    } elseif (!preg_match("/^[a-zA-Z\s]+$/", $new_name)) {
        $update_err = "Name can only contain letters and spaces.";
    } elseif (!preg_match("/^[0-9]{10}$/", $new_phone)) {
        $update_err = "Phone number must be exactly 10 digits.";
    } elseif (!preg_match("/^\d{5,6}$/", $new_pincode)) {
        $update_err = "Enter a valid pincode.";
    } else {
        // Update DB
        $upd_stmt = $conn->prepare("UPDATE users SET name = ?, phone = ?, street = ?, city = ?, pincode = ? WHERE user_id = ?");
        $upd_stmt->bind_param("sssssi", $new_name, $new_phone, $new_street, $new_city, $new_pincode, $user_id);
        
        try {
            if ($upd_stmt->execute()) {
                $update_msg = "Profile updated successfully!";
                $_SESSION['name'] = $new_name; // Update session
                $user_name = htmlspecialchars($new_name); // Update local var
            }
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) { // Duplicate entry error code
                $update_err = "This phone number is already registered with another account.";
            } else {
                $update_err = "Error updating profile: " . $e->getMessage();
            }
        }
        $upd_stmt->close();
    }
}

// Handle Password Change
$pass_msg = "";
$pass_err = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    // Get current hash
    $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($db_hash);
    $stmt->fetch();
    $stmt->close();

    if ($db_hash && password_verify($current_pass, $db_hash)) {
        if ($new_pass === $confirm_pass) {
            if (strlen($new_pass) >= 6) {
                 $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
                 $upd = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                 $upd->bind_param("si", $new_hash, $user_id);
                 if ($upd->execute()) {
                     $pass_msg = "Password updated successfully!";
                 } else {
                     $pass_err = "Error updating password.";
                 }
                 $upd->close();
            } else {
                $pass_err = "New password must be at least 6 characters.";
            }
        } else {
            $pass_err = "New passwords do not match.";
        }
    } else {
        $pass_err = "Incorrect current password.";
    }
}

// Fetch Extended User Details
$stmt = $conn->prepare("SELECT phone, street, city, pincode, profile_image FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($phone, $street, $city, $pincode, $profile_image);
$stmt->fetch();
$stmt->close();

// Fetch Delivery Application Details
$vehicle_type = "Not Provided";
$vehicle_number = "Not Provided";
$license_number = "Not Provided";

$stmt2 = $conn->prepare("SELECT vehicle_type, vehicle_number, license_number FROM delivery_applications WHERE user_id = ? AND status = 'Approved' ORDER BY created_at DESC LIMIT 1");
if ($stmt2) {
    $stmt2->bind_param("i", $user_id);
    $stmt2->execute();
    $stmt2->bind_result($vehicle_type, $vehicle_number, $license_number);
    $stmt2->fetch();
    $stmt2->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Homely Bites</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --brand-green: #27ae60; --bg-body: #f8f8f8; --card-bg: #FFFFFF; --header-height: 80px; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: var(--bg-body); color: #222; display: flex; min-height: 100vh; }
        .main-content { flex: 1; display: flex; flex-direction: column; width: 0; }
        
        header { height: var(--header-height); background-color: var(--card-bg); padding: 0 40px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 900; box-shadow: 0 2px 10px rgba(0,0,0,0.04); }
        .header-title h2 { font-size: 1.5rem; font-weight: 700; color: #333; }
        .user-info { display: flex; align-items: center; gap: 15px; text-align: right; }
        .profile-pic { width: 40px; height: 40px; background: #eee; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.9rem; color: #555; overflow: hidden; }
        .profile-pic img { width: 100%; height: 100%; object-fit: cover; }

        .content-container { padding: 40px 60px; max-width: 1000px; margin: 0 auto; width: 100%; }
        
        .profile-card {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.08);
        }
        .profile-header-section { text-align: center; margin-bottom: 40px; }
        
        .big-profile-pic {
            width: 120px; height: 120px;
            background: #27ae60; color: white;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 3rem; font-weight: 700;
            margin: 0 auto 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(39, 174, 96, 0.3);
        }
        .big-profile-pic img { width: 100%; height: 100%; object-fit: cover; }
        
        .section-title { font-size: 1.1rem; font-weight: 700; color: #333; margin: 30px 0 20px; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px; display: flex; align-items: center; gap: 10px; justify-content: space-between; }
        .section-title i { color: #27ae60; }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
        }
        
        .info-item label { display: block; font-size: 0.8rem; color: #888; font-weight: 600; text-transform: uppercase; margin-bottom: 5px; }
        .info-item input { width: 100%; padding: 12px; border: 1px solid #eee; border-radius: 8px; font-size: 1rem; color: #333; background: #f9f9f9; transition: 0.2s; }
        .info-item input:disabled { background: #f9f9f9; border-color: #eee; color: #555; cursor: default; }
        .info-item input:focus { border-color: var(--brand-green); background: #fff; outline: none; }
        
        .status-badge { background: #e8f5e9; color: #27ae60; padding: 5px 12px; border-radius: 20px; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 6px; font-weight: 600; }

        /* Modal Styles */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; display: none; justify-content: center; align-items: center; backdrop-filter: blur(4px); }
        .modal-content { background: white; width: 90%; max-width: 500px; padding: 40px; border-radius: 20px; box-shadow: 0 20px 50px rgba(0,0,0,0.2); position: relative; }
        .close-modal { position: absolute; top: 20px; right: 20px; font-size: 1.5rem; cursor: pointer; color: #888; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; }
        .form-group input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; }

    </style>
</head>
<body>
    <?php include 'delivery_sidebar.php'; ?>
    <div class="main-content">
        <header>
            <div class="header-title"><h2>My Profile</h2></div>
            <div class="user-info">
                 <div>
                    <p style="font-weight: 700; font-size: 0.95rem; margin-bottom: 2px;"><?php echo $user_name; ?></p>
                     <span style="font-size: 0.75rem; color: #fff; background-color: #27ae60; padding: 1px 6px; border-radius: 4px; text-transform: uppercase;">Delivery</span>
                </div>
                <div class="profile-pic">
                    <?php if(!empty($profile_image) && file_exists($profile_image)): ?>
                        <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile">
                    <?php else: ?>
                        <?php echo $user_initials; ?>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <div class="content-container">
            <?php if ($update_msg): ?> <div style="margin-bottom: 20px; color: green; background: #e8f5e9; padding: 15px; border-radius: 8px;"><?php echo $update_msg; ?></div> <?php endif; ?>
            <?php if ($update_err): ?> <div style="margin-bottom: 20px; color: red; background: #ffebee; padding: 15px; border-radius: 8px;"><?php echo $update_err; ?></div> <?php endif; ?>

            <div class="profile-card">
                <div class="profile-header-section">
                    <div class="big-profile-pic">
                        <?php if(!empty($profile_image) && file_exists($profile_image)): ?>
                            <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile">
                        <?php else: ?>
                            <?php echo $user_initials; ?>
                        <?php endif; ?>
                    </div>
                    <h2 style="font-size: 1.8rem; color: #222;"><?php echo $user_name; ?></h2>
                    <div style="margin-top: 10px;">
                        <span class="status-badge"><i class="fa-solid fa-circle-check"></i> Approved Delivery Partner</span>
                    </div>
                </div>
                
                <form id="profileForm" method="POST">
                    <input type="hidden" name="update_profile" value="1">
                    
                    <!-- Personal Details -->
                    <div class="section-title">
                        <span><i class="fa-solid fa-user"></i> Personal Details</span>
                        <button type="button" id="editProfileBtn" onclick="enableEditMode()" style="background: var(--brand-green); color: white; border: none; padding: 8px 16px; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 0.9rem;">
                            <i class="fa-solid fa-pen-to-square"></i> Edit
                        </button>
                    </div>
                    
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Full Name</label>
                            <input type="text" name="full_name" value="<?php echo $user_name; ?>" disabled required>
                        </div>
                        <div class="info-item">
                            <label>Email Address</label>
                            <input type="email" value="<?php echo htmlspecialchars($user_email); ?>" disabled style="background-color: #f0f0f0;">
                        </div>
                        <div class="info-item">
                            <label>Phone Number</label>
                            <input type="text" name="phone" value="<?php echo htmlspecialchars($phone); ?>" disabled required pattern="[0-9]{10}">
                        </div>
                         <div class="info-item">
                            <label>Street Address</label>
                            <input type="text" name="street" value="<?php echo htmlspecialchars($street); ?>" disabled required>
                        </div>
                         <div class="info-item">
                            <label>City</label>
                            <input type="text" name="city" value="<?php echo htmlspecialchars($city); ?>" disabled required>
                        </div>
                         <div class="info-item">
                            <label>Pincode</label>
                            <input type="text" name="pincode" value="<?php echo htmlspecialchars($pincode); ?>" disabled required pattern="\d{5,6}">
                        </div>
                    </div>

                    <!-- Add Password Change Link -->
                    <div style="margin-top: 20px; text-align: right;">
                         <button type="button" onclick="openPasswordModal()" style="background: none; border: none; color: var(--brand-green); font-weight: 600; cursor: pointer; text-decoration: underline;">Change Password</button>
                    </div>

                    <!-- Edit Actions (Hidden by default) -->
                    <div id="editActions" style="display: none; gap: 10px; margin-top: 30px; justify-content:center;">
                        <button type="submit" style="background: var(--brand-green); color: white; border: none; padding: 12px 30px; border-radius: 8px; font-weight: 600; cursor: pointer;">Save Changes</button>
                        <button type="button" onclick="cancelEditMode()" style="background: #eee; color: #333; border: none; padding: 12px 30px; border-radius: 8px; font-weight: 600; cursor: pointer;">Cancel</button>
                    </div>
                </form>

                <!-- Vehicle Details (Read Only) -->
                <div class="section-title" style="margin-top: 40px;"><i class="fa-solid fa-motorcycle"></i> Vehicle & Application Details (Read Only)</div>
                 <div class="info-grid">
                    <div class="info-item">
                        <label>Vehicle Type</label>
                        <input type="text" value="<?php echo htmlspecialchars($vehicle_type); ?>" disabled>
                    </div>
                    <div class="info-item">
                        <label>Vehicle Number</label>
                        <input type="text" value="<?php echo htmlspecialchars($vehicle_number); ?>" disabled>
                    </div>
                    <div class="info-item">
                        <label>License Number</label>
                        <input type="text" value="<?php echo htmlspecialchars($license_number); ?>" disabled>
                    </div>
                </div>

            </div>
        </div>

        <!-- Change Password Modal -->
        <div id="passwordModal" class="modal-overlay">
            <div class="modal-content">
                <span class="close-modal" onclick="closePasswordModal()"><i class="fa-solid fa-times"></i></span>
                <h3 style="font-size: 1.4rem; font-weight: 700; margin-bottom: 20px;">Change Password</h3>
                
                <?php if ($pass_msg): ?> <div style="color: green; margin-bottom: 10px;"><?php echo $pass_msg; ?></div> <?php endif; ?>
                <?php if ($pass_err): ?> <div style="color: red; margin-bottom: 10px;"><?php echo $pass_err; ?></div> <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="change_password" value="1">
                    <div class="form-group">
                        <label>Current Password</label>
                        <input type="password" name="current_password" required>
                    </div>
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="new_password" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" required>
                    </div>
                    <button type="submit" style="background: var(--brand-green); color: white; border: none; padding: 12px; border-radius: 8px; font-weight: 600; cursor: pointer; width: 100%;">Update Password</button>
                </form>
            </div>
        </div>

    </div>
    <script>
        function toggleSidebar() { document.querySelector('.sidebar').classList.toggle('collapsed'); }

        function enableEditMode() {
            const form = document.getElementById('profileForm');
            const inputs = form.querySelectorAll('input:not([type="hidden"]):not([type="email"])');
            inputs.forEach(input => {
                input.removeAttribute('disabled');
                input.style.borderColor = '#27ae60';
            });
            document.getElementById('editActions').style.display = 'flex';
            document.getElementById('editProfileBtn').style.display = 'none';
        }

        function cancelEditMode() {
            location.reload();
        }

        const modal = document.getElementById('passwordModal');
        function openPasswordModal() { modal.style.display = 'flex'; }
        function closePasswordModal() { modal.style.display = 'none'; }
        
        <?php if ($pass_err || $pass_msg): ?>
            openPasswordModal();
        <?php endif; ?>
        
        window.onclick = function(event) {
            if (event.target == modal) closePasswordModal();
        }
    </script>
</body>
</html>
