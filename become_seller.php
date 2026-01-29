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
include_once 'helpers.php';

$user_id = $_SESSION['user_id'];
$user_name = htmlspecialchars($_SESSION['name']);
$user_initials = getAvatarInitials($_SESSION['name']);
$user_profile_image = getProfileImage($user_id, $conn);

$message = "";
$message_type = "";
$existing_application = null;

// Initialize form variables
$business_name_val = "";
$phone_val = "";
$address_val = "";
$kitchen_type_val = "";
$food_type_val = "";
$experience_val = "";

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_application'])) {
    $business_name_val = trim($_POST['business_name']);
    $phone_val = trim($_POST['phone']);
    $address_val = trim($_POST['address']);
    $kitchen_type_val = $_POST['kitchen_type'];
    $food_type_val = $_POST['food_type'];
    $experience_val = intval($_POST['experience']);

    // Validation
    if (empty($business_name_val) || empty($phone_val) || empty($address_val)) {
        $message = "All fields are required.";
        $message_type = "error";
    } elseif (!preg_match("/^[0-9]{10}$/", $phone_val)) {
        $message = "Phone number must be exactly 10 digits.";
        $message_type = "error";
    } elseif ($experience_val < 0) {
        $message = "Experience cannot be negative.";
        $message_type = "error";
    } else {
        $business_name_safe = $conn->real_escape_string($business_name_val);
        $phone_safe = $conn->real_escape_string($phone_val);
        $address_safe = $conn->real_escape_string($address_val);


    // Check for duplicate
    $check_stmt = $conn->prepare("SELECT application_id FROM seller_applications WHERE user_id = ? AND status IN ('Pending', 'Approved')");
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $check_stmt->store_result();
    
        if ($check_stmt->num_rows > 0) {
            $message = "You already have an active application.";
            $message_type = "error";
        } else {
            $stmt = $conn->prepare("INSERT INTO seller_applications (user_id, business_name, phone_number, address, kitchen_type, food_type, experience_years, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')");
            $stmt->bind_param("isssssi", $user_id, $business_name_safe, $phone_safe, $address_safe, $kitchen_type_val, $food_type_val, $experience_val);

        
            if ($stmt->execute()) {
                $message = "Application submitted successfully! It is now under review.";
                $message_type = "success";
                // Clear values preventing re-display on success
                 $business_name_val = ""; $phone_val = ""; $address_val = ""; $experience_val = "";
            } else {
                $message = "Error submitting application. Please try again.";
                $message_type = "error";
            }
            $stmt->close();
        }
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
    // Pre-fill if rejected and no POST submission (or empty POST submission)
    if ($existing_application['status'] == 'Rejected' && $_SERVER["REQUEST_METHOD"] != "POST") {
        $business_name_val = $existing_application['business_name'];
        $phone_val = $existing_application['phone_number'];
        $address_val = $existing_application['address'];
        $kitchen_type_val = $existing_application['kitchen_type'];
        $food_type_val = $existing_application['food_type'];
        $experience_val = $existing_application['experience_years'];
        
        $message = "Your previous application was rejected. You can edit and resubmit below.";
        $message_type = "error"; 
    }
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Become a Seller - Homely Bites</title>
    <!-- Fonts matching Customer Dashboard -->
    <link href="https://fonts.googleapis.com/css2?family=Lemon&family=Poppins:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
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
            justify-content: flex-end; /* Just User Info here, no search needed for this form page */
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
        
        input[type="text"], input[type="number"], select, textarea {
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
        .status-success { color: #0a8f08; }
        
        .alert {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-weight: 500;
            font-size: 0.95rem;
            border: 1px solid transparent;
        }
        .alert.success { background-color: #e6f8e7; color: #0a8f08; border-color: #c3e6cb; }
        .alert.error { background-color: #fce8e8; color: #d32f2f; border-color: #f5c6cb; }

        /* Grid for 2 columns */
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
                <h2>Become a Seller</h2>
                <p>Start your culinary journey with Homely Bites</p>
            </div>
            
            <?php if ($message): ?>
                <div class="alert <?php echo $message_type; ?>"><?php echo $message; ?></div>
            <?php endif; ?>

            <div class="form-card">
                <?php if ($existing_application && $existing_application['status'] == 'Pending'): ?>
                    <!-- Pending Status View -->
                    <div class="status-box">
                        <i class="fa-solid fa-clock-rotate-left status-icon status-pending"></i>
                        <h3 style="font-size: 1.5rem; color: #333; margin-bottom: 10px;">Application Under Review</h3>
                        <p style="color: #666; max-width: 600px; margin: 0 auto;">
                            Thanks for applying! Your application for <strong><?php echo htmlspecialchars($existing_application['business_name']); ?></strong> is currently being reviewed by our admin team.
                        </p>
                    </div>
                
                <?php elseif ($existing_application && $existing_application['status'] == 'Approved'): ?>
                     <!-- Approved Status View -->
                     <div class="status-box">
                        <i class="fa-solid fa-check-circle status-icon status-success"></i>
                        <h3 style="font-size: 1.5rem; color: #333; margin-bottom: 10px;">You are a Seller!</h3>
                        <p style="color: #666;">
                            Your application has been approved. Please logout and login again to access your Seller Dashboard.
                        </p>
                    </div>

                <?php else: ?>
                    <!-- Application Form -->
                    <form method="POST" action="">
                        <div class="form-group">
                            <label>Kitchen / Business Name</label>
                            <span id="business-error" class="validation-error"></span>
                            <input type="text" name="business_name" id="business_name" required placeholder="e.g. Grandma's Kitchen" value="<?php echo htmlspecialchars($business_name_val); ?>">
                        </div>
                        
                        <div class="grid-2">
                             <div class="form-group">
                                <label>Phone Number</label>
                                <span id="phone-error" class="validation-error"></span>
                                <input type="text" name="phone" id="phone" required placeholder="Enter your contact number" pattern="[0-9]{10}" value="<?php echo htmlspecialchars($phone_val); ?>">
                            </div>
                            <div class="form-group">
                                <label>Cooking Experience (Years)</label>
                                <span id="exp-error" class="validation-error"></span>
                                <input type="number" name="experience" id="experience" min="0" required placeholder="0" value="<?php echo htmlspecialchars($experience_val); ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Full Address</label>
                            <textarea name="address" rows="3" required placeholder="Where will you be cooking from?"><?php echo htmlspecialchars($address_val); ?></textarea>
                        </div>
                        
                        <div class="grid-2">
                            <div class="form-group">
                                <label>Kitchen Type</label>
                                <select name="kitchen_type" required>
                                    <option value="Home Kitchen" <?php echo ($kitchen_type_val == 'Home Kitchen') ? 'selected' : ''; ?>>Home Kitchen</option>
                                    <option value="Restaurant" <?php echo ($kitchen_type_val == 'Restaurant') ? 'selected' : ''; ?>>Restaurant / Cloud Kitchen</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Types of Food You Will Sell</label>
                                <select name="food_type" required>
                                    <option value="Veg" <?php echo ($food_type_val == 'Veg') ? 'selected' : ''; ?>>Vegetarian Only</option>
                                    <option value="Non-Veg" <?php echo ($food_type_val == 'Non-Veg') ? 'selected' : ''; ?>>Non-Vegetarian Only</option>
                                    <option value="Both" <?php echo ($food_type_val == 'Both') ? 'selected' : ''; ?>>Both Veg & Non-Veg</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group" style="display: flex; gap: 12px; align-items: flex-start; margin-top: 10px;">
                            <input type="checkbox" required style="width: auto; margin-top: 4px; border: 1px solid #ccc;">
                            <p style="font-size: 0.9rem; color: #666; line-height: 1.5;">I agree to the Terms & Conditions and certify that I adhere to all food safety standards.</p>
                        </div>

                        <button type="submit" name="submit_application" class="btn-submit">Submit Application</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('collapsed');
        }

        // Form Validation
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
            document.getElementById('experience'), 
            document.getElementById('exp-error'), 
            (value) => value < 0 ? "Experience cannot be negative." : ""
        );

        validateInput(
            document.getElementById('business_name'),
            document.getElementById('business-error'),
            (value) => value.trim().length === 0 ? "Business Name is required." : ""
        );

        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                const phone = document.querySelector('input[name="phone"]').value;
                const experience = document.querySelector('input[name="experience"]').value;
                let errorMessage = "";

                if (!/^\d{10}$/.test(phone)) {
                    errorMessage += "Phone number must be exactly 10 digits.\n";
                }

                if (parseInt(experience) < 0) {
                    errorMessage += "Experience cannot be negative.\n";
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
