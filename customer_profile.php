<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

include_once 'role_check.php';
check_role_access('customer');
include 'db_connect.php';
include_once 'helpers.php';

$user_id = $_SESSION['user_id'];
$formatted_name = formatName($_SESSION['name']);
$user_name = htmlspecialchars($formatted_name);
$user_initials = getAvatarInitials($formatted_name);
$user_email = isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : 'Not Available';

// Fetch current profile image
$user_profile_image = null;
$stmt = $conn->prepare("SELECT profile_image FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($db_profile_image);
if ($stmt->fetch() && $db_profile_image) {
    if (file_exists($db_profile_image)) {
        $user_profile_image = $db_profile_image;
    }
}
$stmt->close();
$_SESSION['profile_image'] = $user_profile_image; // Sync session



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

// Handle Delete Profile Photo
if (isset($_POST['delete_photo']) && $_SERVER["REQUEST_METHOD"] == "POST") {
    $stmt = $conn->prepare("SELECT profile_image FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($current_image);
    $stmt->fetch();
    $stmt->close();

    if ($current_image && file_exists($current_image)) {
        unlink($current_image); // Delete file
    }

    $update_stmt = $conn->prepare("UPDATE users SET profile_image = NULL WHERE user_id = ?");
    $update_stmt->bind_param("i", $user_id);
    
    if ($update_stmt->execute()) {
        $_SESSION['profile_image'] = null; // Update session
        $update_msg = "Profile photo removed successfully.";
        // Refresh values for current page 
        $user_profile_image = null; 
    } else {
        $update_err = "Error removing photo.";
    }
    $update_stmt->close();
}

// Handle Profile Photo Upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["profile_photo"])) {
    $target_dir = "assets/images/users/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($_FILES["profile_photo"]["name"], PATHINFO_EXTENSION));
    $new_file_name = "user_" . $user_id . "_" . time() . "." . $file_extension;
    $target_file = $target_dir . $new_file_name;
    $uploadOk = 1;
    
    // Check for upload errors first
    if ($_FILES["profile_photo"]["error"] !== UPLOAD_ERR_OK) {
        $uploadOk = 0;
        switch ($_FILES["profile_photo"]["error"]) {
            case UPLOAD_ERR_INI_SIZE:
                $update_err = "File is too large (server limit).";
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $update_err = "File is too large (form limit).";
                break;
            case UPLOAD_ERR_NO_FILE:
                $update_err = "No file was uploaded.";
                break;
            default:
                $update_err = "Upload failed. Error code: " . $_FILES["profile_photo"]["error"];
        }
    } else {
        // Validation: Ensure tmp file exists
        if (!file_exists($_FILES["profile_photo"]["tmp_name"])) {
             $update_err = "Upload failed: Temporary file not found. " . htmlspecialchars($_FILES["profile_photo"]["tmp_name"]);
             $uploadOk = 0;
        } else {
            // Check if image file is a actual image
            $check = @getimagesize($_FILES["profile_photo"]["tmp_name"]);
            if($check === false) {
                // Try to get more info
                $file_mime = mime_content_type($_FILES["profile_photo"]["tmp_name"]);
                $update_err = "File is not a valid image. Detected type: " . $file_mime;
                $uploadOk = 0;
            }
        }
    }
    
    // Check file size (limit to 2MB)
    if ($_FILES["profile_photo"]["size"] > 2000000) {
        $update_err = "Sorry, your file is too large (Max 2MB).";
        $uploadOk = 0;
    }
    
    // Allow certain file formats
    if($file_extension != "jpg" && $file_extension != "png" && $file_extension != "jpeg" && $file_extension != "gif" ) {
        $update_err = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        $uploadOk = 0;
    }
    
    if ($uploadOk == 1) {
        if (move_uploaded_file($_FILES["profile_photo"]["tmp_name"], $target_file)) {
            // Delete old image if exists
            $stmt = $conn->prepare("SELECT profile_image FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->bind_result($old_image);
            $stmt->fetch();
            $stmt->close();
            
            if ($old_image && file_exists($old_image)) {
                unlink($old_image);
            }
            
            // Update DB
            $update_stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE user_id = ?");
            $update_stmt->bind_param("si", $target_file, $user_id);
            if ($update_stmt->execute()) {
                $_SESSION['profile_image'] = $target_file; // Update session
                $update_msg = "Profile photo updated successfully.";
                $user_profile_image = $target_file; // Refresh for view
            } else {
                 $update_err = "Database update failed.";
            }
            $update_stmt->close();
        } else {
            $update_err = "Sorry, there was an error uploading your file.";
        }
    }
}

// Fetch User Details (Phone, Address)
$user_phone = "Not Available";
$user_street = "";
$user_city = "";
$user_pincode = "";

$stmt = $conn->prepare("SELECT phone, street, city, pincode FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($user_phone, $user_street, $user_city, $user_pincode);
$stmt->fetch();
$stmt->close();

// Total Orders
$stmt = $conn->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?"); 
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($total_orders);
$stmt->fetch();
$stmt->close();

// Active Orders (Preparing or Out for Delivery)
$stmt = $conn->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND status IN ('Preparing', 'Out for Delivery')");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($active_orders);
$stmt->fetch();
$stmt->close();

// Scheduled Orders
$stmt = $conn->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND status = 'Scheduled'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($scheduled_orders);
$stmt->fetch();
$stmt->close();

// Total Spent
$stmt = $conn->prepare("SELECT SUM(total_amount) FROM orders WHERE user_id = ? AND status = 'Delivered'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($total_spent);
$stmt->fetch();
$stmt->close();

if ($total_spent === null) $total_spent = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Homely Bites</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Lemon&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* SWIGGY-STYLE DESIGN SYSTEM */
        :root {
            --primary-color: #fc8019;
            --brand-green: #0a8f08;
            --bg-body: #f8f8f8;
            --text-dark: #222;
            --text-muted: #666;
            --card-bg: #FFFFFF;
            --header-height: 80px;
            --shadow-card: 0 4px 14px rgba(0,0,0,0.08);
            --shadow-hover: 0 8px 20px rgba(0,0,0,0.12);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: var(--bg-body); color: var(--text-dark); display: flex; min-height: 100vh; }
        
        .main-content { flex: 1; display: flex; flex-direction: column; width: 0; }
        
        /* Header */
        header {
            height: var(--header-height);
            background-color: var(--card-bg);
            padding: 0 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 900;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
        }

        .search-container {
            display: flex; align-items: center; background: #f1f1f1; border-radius: 12px; padding: 12px 20px; width: 400px; transition: 0.3s;
        }
        .search-container i { color: #888; margin-right: 12px; }
        .search-container input { border: none; background: transparent; outline: none; width: 100%; font-size: 0.95rem; font-weight: 500; color: var(--text-dark); }

        .user-info { display: flex; align-items: center; gap: 15px; text-align: right; }
        .profile-pic { width: 40px; height: 40px; background: #eee; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.9rem; color: #555; overflow: hidden; object-fit: cover; }
        .profile-pic img { width: 100%; height: 100%; object-fit: cover; }

        .content-container { padding: 40px 60px; max-width: 1400px; margin: 0 auto; width: 100%; }
        
        .page-header h2 { font-size: 28px; font-weight: 700; margin-bottom: 20px; color: #222; }
        
        /* Summary Grid */
        .summary-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; margin-bottom: 40px; }
        
        .summary-card {
            background-color: var(--card-bg);
            padding: 25px;
            border-radius: 16px;
            box-shadow: var(--shadow-card);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: 0.3s;
        }
        
        .summary-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-hover); }

        .summary-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            background-color: #fdf5e6; /* Light Orange/Brand tint */
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .summary-info h3 { font-size: 1.8rem; font-weight: 700; margin-bottom: 4px; color: var(--text-dark); }
        .summary-info p { color: var(--text-muted); font-size: 0.95rem; font-weight: 500; }

        /* Profile Card */
        .profile-card { background: white; padding: 40px; border-radius: 20px; box-shadow: var(--shadow-card); max-width: 800px; margin: 0 auto; }
        .profile-header { display: flex; align-items: center; gap: 30px; margin-bottom: 40px; }
        /* NEW: Added flex-direction: column to contain image and button */
        .profile-image-section { display: flex; flex-direction: column; align-items: center; gap: 10px; }
        .profile-avatar-container { position: relative; width: 100px; height: 100px; cursor: pointer; }
        .profile-avatar { width: 100%; height: 100%; background: #ffe4b5; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; color: var(--primary-color); font-weight: 700; overflow: hidden; border: 4px solid #fff; box-shadow: 0 4px 15px rgba(0,0,0,0.1); transition: 0.3s; }
        .profile-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .avatar-overlay { position: absolute; inset: 0; background: rgba(0,0,0,0.4); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; opacity: 0; transition: 0.3s; font-size: 1.5rem; }
        .profile-avatar-container:hover .avatar-overlay { opacity: 1; }

        .profile-form { display: grid; gap: 24px; max-width: 800px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; font-size: 0.95rem; }
        .form-group input { width: 100%; padding: 14px 16px; border: 1px solid #e0e0e0; border-radius: 10px; font-size: 1rem; color: #333; transition: 0.2s; background: white; }
        .form-group input:disabled { background: #fafafa; border-color: #eee; color: #777; cursor: default; }
        .form-group input:focus { border-color: var(--primary-color); outline: none; box-shadow: 0 0 0 3px rgba(252, 128, 25, 0.1); }
        
         @media (max-width: 768px) { header { padding: 0 20px; } .content-container { padding: 20px; } }
         
         /* Password Toggle Styles */
         .password-wrapper { position: relative; width: 100%; }
         .password-wrapper input { padding-right: 40px; } /* Space for icon */
         .toggle-password {
             position: absolute;
             right: 15px;
             top: 50%;
             transform: translateY(-50%);
             cursor: pointer;
             color: #aaa;
             font-size: 1rem;
             z-index: 10;
         }
         .toggle-password:hover { color: var(--primary-color); }

     /* Modal Styles */
         .modal-overlay {
             position: fixed; top: 0; left: 0; width: 100%; height: 100%;
             background: rgba(0,0,0,0.6); z-index: 1000;
             display: none; justify-content: center; align-items: center;
             backdrop-filter: blur(4px);
         }
         .modal-content {
             background: white; width: 90%; max-width: 550px;
             padding: 40px; border-radius: 20px; 
             box-shadow: 0 20px 50px rgba(0,0,0,0.2);
             position: relative; animation: slideDown 0.3s ease-out;
             border: 1px solid rgba(0,0,0,0.05);
         }
         @keyframes slideDown { from { transform: translateY(-40px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
         
         .close-modal {
             position: absolute; top: 25px; right: 25px; 
             font-size: 1.5rem; cursor: pointer; color: #888;
             transition: 0.2s;
             width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 50%;
         }
         .close-modal:hover { color: #d32f2f; background: #fff5f5; }

         /* Enhanced Form Styles inside Modal */
         .modal-content h3 { font-size: 1.6rem; color: #333; margin-bottom: 30px; letter-spacing: -0.5px; }
         .modal-content .form-group { margin-bottom: 25px; }
         .modal-content label { font-size: 0.95rem; margin-bottom: 10px; color: #555; font-weight: 500; }
         .modal-content input { 
             padding: 14px 16px; 
             border: 1px solid #e0e0e0; 
             border-radius: 10px; 
             background-color: #f9f9f9; 
             font-size: 1rem;
             transition: all 0.2s;
         }
         .modal-content input:focus {
             background-color: #fff;
             border-color: var(--primary-color);
             box-shadow: 0 0 0 4px rgba(252, 128, 25, 0.15);
         }
         
         /* Validation Error Style */
         .validation-error {
             color: #d32f2f;
             font-size: 0.85rem;
             display: none; /* Hidden by default */
             margin-bottom: 5px;
             font-weight: 500;
             display: block; /* Will be toggled via JS */
             min-height: 20px; /* Reserve space or keep hidden? User wants it to appear. Let's start hidden in JS */
         }
         
         .modal-footer { margin-top: 35px; }
         
         /* Remove Photo Button */
         .btn-remove-photo {
             background: none; border: none; color: #d32f2f; font-size: 0.8rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 5px; padding: 5px 8px; border-radius: 4px; transition: 0.2s;
         }
         .btn-remove-photo:hover { background: #ffebee; }
    </style>
</head>
<body>
    <?php include 'customer_sidebar.php'; ?>
    <div class="main-content">
        <header>
            <div class="search-container">
                <i class="fa-solid fa-search"></i>
                <input type="text" placeholder="Search...">
            </div>

            <div class="user-info">
                <div>
                    <p style="font-weight: 700; font-size: 0.95rem; margin-bottom: 2px; color: var(--text-dark);"><?php echo $user_name; ?></p>
                    <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 500; letter-spacing: 0.5px; text-transform: uppercase;">Customer</span>
                </div>
                <div class="profile-pic">
                    <?php if($user_profile_image): ?>
                        <img src="<?php echo $user_profile_image; ?>" alt="Profile">
                    <?php else: ?>
                        <?php echo $user_initials; ?>
                    <?php endif; ?>
                </div>
            </div>
        </header>
        <div class="content-container">
            <div class="page-header"><h2>My Overview</h2></div>
            
            <!-- Moved from Dashboard -->
            <div class="summary-grid">
                <div class="summary-card">
                    <div class="summary-icon"><i class="fa-solid fa-box"></i></div>
                    <div class="summary-info">
                        <h3><?php echo $total_orders; ?></h3>
                        <p>Total Orders</p>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="summary-icon"><i class="fa-solid fa-clock"></i></div>
                    <div class="summary-info">
                        <h3><?php echo $active_orders; ?></h3>
                        <p>Active Orders</p>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="summary-icon"><i class="fa-solid fa-calendar-days"></i></div>
                    <div class="summary-info">
                        <h3><?php echo $scheduled_orders; ?></h3>
                        <p>Scheduled</p>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="summary-icon"><i class="fa-solid fa-wallet"></i></div>
                    <div class="summary-info">
                        <h3>₹<?php echo number_format($total_spent); ?></h3>
                        <p>Total Spent</p>
                    </div>
                </div>
            </div>

            <div class="page-header">
                <h2>Profile Details</h2>
            </div>
            
            <?php if ($update_msg): ?>
                <div style="margin-bottom: 20px; color: green; background: #e8f5e9; padding: 15px; border-radius: 8px; border-left: 5px solid green;"><?php echo $update_msg; ?></div>
            <?php endif; ?>
            <?php if ($update_err): ?>
                <div style="margin-bottom: 20px; color: #d32f2f; background: #ffebee; padding: 15px; border-radius: 8px; border-left: 5px solid #d32f2f;"><?php echo $update_err; ?></div>
            <?php endif; ?>

            <div class="profile-card">
                <div class="profile-header" style="justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 24px;">
                        
                        <div class="profile-image-section">
                            <form id="photoForm" method="POST" enctype="multipart/form-data">
                                <input type="file" name="profile_photo" id="profileUpload" style="display: none;" onchange="document.getElementById('photoForm').submit();">
                                <div class="profile-avatar-container" onclick="document.getElementById('profileUpload').click();">
                                    <div class="profile-avatar">
                                        <?php if($user_profile_image): ?>
                                            <img src="<?php echo $user_profile_image; ?>" alt="Profile">
                                        <?php else: ?>
                                            <?php echo $user_initials; ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="avatar-overlay">
                                        <i class="fa-solid fa-camera"></i>
                                    </div>
                                </div>
                            </form>
                            
                            <?php if($user_profile_image): ?>
                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete your profile photo?');">
                                    <input type="hidden" name="delete_photo" value="1">
                                    <button type="submit" class="btn-remove-photo">
                                        <i class="fa-solid fa-trash-can"></i> Remove
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>

                        <div class="profile-avatar" style="display: none;"><?php echo $user_initials; ?></div> <!-- hidden backup -->
                        <div>
                            <h3><?php echo $user_name; ?></h3>
                            <p style="color: #7f8c8d;">Customer</p>
                        </div>
                    </div>

                    <button id="editProfileBtn" onclick="enableEditMode()" style="background: var(--brand-green); color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 8px; height: fit-content;">
                        <i class="fa-solid fa-pen-to-square"></i> Edit Profile
                    </button>
                </div>
                
                <form class="profile-form" id="profileForm" method="POST">
                    <input type="hidden" name="update_profile" value="1">
                    
                    <div class="form-group">
                        <label>Full Name</label>
                        <span id="name-error" class="validation-error" style="display:none;"></span>
                        <input type="text" name="full_name" id="fullName" value="<?php echo $user_name; ?>" disabled required>
                    </div>
                    
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" value="<?php echo $user_email; ?>" disabled style="background-color: #f0f0f0; cursor: not-allowed; opacity: 0.7;">
                        <small style="color: #999; margin-top: 4px; display: block;">Email cannot be changed.</small>
                    </div>
                    
                    <!-- Password Placeholder (UX Only) -->
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" value="********" disabled style="background-color: #f0f0f0; cursor: not-allowed; opacity: 0.7;">
                        <button type="button" onclick="openPasswordModal()" style="margin-top: 10px; background: none; border: none; color: var(--primary-color); font-weight: 600; cursor: pointer; text-decoration: underline; font-size: 0.9rem;">Change Password</button>
                    </div>

                    <div class="form-group">
                        <label>Phone Number</label>
                        <span id="phone-error" class="validation-error" style="display:none;"></span>
                        <input type="text" name="phone" id="phone" value="<?php echo htmlspecialchars($user_phone); ?>" disabled required pattern="[0-9]{10}">
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label>Street Address</label>
                            <input type="text" name="street" id="street" value="<?php echo htmlspecialchars($user_street); ?>" disabled required>
                        </div>
                        <div class="form-group">
                            <label>City</label>
                            <input type="text" name="city" id="city" value="<?php echo htmlspecialchars($user_city); ?>" disabled required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Pincode</label>
                        <span id="pincode-error" class="validation-error" style="display:none;"></span>
                        <input type="text" name="pincode" id="pincode" value="<?php echo htmlspecialchars($user_pincode); ?>" disabled required pattern="\d{5,6}">
                    </div>

                    <!-- Edit Actions (Hidden by default) -->
                    <div id="editActions" style="display: none; gap: 10px; margin-top: 20px;">
                        <button type="submit" style="background: var(--brand-green); color: white; border: none; padding: 12px 25px; border-radius: 8px; font-weight: 600; cursor: pointer;">Save Changes</button>
                        <button type="button" onclick="cancelEditMode()" style="background: #e0e0e0; color: #333; border: none; padding: 12px 25px; border-radius: 8px; font-weight: 600; cursor: pointer;">Cancel</button>
                    </div>
                </form>

                <hr style="margin: 40px 0; border: 0; border-top: 1px solid #eee;">

                <!-- Change Password Section -->
                <!-- Change Password Modal -->
                <div id="passwordModal" class="modal-overlay">
                    <div class="modal-content">
                        <span class="close-modal" onclick="closePasswordModal()"><i class="fa-solid fa-times"></i></span>
                        <h3 style="font-size: 1.4rem; font-weight: 700; margin-bottom: 20px; color: var(--text-dark);">Change Password</h3>
                        
                        <?php if ($pass_msg): ?>
                            <div style="margin-bottom: 15px; color: green; font-weight: 500; font-size: 0.9rem; background: #e8f5e9; padding: 10px; border-radius: 6px;"><?php echo $pass_msg; ?></div>
                        <?php endif; ?>
                        <?php if ($pass_err): ?>
                            <div style="margin-bottom: 15px; color: red; font-weight: 500; font-size: 0.9rem; background: #ffebee; padding: 10px; border-radius: 6px;"><?php echo $pass_err; ?></div>
                        <?php endif; ?>

                        <form id="passwordForm" method="POST">
                            <input type="hidden" name="change_password" value="1">
                            <div class="form-group">
                                <label>Current Password</label>
                                <div class="password-wrapper">
                                    <input type="password" name="current_password" id="current_password" required placeholder="Enter current password">
                                    <i class="fa-solid fa-eye toggle-password" onclick="togglePassword(this, 'current_password')"></i>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>New Password</label>
                                <span id="new-pass-error" class="validation-error" style="display:none;"></span>
                                <div class="password-wrapper">
                                    <input type="password" name="new_password" id="new_password" required minlength="6" placeholder="Min 6 chars">
                                    <i class="fa-solid fa-eye toggle-password" onclick="togglePassword(this, 'new_password')"></i>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Confirm New Password</label>
                                <span id="confirm-pass-error" class="validation-error" style="display:none;"></span>
                                <div class="password-wrapper">
                                    <input type="password" name="confirm_password" id="confirm_password" required placeholder="Confirm new password">
                                    <i class="fa-solid fa-eye toggle-password" onclick="togglePassword(this, 'confirm_password')"></i>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" style="background: var(--brand-green); color: white; border: none; padding: 15px; border-radius: 10px; font-weight: 600; cursor: pointer; width: 100%; font-size: 1.05rem; box-shadow: 0 4px 15px rgba(10, 143, 8, 0.25); transition: 0.2s;">
                                    Update Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        function toggleSidebar(){document.querySelector('.sidebar').classList.toggle('collapsed');}

        function enableEditMode() {
            const inputs = document.querySelectorAll('#profileForm input:not([type="hidden"]):not([type="email"]):not([type="password"])');
            inputs.forEach(input => {
                input.removeAttribute('disabled');
                input.style.backgroundColor = '#fff';
                input.style.borderColor = '#27ae60';
            });
            document.getElementById('editActions').style.display = 'flex';
            document.getElementById('editProfileBtn').style.display = 'none';
        }

        function cancelEditMode() {
            // Reload to reset values
            location.reload();
        }

        function togglePassword(icon, fieldId) {
            const input = document.getElementById(fieldId);
            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                input.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }

        const modal = document.getElementById('passwordModal');

        function openPasswordModal() {
            modal.style.display = 'flex';
        }

        function closePasswordModal() {
            modal.style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                closePasswordModal();
            }
        }

        // Auto-open if error
        <?php if ($pass_err || $pass_msg): ?>
            openPasswordModal();
        <?php endif; ?>

        // Form Validation Logic
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            const name = document.getElementById('fullName').value;
            const phone = document.getElementById('phone').value;
            const pincode = document.getElementById('pincode').value;
            let errorMessage = "";

            // Name Validation (Letters and spaces only)
            if (!/^[a-zA-Z\s]+$/.test(name)) {
                errorMessage += "Name can only contain letters and spaces.\n";
            }

            // Phone Validation (10 digits)
            if (!/^\d{10}$/.test(phone)) {
                errorMessage += "Phone number must be exactly 10 digits.\n";
            }

            // Pincode Validation (6 digits)
            if (!/^\d{6}$/.test(pincode)) {
                errorMessage += "Pincode must be exactly 6 digits.\n";
            }

            if (errorMessage) {
                e.preventDefault();
                alert(errorMessage);
            }
        });

        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            const newPass = document.getElementById('new_password').value;
            const confirmPass = document.getElementById('confirm_password').value;
            let errorMessage = "";

            if (newPass.length < 6) {
                errorMessage += "New password must be at least 6 characters long.\n";
            }

            if (newPass !== confirmPass) {
                errorMessage += "New passwords do not match.\n";
            }

            if (errorMessage) {
                e.preventDefault();
                alert(errorMessage);
            }
        });
        // Real-Time Validation Logic
        const validateInput = (input, errorSpan, validator) => {
            input.addEventListener('input', () => {
                const errorMessage = validator(input.value);
                if (errorMessage) {
                    errorSpan.textContent = errorMessage;
                    errorSpan.style.display = 'block';
                    input.style.borderColor = '#d32f2f';
                } else {
                    errorSpan.style.display = 'none';
                    input.style.borderColor = '#e0e0e0';
                }
            });
        };

        // Profile Validations
        validateInput(
            document.getElementById('fullName'), 
            document.getElementById('name-error'), 
            (value) => /[^a-zA-Z\s]/.test(value) ? "Name can only contain letters and spaces." : ""
        );

        validateInput(
            document.getElementById('phone'), 
            document.getElementById('phone-error'), 
            (value) => {
                if (/\D/.test(value)) return "Phone number must contain only digits.";
                if (value.length > 10) return "Phone number cannot exceed 10 digits.";
                return "";
            }
        );

        validateInput(
            document.getElementById('pincode'), 
            document.getElementById('pincode-error'), 
            (value) => {
                if (/\D/.test(value)) return "Pincode must contain only digits.";
                if (value.length > 6) return "Pincode cannot exceed 6 digits.";
                return "";
            }
        );

        // Password Validations
        const newPassInput = document.getElementById('new_password');
        const confirmPassInput = document.getElementById('confirm_password');
        
        validateInput(
            newPassInput,
            document.getElementById('new-pass-error'),
            (value) => value.length > 0 && value.length < 6 ? "Password must be at least 6 characters." : ""
        );

        validateInput(
            confirmPassInput,
            document.getElementById('confirm-pass-error'),
            (value) => value && value !== newPassInput.value ? "Passwords do not match." : ""
        );
        // Also re-validate confirm password when new password changes
        newPassInput.addEventListener('input', () => {
             const confirmVal = confirmPassInput.value;
             const errorSpan = document.getElementById('confirm-pass-error');
             if (confirmVal && confirmVal !== newPassInput.value) {
                 errorSpan.textContent = "Passwords do not match.";
                 errorSpan.style.display = 'block';
             } else {
                 errorSpan.style.display = 'none';
             }
        });
        
        // Form Submission Blockers (reuse existing alerts but also check for visible errors?)
        // The existing listeners are fine, they do a final check.
    </script>    <script>function toggleSidebar(){document.querySelector('.sidebar').classList.toggle('collapsed');}</script>
</body>
</html>
