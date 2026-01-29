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
include_once 'helpers.php';
$user_id = $_SESSION['user_id'];
$user_name = htmlspecialchars($_SESSION['name']);
$user_initials = getAvatarInitials($_SESSION['name']);
$user_profile_image = getProfileImage($user_id, $conn);

$message = "";
$message_type = ""; // success or error
$application_status = null;

// Initialize form variables
$phone_val = "";
$vehicle_type_val = "";
$vehicle_number_val = "";
$license_number_val = "";
$availability_val = "";

// Check existing application status
$check_sql = "SELECT * FROM delivery_applications WHERE user_id = ? ORDER BY created_at DESC LIMIT 1";
$stmt = $conn->prepare($check_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_app = $stmt->get_result();
$existing_app = $result_app->fetch_assoc();
$stmt->close();

if ($existing_app) {
    // If status is rejected, we allow resubmission. Pre-fill data if not a POST request.
    if ($existing_app['status'] == 'Rejected' && $_SERVER["REQUEST_METHOD"] != "POST") {
        $application_status = 'Rejected';
        $phone_val = $existing_app['phone'];
        $vehicle_type_val = $existing_app['vehicle_type'];
        $vehicle_number_val = $existing_app['vehicle_number'];
        $license_number_val = $existing_app['license_number'];
        $availability_val = $existing_app['availability'];
    } elseif ($existing_app['status'] == 'Pending') {
        $application_status = 'Pending';
    } elseif ($existing_app['status'] == 'Approved') {
       $application_status = 'Approved';
    }
}

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && ($application_status === null || $application_status === 'Rejected')) {
    $phone_val = trim($_POST['phone']);
    $vehicle_type_val = trim($_POST['vehicle_type']);
    $vehicle_number_val = trim($_POST['vehicle_number']);
    $license_number_val = trim($_POST['license_number']);
    $availability_val = trim($_POST['availability']);

    if (empty($phone_val) || empty($vehicle_type_val) || empty($vehicle_number_val) || empty($license_number_val) || empty($availability_val)) {
         $message = "All fields are required.";
         $message_type = "error";
    } elseif (!preg_match("/^[0-9]{10}$/", $phone_val)) {
        $message = "Phone number must be exactly 10 digits.";
        $message_type = "error";
    } else {

        $insert_sql = "INSERT INTO delivery_applications (user_id, phone, vehicle_type, vehicle_number, license_number, availability, status) VALUES (?, ?, ?, ?, ?, ?, 'Pending')";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("isssss", $user_id, $phone_val, $vehicle_type_val, $vehicle_number_val, $license_number_val, $availability_val);
        
        if ($insert_stmt->execute()) {
            $message = "Application submitted successfully! Please wait for admin approval.";
            $message_type = "success";
            $application_status = 'Pending'; // Update status for immediate UI reflection
            // Clear values
             $phone_val = ""; $vehicle_type_val = ""; $vehicle_number_val = ""; $license_number_val = ""; $availability_val = "";
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
    <!-- Fonts matching Customer Dashboard -->
    <link href="https://fonts.googleapis.com/css2?family=Lemon&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
         :root {
            --primary-color: #0a8f08; /* Brand Green */
            --bg-body: #f8f8f8;
            --card-bg: #FFFFFF;
            --text-dark: #222;
            --text-muted: #666;
            --header-height: 80px;
            --shadow-card: 0 4px 14px rgba(0,0,0,0.08);
            --border-radius: 16px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        
        body { background-color: var(--bg-body); color: var(--text-dark); display: flex; min-height: 100vh; }
        
        /* Layout */
        .main-content { flex: 1; display: flex; flex-direction: column; width: 0; transition: all 0.4s; }

        /* Header - Matches Dashboard */
        header {
            height: var(--header-height);
            background-color: var(--card-bg);
            padding: 0 40px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            position: sticky;
            top: 0;
            z-index: 900;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
        }

        .user-info { display: flex; align-items: center; gap: 15px; text-align: right; }
        .profile-pic { width: 40px; height: 40px; background: #eee; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.9rem; color: #555; overflow: hidden; object-fit: cover; }
        .profile-pic img { width: 100%; height: 100%; object-fit: cover; }

        /* Content Container */
        .content-container { padding: 40px 60px; max-width: 900px; margin: 0 auto; width: 100%; }

        /* Page Headings */
        .page-header { margin-bottom: 30px; text-align: center; }
        .page-header h2 { font-size: 28px; font-weight: 700; color: #222; margin-bottom: 8px; }
        .page-header p { font-size: 15px; color: #666; font-weight: 500; }

        /* Form Card */
        .form-card {
            background: var(--card-bg);
            padding: 40px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-card);
            border: 1px solid rgba(0,0,0,0.03);
        }
        
        .form-group { margin-bottom: 24px; }
        
        label { 
            display: block; 
            margin-bottom: 8px; 
            font-weight: 600; 
            color: #333;
            font-size: 0.9rem;
        }

        input, select, textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 0.95rem;
            color: #333;
            transition: border-color 0.2s, box-shadow 0.2s;
            background: #fff;
        }
        
        input:focus, select:focus, textarea:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(10, 143, 8, 0.1);
        }
        
        /* Buttons */
        .btn-submit {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 14px 28px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            width: 100%;
            margin-top: 10px;
        }
        
        .btn-submit:hover { 
            background-color: #087f06; 
            transform: translateY(-1px);
        }

        /* Status Box */
        .status-box { text-align: center; padding: 40px 20px; }
        .status-icon { font-size: 3.5rem; margin-bottom: 20px; }
        .status-pending { color: #f39c12; }
        .status-rejected { color: #d32f2f; }
        
        .alert {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-weight: 500;
            font-size: 0.95rem;
            border: 1px solid transparent;
        }
        .alert-success { background-color: #e6f8e7; color: #0a8f08; border-color: #c3e6cb; }
        .alert-error { background-color: #fce8e8; color: #d32f2f; border-color: #f5c6cb; }

        /* Grid */
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        @media (max-width: 600px) { .grid-2 { grid-template-columns: 1fr; } }
        
        /* Validation Error Style */
        .validation-error {
            color: #d32f2f;
            font-size: 0.85rem;
            display: none;
            margin-bottom: 5px;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <?php include 'customer_sidebar.php'; ?>
    <div class="main-content">
        <header>
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
            <div class="page-header">
                <h2>Become a Delivery Partner</h2>
                <p>Join our delivery fleet and start earning today</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if ($application_status === 'Pending'): ?>
                <!-- Pending Status in Card -->
                <div class="form-card">
                    <div class="status-box">
                        <div class="status-icon status-pending"><i class="fa-solid fa-hourglass-half"></i></div>
                        <h3 style="font-size: 1.5rem; color: #333; margin-bottom: 10px;">Application Under Review</h3>
                        <p style="color: #666; max-width: 600px; margin: 0 auto;">
                            Your application is currently being reviewed by our team. Please check back later.
                        </p>
                    </div>
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
                        <div class="grid-2">
                            <div class="form-group">
                                <label>Phone Number</label>
                                <span id="phone-error" class="validation-error"></span>
                                <input type="tel" name="phone" id="phone" placeholder="e.g. +91 9876543210" required value="<?php echo htmlspecialchars($phone_val); ?>">
                            </div>
                            <div class="form-group">
                                <label>Vehicle Type</label>
                                <select name="vehicle_type" required>
                                    <option value="">Select Vehicle</option>
                                    <option value="Bike" <?php echo ($vehicle_type_val == 'Bike') ? 'selected' : ''; ?>>Bike / Motorcycle</option>
                                    <option value="Scooter" <?php echo ($vehicle_type_val == 'Scooter') ? 'selected' : ''; ?>>Scooter</option>
                                    <option value="Bicycle" <?php echo ($vehicle_type_val == 'Bicycle') ? 'selected' : ''; ?>>Bicycle</option>
                                    <option value="Electric Vehicle" <?php echo ($vehicle_type_val == 'Electric Vehicle') ? 'selected' : ''; ?>>Electric Vehicle</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid-2">
                             <div class="form-group">
                                <label>Vehicle Number</label>
                                <span id="vehicle-error" class="validation-error"></span>
                                <input type="text" name="vehicle_number" id="vehicle_number" placeholder="e.g. AB-01-CD-1234" required value="<?php echo htmlspecialchars($vehicle_number_val); ?>">
                            </div>
                            <div class="form-group">
                                <label>Driving License Number</label>
                                <span id="license-error" class="validation-error"></span>
                                <input type="text" name="license_number" id="license_number" placeholder="Enter License Number" required value="<?php echo htmlspecialchars($license_number_val); ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Availability (Days/Hours)</label>
                            <input type="text" name="availability" placeholder="e.g. Weekends, 9AM - 5PM" required value="<?php echo htmlspecialchars($availability_val); ?>">
                        </div>
                        
                        <div class="form-group" style="display: flex; gap: 12px; align-items: flex-start; margin-top: 10px;">
                            <input type="checkbox" required style="width: auto; margin-top: 4px; border: 1px solid #ccc;">
                            <p style="font-size: 0.9rem; color: #666; line-height: 1.5;">I agree to the Terms & Conditions and possess a valid driving license.</p>
                        </div>

                        <button type="submit" class="btn-submit">Submit Application</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script>
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('collapsed');
        }

        // Real-Time Validation Logic
        const validateInput = (input, errorSpan, validator) => {
            if(!input) return;
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
            document.getElementById('license_number'), 
            document.getElementById('license-error'), 
            (value) => value.length < 5 ? "License number seems too short." : ""
        );

        validateInput(
            document.getElementById('vehicle_number'), 
            document.getElementById('vehicle-error'), 
            (value) => value.length < 4 ? "Vehicle number seems too short." : ""
        );

        // Form Validation Submit
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                const phone = document.querySelector('input[name="phone"]').value;
                const license = document.querySelector('input[name="license_number"]').value;
                let errorMessage = "";

                if (!/^\d{10}$/.test(phone)) {
                    errorMessage += "Phone number must be exactly 10 digits.\n";
                }
                
                if(license.length < 5) {
                    errorMessage += "Please enter a valid license number.\n";
                }

                if (errorMessage) {
                    e.preventDefault();
                    alert(errorMessage);
                }
            });
        }
    </script>

</body>
</html>
