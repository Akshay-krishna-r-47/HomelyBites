<?php
/**
 * reset_password.php
 * Validates reset token and allows user to update password.
 */
session_start();
include 'db_connect.php';

$message = "";
$message_type = "";
$token_valid = false;
$user_id = null;

if (isset($_GET['token'])) {
    $token = $conn->real_escape_string($_GET['token']);
    $now = date("Y-m-d H:i:s");

    // Validate token and expiry
    $sql = "SELECT user_id FROM users WHERE reset_token = '$token' AND reset_expiry > '$now'";
    $result = $conn->query($sql);

    if ($result->num_rows == 1) {
        $token_valid = true;
        $row = $result->fetch_assoc();
        $user_id = $row['user_id'];
    } else {
        $message = "Invalid or expired reset token.";
        $message_type = "error";
    }
} else {
    $message = "No reset token provided.";
    $message_type = "error";
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $token_valid) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $message = "Passwords do not match.";
        $message_type = "error";
    } elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters long.";
        $message_type = "error";
    } else {
        // Hash new password and clear token fields
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $update_sql = "UPDATE users SET password = '$hashed_password', reset_token = NULL, reset_expiry = NULL WHERE user_id = $user_id";

        if ($conn->query($update_sql)) {
            $message = "Password updated successfully! You can now login.";
            $message_type = "success";
            $token_valid = false; // Prevent resubmission
        } else {
            $message = "Error updating password: " . $conn->error;
            $message_type = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Homely Bites</title>
    <link rel="stylesheet" href="assets/css/style.css?v=1.2">
</head>
<body style="min-height: 100vh; margin: 0; padding: 0; background-color: #f8f9fa; display: flex; justify-content: center; align-items: center; font-family: 'Lato', sans-serif;">
    <div class="form-tile" style="width: 100%; max-width: 450px; background: #ffffff; padding: 40px; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
        <div style="text-align: center; margin-bottom: 30px;">
            <h1 style="font-weight: 900; font-size: 2rem; color: #2C3E50; margin: 0; margin-bottom: 8px;">Reset Password</h1>
            <p style="font-size: 0.95rem; color: #7f8c8d; margin: 0;">Set a new password for your account.</p>
        </div>

        <?php if ($message): ?>
            <div class="message-box" style="padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 0.9rem; text-align: center; border: 1px solid; 
                background: <?php echo ($message_type == 'success') ? '#e8f5e9' : '#ffebee'; ?>; 
                color: <?php echo ($message_type == 'success') ? '#2e7d32' : '#c62828'; ?>; 
                border-color: <?php echo ($message_type == 'success') ? '#a5d6a7' : '#ffcdd2'; ?>;">
                <?php echo $message; ?>
            </div>
            <?php if ($message_type == 'success'): ?>
                <div style="text-align: center; margin-top: 10px;">
                    <a href="login.php" style="color: #3e5a32; text-decoration: none; font-weight: 700;">Proceed to Login</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($token_valid): ?>
            <form method="POST" action="">
                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="password" style="display: block; color: #2C3E50; font-weight: 700; margin-bottom: 6px; font-size: 0.9rem;">New Password</label>
                    <input type="password" id="password" name="password" placeholder="Min 6 characters" required style="width: 100%; padding: 12px; border: 1px solid #e0e0e0; border-radius: 8px; background: #fcfcfc; font-size: 0.95rem; outline: none;">
                </div>

                <div class="form-group" style="margin-bottom: 25px;">
                    <label for="confirm_password" style="display: block; color: #2C3E50; font-weight: 700; margin-bottom: 6px; font-size: 0.9rem;">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Repeat password" required style="width: 100%; padding: 12px; border: 1px solid #e0e0e0; border-radius: 8px; background: #fcfcfc; font-size: 0.95rem; outline: none;">
                </div>

                <button type="submit" class="btn" style="width: 100%; border-radius: 8px; background-color: #3e5a32; color: white; border: none; padding: 14px; font-size: 1rem; font-weight: 700; cursor: pointer;">
                    Update Password
                </button>
            </form>
        <?php endif; ?>

        <?php if (!$token_valid && $message_type !== 'success'): ?>
             <p style="text-align: center; margin-top: 25px; font-size: 0.9rem; color: #7f8c8d;">
                <a href="forgot_password.php" style="color: #3e5a32; text-decoration: none; font-weight: 700;">Request a new reset link</a>
            </p>
        <?php endif; ?>
    </div>
</body>
</html>
