<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Redirect if not logged in or not a Customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Customer') {
    // If user is already a Delivery Partner, redirect to dashboard
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'Delivery') {
        header("Location: delivery_dashboard.php");
        exit();
    }
    header("Location: login.php");
    exit();
}

include 'db_connect.php';
$user_id = $_SESSION['user_id'];
$user_name = htmlspecialchars($_SESSION['name']);
$message = "";
$message_type = ""; // success or error
$application_status = null;

// Check existing application status
$check_sql = "SELECT status FROM delivery_applications WHERE user_id = ? ORDER BY created_at DESC LIMIT 1";
$stmt = $conn->prepare($check_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($status_db);
if ($stmt->fetch()) {
    $application_status = $status_db;
}
$stmt->close();

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && ($application_status === null || $application_status === 'Rejected')) {
    $phone = trim($_POST['phone']);
    $vehicle_type = trim($_POST['vehicle_type']);
    $vehicle_number = trim($_POST['vehicle_number']);
    $license_number = trim($_POST['license_number']);
    $availability = trim($_POST['availability']);

    if (empty($phone) || empty($vehicle_type) || empty($vehicle_number) || empty($license_number) || empty($availability)) {
         $message = "All fields are required.";
         $message_type = "error";
    } else {
        $insert_sql = "INSERT INTO delivery_applications (user_id, phone, vehicle_type, vehicle_number, license_number, availability, status) VALUES (?, ?, ?, ?, ?, ?, 'Pending')";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("isssss", $user_id, $phone, $vehicle_type, $vehicle_number, $license_number, $availability);
        
        if ($insert_stmt->execute()) {
            $message = "Application submitted successfully! Please wait for admin approval.";
            $message_type = "success";
            $application_status = 'Pending'; // Update status for immediate UI reflection
        } else {
            $message = "Error submitting application. Please try again.";
            $message_type = "error";
        }
        $insert_stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Become a Delivery Partner - Homely Bites</title>
    <link href="https://fonts.googleapis.com/css2?family=Lemon&family=Lato:wght@300;400;700&family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary-color: #27ae60; --brand-green: #008000; --bg-body: #fdfbf7; --card-bg: #FFFFFF; --text-dark: #2c3e50; --text-muted: #7f8c8d; --sidebar-width: 280px; --header-height: 80px; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Lato', sans-serif; }
        body { background-color: var(--bg-body); color: var(--text-dark); display: flex; min-height: 100vh; }
        
        /* Main Layout */
        .main-content { flex: 1; display: flex; flex-direction: column; width: 100%; margin-left: 0; transition: margin-left 0.4s; }
        /* Reuse Sidebar/Header styles from customer_sidebar.php via include, but adding specific styles for form */
        header { height: var(--header-height); background-color: var(--card-bg); padding: 0 40px; display: flex; align-items: center; justify-content: flex-end; position: sticky; top: 0; z-index: 900; border-bottom: 1px solid rgba(0,0,0,0.06); }
        .content-container { padding: 40px 50px; width: 100%; max-width: 1000px; margin: 0 auto; }
        
        .page-header h2 { font-family: 'Playfair Display', serif; font-size: 2rem; margin-bottom: 10px; }
        .page-header p { color: var(--text-muted); margin-bottom: 30px; }

        .form-card { background: #fff; padding: 40px; border-radius: 16px; border: 1px solid rgba(0,0,0,0.06); box-shadow: 0 4px 20px rgba(0,0,0,0.04); }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 700; margin-bottom: 8px; color: var(--text-dark); }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px; border: 1px solid #e0e0e0; border-radius: 8px; font-size: 1rem; outline: none; transition: border-color 0.3s; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: var(--primary-color); }
        
        .btn-submit { background-color: var(--primary-color); color: white; border: none; padding: 14px 24px; border-radius: 8px; font-weight: 700; font-size: 1rem; cursor: pointer; transition: background 0.3s; width: 100%; }
        .btn-submit:hover { background-color: #219150; }

        .status-box { text-align: center; padding: 60px 20px; border-radius: 16px; background: #fff; border: 1px solid rgba(0,0,0,0.06); }
        .status-icon { font-size: 4rem; margin-bottom: 20px; display: inline-block; }
        .status-pending { color: #f39c12; }
        .status-rejected { color: #c0392b; }
        
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        @media (max-width: 768px) { .content-container { padding: 20px; } }
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
            <div class="page-header">
                <h2>Become a Delivery Partner</h2>
                <p>Join our delivery fleet and start earning today.</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if ($application_status === 'Pending'): ?>
                <div class="status-box">
                    <div class="status-icon status-pending"><i class="fa-solid fa-hourglass-half"></i></div>
                    <h3 style="font-size: 1.5rem; margin-bottom: 10px;">Application Under Review</h3>
                    <p style="color: var(--text-muted);">Your application is currently being reviewed by our team. Please check back later.</p>
                </div>
            <?php elseif ($application_status === 'Rejected'): ?>
                <div class="alert alert-error" style="text-align: center;">
                    <strong>Application Rejected</strong><br>
                    Your previous application was not approved. You can submit a new application below.
                </div>
                <!-- Show Form below -->
            <?php endif; ?>

            <?php if ($application_status === null || $application_status === 'Rejected'): ?>
                <div class="form-card">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="tel" name="phone" placeholder="e.g. +91 9876543210" required>
                        </div>
                        <div class="form-group">
                            <label>Vehicle Type</label>
                            <select name="vehicle_type" required>
                                <option value="">Select Vehicle</option>
                                <option value="Bike">Bike / Motorcycle</option>
                                <option value="Scooter">Scooter</option>
                                <option value="Bicycle">Bicycle</option>
                                <option value="Electric Vehicle">Electric Vehicle</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Vehicle Number</label>
                            <input type="text" name="vehicle_number" placeholder="e.g. AB-01-CD-1234" required>
                        </div>
                        <div class="form-group">
                            <label>Driving License Number</label>
                            <input type="text" name="license_number" placeholder="Enter License Number" required>
                        </div>
                        <div class="form-group">
                            <label>Availability (Days/Hours)</label>
                            <input type="text" name="availability" placeholder="e.g. Weekends, 9AM - 5PM" required>
                        </div>
                        <button type="submit" class="btn-submit">Submit Application</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script>function toggleSidebar(){document.querySelector('.sidebar').classList.toggle('collapsed');}</script>
</body>
</html>
