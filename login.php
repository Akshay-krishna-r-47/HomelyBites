<?php
session_start();
ob_start(); // Buffer output to prevent headers/warnings from breaking JSON
include 'db_connect.php';

// Disable caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Redirect if already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    if (isset($_POST['ajax']) && $_POST['ajax'] === '1') {
        ob_end_clean();
        $redirect = "index.php";
        switch ($_SESSION['role']) {
            case 'Customer': $redirect = "customer_dashboard.php"; break;
            case 'Seller': $redirect = "seller_dashboard.php"; break;
            case 'Delivery': $redirect = "delivery_dashboard.php"; break;
            case 'Admin': $redirect = "admin_dashboard.php"; break;
        }
        echo json_encode(['status' => 'success', 'redirect' => $redirect]);
        exit();
    } else {
        switch ($_SESSION['role']) {
            case 'Customer': header("Location: customer_dashboard.php"); exit();
            case 'Seller': header("Location: seller_dashboard.php"); exit();
            case 'Delivery': header("Location: delivery_dashboard.php"); exit();
            case 'Admin': header("Location: admin_dashboard.php"); exit();
            default: header("Location: index.php"); exit();
        }
    }
}

$error = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    $is_ajax = isset($_POST['ajax']) && $_POST['ajax'] === '1';
    
    $response = ['status' => 'error', 'message' => ''];

    if (empty($email) || empty($password)) {
        $response['message'] = "Please enter both email and password.";
        if (!$is_ajax) $error = $response['message'];
    } elseif ($email === 'admin@gmail.com' && $password === 'admin') {
        // ADMIN LOGIN SUCCESS
        $_SESSION['user_id'] = 'admin';
        $_SESSION['name'] = 'Admin';
        $_SESSION['role'] = 'Admin';
        $_SESSION['email'] = 'admin@gmail.com';

        if ($is_ajax) {
            ob_end_clean();
            echo json_encode(['status' => 'success', 'redirect' => 'admin_dashboard.php']);
            exit();
        } else {
            header("Location: admin_dashboard.php");
            exit();
        }
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = "Invalid email format.";
        if (!$is_ajax) $error = $response['message'];
    } else {
        $sql = "SELECT user_id, name, email, password, role FROM users WHERE email = '$email' AND status = 'Active'";
        $result = $conn->query($sql);

        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            if (password_verify($password, $row['password'])) {
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['name'] = $row['name'];
                $_SESSION['role'] = $row['role'];
                $_SESSION['email'] = $row['email'];

                $redirect = "index.php";
                switch ($row['role']) {
                    case 'Customer': $redirect = "customer_dashboard.php"; break;
                    case 'Seller': $redirect = "seller_dashboard.php"; break;
                    case 'Delivery': $redirect = "delivery_dashboard.php"; break;
                    case 'Admin': $redirect = "admin_dashboard.php"; break;
                    default: $redirect = "index.php"; break;
                }

                if ($is_ajax) {
                    ob_end_clean();
                    echo json_encode(['status' => 'success', 'redirect' => $redirect]);
                    exit();
                } else {
                    header("Location: " . $redirect);
                    exit();
                }
            } else {
                $response['message'] = "Invalid password.";
                if (!$is_ajax) $error = $response['message'];
            }
        } else {
            $response['message'] = "User not found.";
            if (!$is_ajax) $error = $response['message'];
        }
    }

    if ($is_ajax) {
        ob_end_clean(); // Discard any prior output (warnings, whitespace)
        echo json_encode($response);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Homely Bites</title>
    <!-- Added version parameter to force cache refresh -->
    <link rel="stylesheet" href="assets/css/style.css?v=1.2">
    <link href="https://fonts.cdnfonts.com/css/pecita" rel="stylesheet">
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
<body style="min-height: 100vh; margin: 0; padding: 0; background-color: #ffffff; display: flex; justify-content: center; align-items: center; font-family: 'Lato', sans-serif;">
    
    <!-- Full Screen Split Layout -->
    <div class="split-screen-container" style="display: flex; width: 100%; height: 100vh; overflow: hidden;">
        
        <!-- Left Side (Background + Floating Form Tile - 50% Width) -->
        <div class="split-left-form" style="flex: 1; display: flex; flex-direction: column; justify-content: center; align-items: center; background: #f8f9fa; padding: 20px;">
            
            <!-- Floating Form Tile (Compact Size) -->
            <div class="form-tile" style="width: 100%; max-width: 450px; background: #ffffff; padding: 40px; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
                
                <div style="text-align: center; margin-bottom: 30px;">
                    <h1 style="font-family: 'Lato', sans-serif; font-weight: 900; font-size: 2.5rem; color: #2C3E50; margin: 0; margin-bottom: 8px;">LOGIN</h1>
                    <p style="font-family: 'Lato', sans-serif; font-size: 0.95rem; color: #7f8c8d; margin: 0;">Welcome back! Please login to your account.</p>
                </div>
                
                <div id="loginError" class="error-msg" style="background: #ffebee; color: #c62828; padding: 10px; border-radius: 6px; margin-bottom: 20px; font-size: 0.9rem; text-align: center; border: 1px solid #ffcdd2; display: <?php echo $error ? 'block' : 'none'; ?>;">
                    <?php echo $error; ?>
                </div>

                <form id="loginForm" method="POST" action="" novalidate>
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label for="email" style="display: block; color: #2C3E50; font-weight: 700; margin-bottom: 6px; font-size: 0.9rem;">Email</label>
                        <input type="email" id="email" name="email" placeholder="Enter your email" value="<?php echo htmlspecialchars($email); ?>" required style="width: 100%; padding: 12px; border: 1px solid #e0e0e0; border-radius: 8px; background: #fcfcfc; font-size: 0.95rem; outline: none; transition: border 0.3s;">
                        <span class="error-feedback" id="emailError">Please enter a valid email address</span>
                    </div>

                    <div class="form-group" style="margin-bottom: 25px;">
                        <label for="password" style="display: block; color: #2C3E50; font-weight: 700; margin-bottom: 6px; font-size: 0.9rem;">Password</label>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required style="width: 100%; padding: 12px; border: 1px solid #e0e0e0; border-radius: 8px; background: #fcfcfc; font-size: 0.95rem; outline: none; transition: border 0.3s;">
                        <span class="error-feedback" id="passwordError">Password is required</span>
                    </div>

                    <div class="form-group" style="margin-bottom: 20px; width: 100%; padding-left: 0; display: flex; justify-content: space-between; align-items: center;">
                        <label style="font-size: 0.9rem; display: flex; align-items: center; cursor: pointer; color: #555; justify-content: flex-start; padding: 0; margin: 0; text-transform: none;">
                            <input type="checkbox" style="margin: 0 8px 0 0; width: auto !important; transform: scale(1);"> Remember me
                        </label>
                        <a href="forgot_password.php" style="font-size: 0.9rem; color: #3e5a32; text-decoration: none; font-weight: 600;">Forgot Password?</a>
                    </div>

                    <button type="submit" class="btn" style="width: 100%; border-radius: 8px; background-color: #3e5a32; color: white; border: none; padding: 14px; font-size: 1rem; font-weight: 700; cursor: pointer; text-transform: none; box-shadow: 0 3px 5px rgba(0,0,0,0.1);">
                        Login
                    </button>
                    
                    <!-- 'Or' Separator -->
                    <div style="display: flex; align-items: center; margin: 25px 0;">
                        <div style="flex: 1; height: 1px; background: #e0e0e0;"></div>
                        <span style="padding: 0 10px; color: #999; font-size: 0.85rem;">Or</span>
                        <div style="flex: 1; height: 1px; background: #e0e0e0;"></div>
                    </div>

                    <!-- Google Sign In -->
                    <button type="button" onclick="location.href='google_login.php'" style="width: 100%; padding: 12px; border: 1px solid #e0e0e0; background: #fff; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 0.95rem; cursor: pointer; transition: background 0.3s; color: #555; font-weight: 600;">
                        <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" alt="Google" style="width: 18px; height: 18px; margin-right: 10px;">
                        Continue with Google
                    </button>

                    <p style="text-align: center; margin-top: 25px; font-size: 0.9rem; color: #7f8c8d;">
                        Don't have an account? <a href="register.php" style="color: #3e5a32; text-decoration: none; font-weight: 700;">Create an account</a>
                    </p>
                </form>
            </div>
        </div>

        <!-- Right Side (Image - 50% Width) -->
        <div class="split-right-image" style="flex: 1; background: url('loginpageimage.jpg') center center / cover no-repeat; position: relative;">
            <div style="position: absolute; top:0; left:0; width:100%; height:100%; background: linear-gradient(to right, rgba(0,0,0,0.2), rgba(0,0,0,0));"></div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Check if page is loaded from cache (back button)
            window.addEventListener('pageshow', function(event) {
                if (event.persisted || (window.performance && window.performance.navigation.type === 2)) {
                    window.location.reload();
                }
            });

            const form = document.getElementById('loginForm');
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');

            const validateEmail = () => {
                const value = emailInput.value.trim();
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                const errorSpan = document.getElementById('emailError');

                emailInput.classList.remove('valid', 'invalid');
                errorSpan.style.display = 'none';

                if (value === "") {
                    emailInput.classList.add('invalid');
                    errorSpan.textContent = "Email is required.";
                    errorSpan.style.display = 'block';
                    return false;
                } else if (!emailRegex.test(value)) {
                    emailInput.classList.add('invalid');
                    errorSpan.textContent = "Enter a valid email address.";
                    errorSpan.style.display = 'block';
                    return false;
                } else {
                    emailInput.classList.add('valid');
                    return true;
                }
            };

            const validatePassword = () => {
                const value = passwordInput.value;
                const errorSpan = document.getElementById('passwordError');

                passwordInput.classList.remove('valid', 'invalid');
                errorSpan.style.display = 'none';

                if (value === "") {
                    passwordInput.classList.add('invalid');
                    errorSpan.textContent = "Password is required.";
                    errorSpan.style.display = 'block';
                    return false;
                } else {
                    passwordInput.classList.add('valid');
                    return true;
                }
            };
            
            emailInput.addEventListener('input', validateEmail);
            emailInput.addEventListener('blur', validateEmail);
            passwordInput.addEventListener('input', validatePassword);
            passwordInput.addEventListener('blur', validatePassword);

            form.addEventListener('submit', (e) => {
                e.preventDefault();

                const isEmailValid = validateEmail();
                const isPasswordValid = validatePassword();

                if (!isEmailValid || !isPasswordValid) {
                    return;
                }

                const formData = new FormData(form);
                formData.append('ajax', '1');
                
                // Show loading state if desired, or just wait
                const btn = form.querySelector('button[type="submit"]');
                const originalBtnText = btn.textContent;
                btn.textContent = 'Logging in...';
                btn.disabled = true;

                fetch('login.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Use location.replace to replace login page in history
                        window.location.replace(data.redirect);
                    } else {
                        const loginError = document.getElementById('loginError');
                        loginError.textContent = data.message;
                        loginError.style.display = 'block';
                        btn.textContent = originalBtnText;
                        btn.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    const loginError = document.getElementById('loginError');
                    loginError.textContent = "An error occurred. Please try again.";
                    loginError.style.display = 'block';
                    btn.textContent = originalBtnText;
                    btn.disabled = false;
                });
            });
        });
    </script>
</body>
</html>
