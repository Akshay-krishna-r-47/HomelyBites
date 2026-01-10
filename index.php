<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lemon&family=Lato:wght@300;400;700&family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet">
    <title>Homely Bites - Homemade Food Delivery</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    
    <!-- Header -->
    <header>
        <nav>
            <ul>
                <li><a href="index.php" class="active">Home</a></li>
                <li><a href="login.php">Login</a></li>
                <li><a href="register.php">Register</a></li>
            </ul>
        </nav>
    </header>

    <!-- Landing Splash -->
    <section class="landing-splash">
        <div class="logo-container">
            <h1 class="main-title">HOMELY BITES</h1>
            <img src="CAP.png" alt="Chef Hat" class="chef-hat">
        </div>
        <p class="description-text">Taste the Warmth of Home</p>
        <div class="cta-container">
            <a href="login.php" class="btn-primary">Order Now</a>
        </div>
    </section>



    <!-- Why Homely Bites Section -->
    <section class="features-section fade-in-section">
        <div class="features-header">
            <h2>Why Homely Bites?</h2>
            <p class="features-subtitle">Delicious, home-cooked meals made with care</p>
        </div>
        <div class="features-grid">
            <!-- Card 1 -->
            <div class="feature-card fade-in-section" style="transition-delay: 0.1s;">
                <div class="icon-container">
                    <img src="assets/images/Homemade & Hygienic Food.png" alt="Homemade Food">
                </div>
                <h3>Homemade & Hygienic Food</h3>
                <p>Delicious, home-cooked meals made with care</p>
            </div>
            <!-- Card 2 -->
            <div class="feature-card fade-in-section" style="transition-delay: 0.2s;">
                <div class="icon-container">
                    <img src="assets/images/fast local delivery.png" alt="Fast Delivery">
                </div>
                <h3>Fast Local Delivery</h3>
                <p>Hot and fresh food delivered fast to your doorstep</p>
            </div>
            <!-- Card 3 -->
            <div class="feature-card fade-in-section" style="transition-delay: 0.3s;">
                <div class="icon-container">
                    <img src="assets/images/Trusted Home Chefs.png" alt="Trusted Chefs">
                </div>
                <h3>Trusted Home Chefs</h3>
                <p>Experienced and vetted home chefs in your neighbourhood</p>
            </div>
            <!-- Card 4 -->
            <div class="feature-card fade-in-section" style="transition-delay: 0.4s;">
                <div class="icon-container">
                    <img src="assets/images/secure payments.png" alt="Secure Payments">
                </div>
                <h3>Secure Payments</h3>
                <p>Safe and easy payment options you can trust</p>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <p>&copy; 2024 Homely Bites. All rights reserved.</p>
    </footer>

    <script src="assets/js/script.js"></script>
</body>
</html>
