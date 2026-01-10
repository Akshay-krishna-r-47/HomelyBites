<?php
/**
 * complete_profile.php
 * Collects mandatory address details for Google-authenticated users.
 */
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $street = $conn->real_escape_string($_POST['street']);
    $city = $conn->real_escape_string($_POST['city']);
    $pincode = $conn->real_escape_string($_POST['pincode']);

    $update_sql = "UPDATE users SET street = '$street', city = '$city', pincode = '$pincode' WHERE user_id = $user_id";

    if ($conn->query($update_sql)) {
        header("Location: customer_dashboard.php");
        exit();
    } else {
        $error = "Error updating profile: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Profile - Homely Bites</title>
    <link rel="stylesheet" href="assets/css/style.css?v=1.2">
</head>
<body style="min-height: 100vh; margin: 0; padding: 0; background-color: #f8f9fa; display: flex; justify-content: center; align-items: center; font-family: 'Lato', sans-serif;">
    <div class="form-tile" style="width: 100%; max-width: 450px; background: #ffffff; padding: 40px; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
        <div style="text-align: center; margin-bottom: 30px;">
            <h1 style="font-weight: 900; font-size: 2rem; color: #2C3E50; margin: 0; margin-bottom: 8px;">One Last Step!</h1>
            <p style="font-size: 0.95rem; color: #7f8c8d; margin: 0;">Please provide your address to complete your profile.</p>
        </div>

        <?php if ($error): ?>
            <div class="error-msg" style="background: #ffebee; color: #c62828; padding: 10px; border-radius: 6px; margin-bottom: 20px; font-size: 0.9rem; text-align: center; border: 1px solid #ffcdd2; display: block;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group" style="margin-bottom: 20px;">
                <label for="street" style="display: block; color: #2C3E50; font-weight: 700; margin-bottom: 6px; font-size: 0.9rem;">Street Address</label>
                <input type="text" id="street" name="street" placeholder="e.g. 123 Main St" required style="width: 100%; padding: 12px; border: 1px solid #e0e0e0; border-radius: 8px; background: #fcfcfc; font-size: 0.95rem; outline: none;">
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label for="city" style="display: block; color: #2C3E50; font-weight: 700; margin-bottom: 6px; font-size: 0.9rem;">City</label>
                <input type="text" id="city" name="city" placeholder="e.g. New York" required style="width: 100%; padding: 12px; border: 1px solid #e0e0e0; border-radius: 8px; background: #fcfcfc; font-size: 0.95rem; outline: none;">
            </div>

            <div class="form-group" style="margin-bottom: 25px;">
                <label for="pincode" style="display: block; color: #2C3E50; font-weight: 700; margin-bottom: 6px; font-size: 0.9rem;">Pincode / ZIP</label>
                <input type="text" id="pincode" name="pincode" placeholder="e.g. 10001" required style="width: 100%; padding: 12px; border: 1px solid #e0e0e0; border-radius: 8px; background: #fcfcfc; font-size: 0.95rem; outline: none;">
            </div>

            <button type="submit" class="btn" style="width: 100%; border-radius: 8px; background-color: #3e5a32; color: white; border: none; padding: 14px; font-size: 1rem; font-weight: 700; cursor: pointer;">
                Finish Setup
            </button>
        </form>
    </div>
</body>
</html>
