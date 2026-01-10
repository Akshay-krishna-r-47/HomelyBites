<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Customer') {
    header("Location: login.php");
    exit();
}
include 'db_connect.php';

$user_id = $_SESSION['user_id'];
$user_name = htmlspecialchars($_SESSION['name']);
$message = "";
$message_type = "";
$existing_application = null;

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_application'])) {
    $business_name = $conn->real_escape_string($_POST['business_name']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $address = $conn->real_escape_string($_POST['address']);
    $kitchen_type = $conn->real_escape_string($_POST['kitchen_type']);
    $food_type = $conn->real_escape_string($_POST['food_type']);
    $experience = intval($_POST['experience']);

    // Check for duplicate
    $check_stmt = $conn->prepare("SELECT application_id FROM seller_applications WHERE user_id = ? AND status IN ('Pending', 'Approved')");
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $check_stmt->store_result();
    
    if ($check_stmt->num_rows > 0) {
        $message = "You already have an active application.";
        $message_type = "error";
    } else {
        $stmt = $conn->prepare("INSERT INTO seller_applications (user_id, business_name, phone_number, address, kitchen_type, food_type, experience_years) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssssi", $user_id, $business_name, $phone, $address, $kitchen_type, $food_type, $experience);
        
        if ($stmt->execute()) {
            $message = "Application submitted successfully! It is now under review.";
            $message_type = "success";
        } else {
            $message = "Error submitting application. Please try again.";
            $message_type = "error";
        }
        $stmt->close();
    }
    $check_stmt->close();
}

// Fetch Existing Application Status
$stmt = $conn->prepare("SELECT * FROM seller_applications WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $existing_application = $result->fetch_assoc();
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Become a Seller - Homely Bites</title>
    <link href="https://fonts.googleapis.com/css2?family=Lemon&family=Lato:wght@300;400;700&family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary-color: #27ae60; --brand-green: #008000; --bg-body: #fdfbf7; --card-bg: #FFFFFF; --text-dark: #2c3e50; --border-radius: 16px; --header-height: 80px; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Lato', sans-serif; }
        body { background-color: var(--bg-body); color: var(--text-dark); display: flex; min-height: 100vh; }
        @import url('assets/css/style.css'); /* Ensuring consistent styles */
        
        /* Layout */
        .main-content { flex: 1; display: flex; flex-direction: column; width: 0; transition: all 0.4s; }
        header { height: var(--header-height); background-color: var(--card-bg); padding: 0 40px; display: flex; align-items: center; justify-content: flex-end; position: sticky; top: 0; z-index: 900; border-bottom: 1px solid rgba(0,0,0,0.06); }
        .content-container { padding: 40px 50px; width: 100%; max-width: 1000px; margin: 0 auto; }
        
        .page-header h2 { font-family: 'Playfair Display', serif; font-size: 2rem; margin-bottom: 20px; }
        
        .form-card {
            background-color: var(--card-bg);
            padding: 40px;
            border-radius: var(--border-radius);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border: 1px solid rgba(0,0,0,0.06);
        }

        .form-group { margin-bottom: 20px; }
        
        label { 
            display: block; 
            margin-bottom: 8px; 
            font-weight: 700; 
            color: var(--text-dark);
            font-size: 0.95rem;
        }
        
        input[type="text"], input[type="number"], select, textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        input:focus, select:focus, textarea:focus {
            border-color: var(--primary-color);
            outline: none;
        }

        .btn-submit {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 14px 28px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.3s;
            width: 100%;
            margin-top: 10px;
        }
        
        .btn-submit:hover { background-color: #219150; }
        .btn-submit:disabled { background-color: #bdc3c7; cursor: not-allowed; }

        .status-box {
            text-align: center;
            padding: 40px;
        }
        
        .status-icon {
            font-size: 4rem;
            margin-bottom: 20px;
        }
        
        .status-pending { color: #f39c12; }
        .status-success { color: #27ae60; }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .alert.success { background-color: #d4edda; color: #155724; }
        .alert.error { background-color: #f8d7da; color: #721c24; }

    </style>
</head>
<body>
    <?php include 'customer_sidebar.php'; ?>
    <div class="main-content">
        <header>
            <div style="text-align: right; margin-right: 15px;">
                <p style="font-weight: 700;"><?php echo $user_name; ?></p>
                <span style="font-size: 0.8rem; color: #888;">Customer</span>
            </div>
            <div style="width: 40px; height: 40px; background: #ddd; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                <i class="fa-solid fa-user"></i>
            </div>
        </header>

        <div class="content-container">
            <div class="page-header"><h2>Become a Seller</h2></div>
            
            <?php if ($message): ?>
                <div class="alert <?php echo $message_type; ?>"><?php echo $message; ?></div>
            <?php endif; ?>

            <div class="form-card">
                <?php if ($existing_application && $existing_application['status'] == 'Pending'): ?>
                    <!-- Pending Status View -->
                    <div class="status-box">
                        <i class="fa-solid fa-clock-rotate-left status-icon status-pending"></i>
                        <h3>Application Under Review</h3>
                        <p style="color: #7f8c8d; margin-top: 10px;">
                            Thanks for applying! Your application for <strong><?php echo htmlspecialchars($existing_application['business_name']); ?></strong> is currently being reviewed by our admin team.
                        </p>
                    </div>
                
                <?php elseif ($existing_application && $existing_application['status'] == 'Approved'): ?>
                     <!-- Approved Status View -->
                     <div class="status-box">
                        <i class="fa-solid fa-check-circle status-icon status-success"></i>
                        <h3>You are a Seller!</h3>
                        <p style="color: #7f8c8d; margin-top: 10px;">
                            Your application has been approved. Please logout and login again to access your Seller Dashboard.
                        </p>
                    </div>

                <?php else: ?>
                    <!-- Application Form -->
                    <form method="POST" action="">
                        <div class="form-group">
                            <label>Kitchen / Business Name</label>
                            <input type="text" name="business_name" required placeholder="e.g. Grandma's Kitchen">
                        </div>
                        
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="text" name="phone" required placeholder="Enter your contact number">
                        </div>
                        
                        <div class="form-group">
                            <label>Full Address</label>
                            <textarea name="address" rows="3" required placeholder="Where will you be cooking from?"></textarea>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div class="form-group">
                                <label>Kitchen Type</label>
                                <select name="kitchen_type" required>
                                    <option value="Home Kitchen">Home Kitchen</option>
                                    <option value="Restaurant">Restaurant / Cloud Kitchen</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Cooking Experience (Years)</label>
                                <input type="number" name="experience" min="0" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Types of Food You Will Sell</label>
                            <select name="food_type" required>
                                <option value="Veg">Vegetarian Only</option>
                                <option value="Non-Veg">Non-Vegetarian Only</option>
                                <option value="Both">Both Veg & Non-Veg</option>
                            </select>
                        </div>

                        <div class="form-group" style="display: flex; gap: 10px; align-items: start; margin-top: 30px;">
                            <input type="checkbox" required style="width: auto; margin-top: 5px;">
                            <p style="font-size: 0.9rem; color: #666;">I agree to the Terms & Conditions and certify that I adhere to all food safety standards.</p>
                        </div>

                        <button type="submit" name="submit_application" class="btn-submit">Submit Application</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script>function toggleSidebar(){document.querySelector('.sidebar').classList.toggle('collapsed');}</script>
</body>
</html>
