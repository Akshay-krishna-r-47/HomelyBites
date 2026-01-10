<?php
session_start();
include 'db_connect.php';

$error = "";
$success = "";

// Initialize variables to keep form data
$name = "";
$street = "";
$city = "";
$pincode = "";
$email = "";
$phone = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $conn->real_escape_string($_POST['name']);
    // Default values
    $dob = '2000-01-01'; 
    $gender = 'Other';
    $role = 'Customer';
    
    $street = $conn->real_escape_string($_POST['street']);
    $city = $conn->real_escape_string($_POST['city']);
    $pincode = $conn->real_escape_string($_POST['pincode']);
    $email = $conn->real_escape_string($_POST['email']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Server-side Validation
    if (empty($name) || empty($street) || empty($city) || empty($pincode) || empty($email) || empty($phone) || empty($password)) {
        $error = "All fields are required.";
    } elseif (!preg_match("/^[a-zA-Z\s]+$/", $name)) {
        $error = "Name can only contain letters and spaces.";
    } elseif (!preg_match("/^[a-zA-Z\s]+$/", $city)) {
        $error = "City can only contain letters and spaces.";
    } elseif (!preg_match("/^[a-zA-Z0-9\s,.\-#\/]+$/", $street)) {
        $error = "Invalid characters in address.";
    } elseif (!preg_match("/^\d{5,6}$/", $pincode)) {
        $error = "Enter a valid pincode (5-6 digits).";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (!preg_match("/^[0-9]{10}$/", $phone)) {
        $error = "Phone number must be exactly 10 digits.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } else {
        // Check if email or phone already exists
        $check_sql = "SELECT user_id FROM users WHERE email = '$email' OR phone = '$phone'";
        $result = $conn->query($check_sql);

        if ($result->num_rows > 0) {
            $error = "Email or Phone already registered!";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (name, date_of_birth, gender, street, city, pincode, email, phone, password, role) 
                    VALUES ('$name', '$dob', '$gender', '$street', '$city', '$pincode', '$email', '$phone', '$hashed_password', '$role')";

            if ($conn->query($sql) === TRUE) {
                // Redirect to login page on success
                header("Location: login.php");
                exit();
            } else {
                $error = "Error: " . $sql . "<br>" . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Homely Bites</title>
    <!-- Version parameter for cache busting -->
    <link rel="stylesheet" href="assets/css/style.css?v=1.3">
    <link href="https://fonts.cdnfonts.com/css/pecita" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        .error-feedback {
            color: #e74c3c;
            font-size: 0.8rem;
            margin-top: 5px;
            display: none;
        }
        .form-group input.invalid {
            border-color: #e74c3c;
        }
        .form-group input.valid {
            border-color: #2ecc71;
        }
    </style>
</head>
<body style="min-height: 100vh; margin: 0; padding: 0; background-color: #ffffff; font-family: 'Lato', sans-serif;">

    <!-- Full Screen Split Layout -->
    <div class="split-screen-container" style="display: flex; width: 100%; height: 100vh; overflow: hidden;">
        
        <!-- Left Side (Form - Scrollable) -->
        <div class="split-left-form" style="flex: 1; display: flex; flex-direction: column; align-items: center; background: #f8f9fa; padding: 40px; overflow-y: auto;">
            
            <!-- Floating Form Tile (Compact Size) -->
            <div class="form-tile" style="width: 100%; max-width: 450px; background: #ffffff; padding: 40px; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); margin-top: auto; margin-bottom: auto;">
                
                <div style="text-align: center; margin-bottom: 30px;">
                    <a href="index.php" style="text-decoration: none;">
                        <h1 style="font-family: 'Lato', sans-serif; font-weight: 900; font-size: 2.5rem; color: #2C3E50; margin: 0; margin-bottom: 8px;">REGISTER</h1>
                    </a>
                    <p style="font-family: 'Lato', sans-serif; font-size: 0.95rem; color: #7f8c8d; margin: 0;">Create your account to get started.</p>
                </div>

                <?php if ($error): ?>
                    <div class="error-msg" style="display:block; background: #ffebee; color: #c62828; padding: 10px; border-radius: 6px; margin-bottom: 20px; font-size: 0.9rem; text-align: center; border: 1px solid #ffcdd2;"><?php echo $error; ?></div>
                <?php endif; ?>

                <form id="registerForm" method="POST" action="" novalidate>
                    
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label for="name" style="display: block; color: #2C3E50; font-weight: 700; margin-bottom: 6px; font-size: 0.9rem;">Full Name</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required style="width: 100%; padding: 12px; border: 1px solid #e0e0e0; border-radius: 8px; background: #fcfcfc; font-size: 0.95rem; outline: none; transition: border 0.3s;">
                        <span class="error-feedback" id="nameError">Name is required</span>
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label for="street" style="display: block; color: #2C3E50; font-weight: 700; margin-bottom: 6px; font-size: 0.9rem;">Street Address</label>
                        <input type="text" id="street" name="street" value="<?php echo htmlspecialchars($street); ?>" required style="width: 100%; padding: 12px; border: 1px solid #e0e0e0; border-radius: 8px; background: #fcfcfc; font-size: 0.95rem; outline: none; transition: border 0.3s;">
                        <span class="error-feedback" id="streetError">Street address is required</span>
                    </div>

                    <div style="display: flex; gap: 15px; margin-bottom: 20px;">
                        <div class="form-group" style="flex: 1;">
                            <label for="city" style="display: block; color: #2C3E50; font-weight: 700; margin-bottom: 6px; font-size: 0.9rem;">City</label>
                            <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($city); ?>" required style="width: 100%; padding: 12px; border: 1px solid #e0e0e0; border-radius: 8px; background: #fcfcfc; font-size: 0.95rem; outline: none; transition: border 0.3s;">
                            <span class="error-feedback" id="cityError">City is required</span>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label for="pincode" style="display: block; color: #2C3E50; font-weight: 700; margin-bottom: 6px; font-size: 0.9rem;">Pincode</label>
                            <input type="text" id="pincode" name="pincode" value="<?php echo htmlspecialchars($pincode); ?>" required style="width: 100%; padding: 12px; border: 1px solid #e0e0e0; border-radius: 8px; background: #fcfcfc; font-size: 0.95rem; outline: none; transition: border 0.3s;">
                            <span class="error-feedback" id="pincodeError">Valid pincode required</span>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label for="email" style="display: block; color: #2C3E50; font-weight: 700; margin-bottom: 6px; font-size: 0.9rem;">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required style="width: 100%; padding: 12px; border: 1px solid #e0e0e0; border-radius: 8px; background: #fcfcfc; font-size: 0.95rem; outline: none; transition: border 0.3s;">
                        <span class="error-feedback" id="emailError">Please enter a valid email address</span>
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label for="phone" style="display: block; color: #2C3E50; font-weight: 700; margin-bottom: 6px; font-size: 0.9rem;">Phone</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>" required style="width: 100%; padding: 12px; border: 1px solid #e0e0e0; border-radius: 8px; background: #fcfcfc; font-size: 0.95rem; outline: none; transition: border 0.3s;">
                        <span class="error-feedback" id="phoneError">Phone number must be 10 digits</span>
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label for="password" style="display: block; color: #2C3E50; font-weight: 700; margin-bottom: 6px; font-size: 0.9rem;">Password</label>
                        <input type="password" id="password" name="password" required style="width: 100%; padding: 12px; border: 1px solid #e0e0e0; border-radius: 8px; background: #fcfcfc; font-size: 0.95rem; outline: none; transition: border 0.3s;">
                        <span class="error-feedback" id="passwordError">Password must be at least 6 characters</span>
                    </div>

                    <div class="form-group" style="margin-bottom: 25px;">
                        <label for="confirm_password" style="display: block; color: #2C3E50; font-weight: 700; margin-bottom: 6px; font-size: 0.9rem;">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required style="width: 100%; padding: 12px; border: 1px solid #e0e0e0; border-radius: 8px; background: #fcfcfc; font-size: 0.95rem; outline: none; transition: border 0.3s;">
                        <span class="error-feedback" id="confirmPasswordError">Passwords do not match</span>
                    </div>

                    <button type="submit" class="btn" style="width: 100%; border-radius: 8px; background-color: #3e5a32; color: white; border: none; padding: 14px; font-size: 1rem; font-weight: 700; cursor: pointer; text-transform: none; box-shadow: 0 3px 5px rgba(0,0,0,0.1);">
                        Register
                    </button>
                    
                    <p style="text-align: center; margin-top: 25px; font-size: 0.9rem; margin-bottom: 0;">
                        Already have an account? <a href="login.php" style="color: #3e5a32; text-decoration: none; font-weight: 700;">Sign In</a>
                    </p>
                </form>
            </div>
        </div>
        
        <!-- Right Side (Image - 50% Width) -->
        <div class="split-right-image" style="flex: 1; background: url('loginpageimage.jpg') center center / cover no-repeat; position: relative;">
            <div style="position: absolute; top:0; left:0; width:100%; height:100%; background: linear-gradient(to right, rgba(0,0,0,0.2), rgba(0,0,0,0));"></div>
        </div>
    </div>

    <!-- JavaScript Validation -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('registerForm');
            const fields = ['name', 'street', 'city', 'pincode', 'email', 'phone', 'password', 'confirm_password'];

            fields.forEach(field => {
                const input = document.getElementById(field);
                if (input) {
                    input.addEventListener('input', () => validateField(field));
                    input.addEventListener('blur', () => validateField(field));
                }
            });

            form.addEventListener('submit', (e) => {
                let isValid = true;
                fields.forEach(field => {
                    if (!validateField(field)) {
                        isValid = false;
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                }
            });

            function validateField(fieldId) {
                const input = document.getElementById(fieldId);
                const errorSpan = document.getElementById(fieldId + 'Error');
                const value = input.value.trim();
                let isValid = true;
                let errorMessage = "";

                // Reset styles
                input.classList.remove('valid', 'invalid');
                errorSpan.style.display = 'none';

                if (value === "" && fieldId !== 'confirm_password') {
                     // Confirm password handles empty check separately below
                     isValid = false;
                     errorMessage = "This field is required.";
                } else {
                    switch(fieldId) {
                        case 'name':
                            if (!/^[a-zA-Z\s]+$/.test(value)) {
                                isValid = false;
                                errorMessage = "Name can only contain letters and spaces.";
                            }
                            break;
                        case 'street':
                            if (!/^[a-zA-Z0-9\s,.\-#\/]+$/.test(value)) {
                                isValid = false;
                                errorMessage = "Invalid characters in address.";
                            }
                            break;
                        case 'city':
                            if (!/^[a-zA-Z\s]+$/.test(value)) {
                                isValid = false;
                                errorMessage = "City can only contain letters and spaces.";
                            }
                            break;
                        case 'pincode':
                            // Allow 5 or 6 digits
                            if (!/^\d{5,6}$/.test(value)) {
                                isValid = false;
                                errorMessage = "Enter a valid pincode (5-6 digits).";
                            }
                            break;
                        case 'email':
                            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                            if (!emailRegex.test(value)) {
                                isValid = false;
                                errorMessage = "Enter a valid email address.";
                            }
                            break;
                        case 'phone':
                            if (!/^\d{10}$/.test(value)) {
                                isValid = false;
                                errorMessage = "Phone number must be exactly 10 digits.";
                            }
                            break;
                        case 'password':
                            if (value.length < 6) {
                                isValid = false;
                                errorMessage = "Password must be at least 6 characters.";
                            }
                            break;
                        case 'confirm_password':
                            const passwordVal = document.getElementById('password').value;
                            if (value === "") {
                                isValid = false;
                                errorMessage = "Please confirm your password.";
                            } else if (value !== passwordVal) {
                                isValid = false;
                                errorMessage = "Passwords do not match.";
                            }
                            break;
                    }
                }

                if (!isValid) {
                    input.classList.add('invalid');
                    errorSpan.textContent = errorMessage;
                    errorSpan.style.display = 'block';
                } else {
                    input.classList.add('valid');
                }

                return isValid;
            }
        });
    </script>
</body>
</html>
