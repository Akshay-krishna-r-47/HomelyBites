<?php
/**
 * forgot_password.php
 * Handles password reset requests.
 */
session_start();
include 'db_connect.php';

// Import PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Manually include PHPMailer files
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

$message = "";
$message_type = ""; // Success or Error

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $conn->real_escape_string($_POST['email']);

    // 1. Check if user exists and is a 'Normal' login type
    $sql = "SELECT user_id, name, login_type FROM users WHERE email = '$email'";
    $result = $conn->query($sql);

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();

        if ($user['login_type'] !== 'Normal') {
            $message = "Accounts registered via Google cannot reset passwords here. Please use Google Sign-In.";
            $message_type = "error";
        } else {
            // 2. Generate secure token and expiry
            $token = bin2hex(random_bytes(32));
            $expiry = date("Y-m-d H:i:s", strtotime("+15 minutes"));

            // 3. Store in database
            $update_sql = "UPDATE users SET reset_token = '$token', reset_expiry = '$expiry' WHERE email = '$email'";
            if ($conn->query($update_sql)) {
                
                // 4. Send Email using PHPMailer
                $mail = new PHPMailer(true);

                try {
                    // Server settings
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'iamgroot7541@gmail.com'; // Your Gmail address
                    $mail->Password   = 'zicb zfyu tlxi dqpw';   // Your App Password
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;

                    // Recipients
                    $mail->setFrom('iamgroot7541@gmail.com', 'Homely Bites');
                    $mail->addAddress($email, $user['name']);

                    // Content
                    $reset_link = "http://localhost/homelybites/reset_password.php?token=" . $token;
                    $mail->isHTML(true);
                    $mail->Subject = 'Password Reset Request - Homely Bites';
                    $mail->Body    = "
                        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 10px;'>
                            <h2 style='color: #3e5a32; text-align: center;'>Reset Your Password</h2>
                            <p>Hi " . htmlspecialchars($user['name']) . ",</p>
                            <p>We received a request to reset your password for your Homely Bites account. Click the button below to proceed:</p>
                            <div style='text-align: center; margin: 30px 0;'>
                                <a href='$reset_link' style='background-color: #3e5a32; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Reset Password</a>
                            </div>
                            <p>This link will expire in 15 minutes.</p>
                            <p>If you didn't request this, you can safely ignore this email.</p>
                            <hr style='border: 0; border-top: 1px solid #eeeeee;'>
                            <p style='font-size: 0.8rem; color: #7f8c8d; text-align: center;'>&copy; 2024 Homely Bites. All rights reserved.</p>
                        </div>
                    ";

                    $mail->send();
                    $message = "A password reset link has been sent to your email.";
                    $message_type = "success";
                } catch (Exception $e) {
                    $message = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
                    $message_type = "error";
                }
            } else {
                $message = "Error updating database: " . $conn->error;
                $message_type = "error";
            }
        }
    } else {
        $message = "Email address not found.";
        $message_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Homely Bites</title>
    <link rel="stylesheet" href="assets/css/style.css?v=1.2">
</head>
<body style="min-height: 100vh; margin: 0; padding: 0; background-color: #f8f9fa; display: flex; justify-content: center; align-items: center; font-family: 'Lato', sans-serif;">
    <div class="form-tile" style="width: 100%; max-width: 450px; background: #ffffff; padding: 40px; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
        <div style="text-align: center; margin-bottom: 30px;">
            <h1 style="font-weight: 900; font-size: 2rem; color: #2C3E50; margin: 0; margin-bottom: 8px;">Forgot Password?</h1>
            <p style="font-size: 0.95rem; color: #7f8c8d; margin: 0;">Enter your email to receive a reset link.</p>
        </div>

        <?php if ($message): ?>
            <div class="message-box" style="padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 0.9rem; text-align: center; border: 1px solid; 
                background: <?php echo ($message_type == 'success') ? '#e8f5e9' : '#ffebee'; ?>; 
                color: <?php echo ($message_type == 'success') ? '#2e7d32' : '#c62828'; ?>; 
                border-color: <?php echo ($message_type == 'success') ? '#a5d6a7' : '#ffcdd2'; ?>;">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group" style="margin-bottom: 25px;">
                <label for="email" style="display: block; color: #2C3E50; font-weight: 700; margin-bottom: 6px; font-size: 0.9rem;">Registered Email</label>
                <input type="email" id="email" name="email" placeholder="Enter your email" required style="width: 100%; padding: 12px; border: 1px solid #e0e0e0; border-radius: 8px; background: #fcfcfc; font-size: 0.95rem; outline: none;">
            </div>

            <button type="submit" class="btn" style="width: 100%; border-radius: 8px; background-color: #3e5a32; color: white; border: none; padding: 14px; font-size: 1rem; font-weight: 700; cursor: pointer;">
                Send Reset Link
            </button>

            <p style="text-align: center; margin-top: 25px; font-size: 0.9rem; color: #7f8c8d;">
                Remembered your password? <a href="login.php" style="color: #3e5a32; text-decoration: none; font-weight: 700;">Back to Login</a>
            </p>
        </form>
    </div>
</body>
</html>
