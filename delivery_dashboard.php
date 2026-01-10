<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Manual role check instead of include to ensure strict control and consistency with other files
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Delivery') {
    header("Location: login.php");
    exit();
}
$user_name = htmlspecialchars($_SESSION['name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Dashboard - Homely Bites</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Lemon&family=Lato:wght@300;400;700&family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary-color: #27ae60; --brand-green: #008000; --bg-body: #fdfbf7; --card-bg: #FFFFFF; --text-dark: #2c3e50; --text-muted: #7f8c8d; --header-height: 80px; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Lato', sans-serif; }
        body { background-color: var(--bg-body); color: var(--text-dark); min-height: 100vh; display: flex; flex-direction: column; }

        header { height: var(--header-height); background-color: var(--card-bg); padding: 0 40px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 900; border-bottom: 1px solid rgba(0,0,0,0.06); }
        .logo { font-family: 'Lemon', serif; font-size: 1.5rem; color: var(--brand-green); text-decoration: none; }
        .nav-user { display: flex; align-items: center; gap: 15px; }
        .logout-btn { color: #e74c3c; text-decoration: none; font-weight: 600; font-size: 0.9rem; border: 1px solid #e74c3c; padding: 8px 16px; border-radius: 8px; transition: all 0.3s; }
        .logout-btn:hover { background-color: #e74c3c; color: white; }

        .container { padding: 40px; max-width: 1200px; margin: 0 auto; width: 100%; }
        .welcome-card { background: linear-gradient(135deg, var(--brand-green), #27ae60); color: white; padding: 40px; border-radius: 16px; margin-bottom: 40px; box-shadow: 0 10px 30px rgba(39, 174, 96, 0.2); }
        .welcome-card h1 { font-family: 'Playfair Display', serif; font-size: 2.5rem; margin-bottom: 10px; }

        .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px; }
        .card { background: white; padding: 30px; border-radius: 16px; border: 1px solid rgba(0,0,0,0.05); text-align: center; transition: transform 0.3s; }
        .card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
        .card-icon { font-size: 3rem; color: var(--primary-color); margin-bottom: 20px; }
        .card h3 { font-size: 1.2rem; margin-bottom: 10px; }
        .card p { color: var(--text-muted); font-size: 0.9rem; }
    </style>
</head>
<body>
    <header>
        <a href="#" class="logo">Homely Bites</a>
        <div class="nav-user">
            <span>Welcome, <strong><?php echo $user_name; ?></strong></span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </header>

    <div class="container">
        <div class="welcome-card">
            <h1>Delivery Partner Dashboard</h1>
            <p>Ready to deliver smiles? View your assigned orders below.</p>
        </div>

        <div class="dashboard-grid">
            <div class="card">
                <div class="card-icon"><i class="fa-solid fa-list-check"></i></div>
                <h3>New Orders</h3>
                <p>No new orders assigned yet.</p>
            </div>
            <div class="card">
                <div class="card-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
                <h3>Delivery History</h3>
                <p>View your past deliveries.</p>
            </div>
            <div class="card">
                <div class="card-icon"><i class="fa-solid fa-user-gear"></i></div>
                <h3>My Profile</h3>
                <p>Manage your vehicle and details.</p>
            </div>
        </div>
    </div>
</body>
</html>
