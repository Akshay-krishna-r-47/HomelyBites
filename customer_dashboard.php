<?php
include 'role_check.php';
check_role_access('customer');

$user_name = htmlspecialchars($_SESSION['name']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Homely Bites</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Lemon&family=Lato:wght@300;400;700&family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* DASHBOARD REFACTOR - CLEAN ONLY */
        :root {
            --primary-color: #27ae60;
            --secondary-color: #2c3e50;
            --accent-color: #f39c12;
            --bg-body: #fdfbf7;
            --text-dark: #2c3e50;
            --text-muted: #7f8c8d;
            --card-bg: #FFFFFF;
            --brand-green: #008000;
            
            --sidebar-width: 280px;
            --sidebar-collapsed-width: 80px;
            --header-height: 80px;
            --border-radius: 16px;
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.04);
            --shadow-hover: 0 12px 32px rgba(0,0,0,0.08);
            --border-light: 1px solid rgba(0,0,0,0.06);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Lato', sans-serif;
        }

        body {
            background-color: var(--bg-body);
            color: var(--text-dark);
            display: flex;
            min-height: 100vh;
        }
        
        @import url('assets/css/style.css'); /* Import shared styles if present, defaulting to inline for safety of previous edits */
        
        /* SIDEBAR css is in customer_sidebar.php import, but layout needs this */
        .sidebar { /* Handled by include */ }

        /* Main Content Layout */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            width: 0; /* Prevents flex overflow */
            transition: all 0.4s ease;
        }

        /* Top Header */
        header {
            height: var(--header-height);
            background-color: var(--card-bg);
            padding: 0 40px;
            display: flex;
            align-items: center;
            justify-content: space-between; /* Changed for search bar */
            position: sticky;
            top: 0;
            z-index: 900;
            border-bottom: var(--border-light);
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
        }

        .search-container {
            display: flex;
            align-items: center;
            background: #f8f9fa;
            border-radius: 12px;
            padding: 10px 20px;
            width: 400px;
            border: 1px solid #eee;
            transition: all 0.3s;
        }

        .search-container:focus-within {
            background: #fff;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(39, 174, 96, 0.1);
        }

        .search-container i { color: #aaa; margin-right: 12px; }
        .search-container input { border: none; background: transparent; outline: none; width: 100%; font-size: 0.95rem; color: var(--text-dark); }

        .user-info { display: flex; align-items: center; gap: 15px; text-align: right; }
        .profile-pic { width: 42px; height: 42px; background: linear-gradient(135deg, var(--brand-green), #2ecc71); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.1rem; box-shadow: 0 4px 10px rgba(0, 128, 0, 0.2); }

        /* Dashboard Content */
        .content-container {
            padding: 40px 50px;
            max-width: 1600px;
            margin: 0 auto;
            width: 100%;
        }

        .welcome-section { margin-bottom: 40px; }
        .welcome-section h2 { font-family: 'Playfair Display', serif; font-size: 2.2rem; color: var(--text-dark); margin-bottom: 8px; }

        /* Section Header */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 25px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(0,0,0,0.03);
        }

        .section-header h3 { font-family: 'Playfair Display', serif; font-size: 1.8rem; color: var(--text-dark); }
        .view-all { color: var(--primary-color); text-decoration: none; font-weight: 700; font-size: 0.9rem; display: flex; align-items: center; gap: 5px; transition: gap 0.2s; }
        .view-all:hover { gap: 8px; }
        .view-all::after { content: '→'; font-size: 1.1rem; }

        /* Food Grid (Copied) */
        .food-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 30px;
            margin-bottom: 50px;
        }

        .food-card {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            border: var(--border-light);
            transition: all 0.3s ease;
            position: relative;
        }

        .food-card:hover { transform: translateY(-8px); box-shadow: var(--shadow-hover); }

        .food-img { height: 200px; background-color: #f0f0f0; background-size: cover; background-position: center; position: relative; }
        .food-info { padding: 24px; }
        .food-name { font-family: 'Playfair Display', serif; font-weight: 700; font-size: 1.25rem; margin-bottom: 8px; color: var(--text-dark); }
        .food-meta { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .food-price { color: var(--text-dark); font-weight: 800; font-size: 1.1rem; }
        .food-rating { background-color: #fff8e1; color: #f39c12; padding: 4px 10px; border-radius: 8px; font-size: 0.85rem; font-weight: 700; display: flex; align-items: center; gap: 5px; }

        /* 3-Dot Menu */
        .card-menu { position: absolute; top: 15px; right: 15px; z-index: 10; }
        .menu-btn { background: rgba(255, 255, 255, 0.95); border: none; width: 34px; height: 34px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; color: var(--text-dark); box-shadow: 0 4px 10px rgba(0,0,0,0.15); transition: all 0.2s; }
        .menu-btn:hover { background: #fff; transform: scale(1.1) rotate(90deg); }
        .menu-options { position: absolute; top: 40px; right: 0; background: white; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); padding: 8px; min-width: 180px; display: none; flex-direction: column; border: var(--border-light); z-index: 100; transform-origin: top right; }
        .menu-options.show { display: flex; animation: menuPop 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        @keyframes menuPop { from { opacity: 0; transform: scale(0.9); } to { opacity: 1; transform: scale(1); } }
        .menu-option { padding: 12px 16px; text-align: left; background: none; border: none; cursor: pointer; font-size: 0.9rem; color: var(--text-dark); display: flex; align-items: center; gap: 12px; border-radius: 8px; width: 100%; font-weight: 500; transition: all 0.2s; }
        .menu-option:hover { background-color: #f8f9fa; color: var(--primary-color); transform: translateX(4px); }
        .menu-option i { width: 20px; text-align: center; }

    </style>
</head>
<body>

    <?php include 'customer_sidebar.php'; ?>

    <div class="main-content">
        <!-- Header -->
        <header>
            <div class="search-container">
                <i class="fa-solid fa-search"></i>
                <input type="text" placeholder="Search for food, restaurants...">
            </div>

            <div class="user-info">
                <div>
                    <p style="font-weight: 700; font-size: 0.95rem; margin-bottom: 2px; color: var(--text-dark);"><?php echo $user_name; ?></p>
                    <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 500; letter-spacing: 0.5px; text-transform: uppercase;">Customer</span>
                </div>
                <div class="profile-pic">
                    <i class="fa-solid fa-user"></i>
                </div>
            </div>
        </header>

        <!-- Dynamic Content -->
        <div class="content-container">
            
            <!-- Welcome Section -->
            <div class="welcome-section">
                <h2>Welcome back, <?php echo $user_name; ?>! 👋</h2>
                <p style="color: #7f8c8d; font-size: 1.05rem;">Ready to satisfy your cravings today?</p>
            </div>

            <!-- Recommended Section (Kept) -->
            <div class="section-header">
                <h3>Recommended to you</h3>
                <a href="#" class="view-all">View All</a>
            </div>

            <div class="food-grid">
                <!-- Food Card 1 -->
                <div class="food-card">
                    <div class="food-img" style="background-image: url('https://images.unsplash.com/photo-1589302168068-964664d93dc0?auto=format&fit=crop&w=800');">
                        <div class="card-menu">
                            <button class="menu-btn" onclick="toggleMenu(this, event)"><i class="fa-solid fa-ellipsis-vertical"></i></button>
                            <div class="menu-options">
                                <button class="menu-option"><i class="fa-regular fa-heart"></i>Add to Favorites</button>
                                <button class="menu-option"><i class="fa-solid fa-share-nodes"></i>Share</button>
                                <button class="menu-option" style="color: #e74c3c;"><i class="fa-solid fa-ban"></i>Not Interested</button>
                            </div>
                        </div>
                    </div>
                    <div class="food-info">
                        <h4 class="food-name">Hyderabadi Chicken Biryani</h4>
                        <div class="food-meta">
                            <span class="food-price">₹250</span>
                            <div class="food-rating"><i class="fa-solid fa-star"></i> 4.8</div>
                        </div>
                    </div>
                </div>

                <!-- Food Card 2 -->
                <div class="food-card">
                    <div class="food-img" style="background-image: url('https://images.unsplash.com/photo-1513104890138-7c749659a591?auto=format&fit=crop&w=800');">
                        <div class="card-menu">
                            <button class="menu-btn" onclick="toggleMenu(this, event)"><i class="fa-solid fa-ellipsis-vertical"></i></button>
                            <div class="menu-options">
                                <button class="menu-option"><i class="fa-regular fa-heart"></i>Add to Favorites</button>
                                <button class="menu-option"><i class="fa-solid fa-share-nodes"></i>Share</button>
                                <button class="menu-option" style="color: #e74c3c;"><i class="fa-solid fa-ban"></i>Not Interested</button>
                            </div>
                        </div>
                    </div>
                    <div class="food-info">
                        <h4 class="food-name">Classic Margherita Pizza</h4>
                        <div class="food-meta">
                            <span class="food-price">₹320</span>
                            <div class="food-rating"><i class="fa-solid fa-star"></i> 4.5</div>
                        </div>
                    </div>
                </div>

                <!-- Food Card 3 -->
                <div class="food-card">
                    <div class="food-img" style="background-image: url('https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&w=800');">
                        <div class="card-menu">
                            <button class="menu-btn" onclick="toggleMenu(this, event)"><i class="fa-solid fa-ellipsis-vertical"></i></button>
                            <div class="menu-options">
                                <button class="menu-option"><i class="fa-regular fa-heart"></i>Add to Favorites</button>
                                <button class="menu-option"><i class="fa-solid fa-share-nodes"></i>Share</button>
                                <button class="menu-option" style="color: #e74c3c;"><i class="fa-solid fa-ban"></i>Not Interested</button>
                            </div>
                        </div>
                    </div>
                    <div class="food-info">
                        <h4 class="food-name">Premium Beef Burger</h4>
                        <div class="food-meta">
                            <span class="food-price">₹180</span>
                            <div class="food-rating"><i class="fa-solid fa-star"></i> 4.3</div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('collapsed');
        }

        function toggleMenu(btn, event) {
            event.stopPropagation();
            
            // Close all other open menus first
            document.querySelectorAll('.menu-options.show').forEach(menu => {
                if (menu !== btn.nextElementSibling) {
                    menu.classList.remove('show');
                }
            });

            const menu = btn.nextElementSibling;
            menu.classList.toggle('show');
        }

        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.card-menu')) {
                document.querySelectorAll('.menu-options.show').forEach(menu => {
                    menu.classList.remove('show');
                });
            }
        });
    </script>
</body>
</html>
