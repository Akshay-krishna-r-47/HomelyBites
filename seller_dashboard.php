<?php
include 'role_check.php';
check_role_access('seller');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Seller Dashboard - Homely Bites</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <a href="#" class="logo">Homely Bites</a>
        <nav>
            <ul>
                <li>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></li>
                <li><a href="#" onclick="location.replace('logout.php'); return false;">Logout</a></li>
            </ul>
        </nav>
    </header>
    <div style="padding: 2rem; text-align: center;">
        <h1>Seller Dashboard</h1>
        <p>Manage your menu and orders here.</p>
    </div>
</body>
</html>
