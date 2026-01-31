<?php
include 'role_check.php';
check_role_access('customer');
include 'db_connect.php';
include_once 'helpers.php';

$user_id = $_SESSION['user_id'];
$user_name = htmlspecialchars($_SESSION['name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed - Homely Bites</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary-color: #fc8019; --brand-green: #0a8f08; --bg-body: #f8f8f8; --text-dark: #222; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: var(--bg-body); color: var(--text-dark); display: flex; min-height: 100vh; }
        .main-content { flex: 1; padding: 40px; display: flex; justify-content: center; align-items: center; }
        
        .confirmation-card { 
            background: white; 
            padding: 50px; 
            border-radius: 16px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.08); 
            text-align: center; 
            max-width: 500px; 
            width: 100%; 
        }
        
        .success-icon { 
            font-size: 5rem; 
            color: var(--brand-green); 
            margin-bottom: 25px; 
            animation: popIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        
        h1 { margin-bottom: 15px; color: #222; }
        p { color: #666; margin-bottom: 30px; line-height: 1.6; }
        
        .btn-group { display: flex; flex-direction: column; gap: 15px; }
        
        .btn-primary { 
            background: var(--brand-green); 
            color: white; 
            padding: 15px; 
            border-radius: 8px; 
            text-decoration: none; 
            font-weight: 600; 
            transition: 0.3s; 
        }
        .btn-primary:hover { background: #087f06; }
        
        .btn-secondary { 
            background: #f1f1f1; 
            color: #555; 
            padding: 15px; 
            border-radius: 8px; 
            text-decoration: none; 
            font-weight: 600; 
            transition: 0.3s; 
        }
        .btn-secondary:hover { background: #e5e5e5; }
        
        @keyframes popIn {
            0% { transform: scale(0); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }
    </style>
</head>
<body>
    <?php include 'customer_sidebar.php'; ?>
    <div class="main-content">
        <div class="confirmation-card">
            <i class="fa-solid fa-circle-check success-icon"></i>
            <h1>Order Placed!</h1>
            <p>Thank you for your order, <strong><?php echo $user_name; ?></strong>! Your delicious food will be with you shortly.</p>
            
            <div class="btn-group">
                <a href="customer_orders.php" class="btn-primary">View My Orders</a>
                <a href="customer_dashboard.php" class="btn-secondary">Continue Shopping</a>
            </div>
        </div>
    </div>
    <script>function toggleSidebar(){ document.querySelector('.sidebar').classList.toggle('collapsed'); }</script>
</body>
</html>
