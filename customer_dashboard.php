<?php
include 'role_check.php';
check_role_access('customer');

$formatted_name = formatName($_SESSION['name']);
$user_name = htmlspecialchars($formatted_name);
$user_initials = getAvatarInitials($formatted_name);

// Fetch Profile Image
$user_profile_image = getProfileImage($_SESSION['user_id'], $conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Homely Bites</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Lemon&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* SWIGGY-STYLE DASHBOARD DESIGN */
        :root {
            --primary-color: #fc8019; /* Swiggy Orange-ish or keep brand color if preferred, User said "Swiggy-style cards", let's keep consistent green or switch to neutral? User didn't specify color change, just "Style". I'll keep brand greens but use Swiggy layout. Actually, prompt said "Professional typography... Minimal". I will use the Green brand identity but Swiggy structure. */
            --brand-green: #0a8f08;
            --bg-body: #f8f8f8;
            --text-dark: #222;
            --text-muted: #666;
            --card-bg: #FFFFFF;
            --header-height: 80px;
            --shadow-card: 0 4px 14px rgba(0,0,0,0.08);
            --shadow-hover: 0 8px 20px rgba(0,0,0,0.12);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        
        body { background-color: var(--bg-body); color: var(--text-dark); display: flex; min-height: 100vh; }

        /* Reuse Sidebar logic from include, assume it adapts or we override font */
        
        .main-content { flex: 1; display: flex; flex-direction: column; width: 0; }

        /* Header */
        header {
            height: var(--header-height);
            background-color: var(--card-bg);
            padding: 0 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 900;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
        }

        .search-container {
            display: flex; align-items: center; background: #f1f1f1; border-radius: 12px; padding: 12px 20px; width: 400px; transition: 0.3s;
        }
        .search-container i { color: #888; margin-right: 12px; }
        .search-container input { border: none; background: transparent; outline: none; width: 100%; font-size: 0.95rem; font-weight: 500; color: var(--text-dark); }

        .user-info { display: flex; align-items: center; gap: 15px; text-align: right; }
        .profile-pic { width: 40px; height: 40px; background: #eee; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.9rem; color: #555; overflow: hidden; object-fit: cover; }
        .profile-pic img { width: 100%; height: 100%; object-fit: cover; }

        /* Layout */
        .content-container { padding: 40px 60px; max-width: 1400px; margin: 0 auto; width: 100%; }

        /* Welcome Text */
        .welcome-section { margin-bottom: 40px; }
        .welcome-title { font-size: 28px; font-weight: 700; margin-bottom: 4px; color: #222; }
        .welcome-sub { font-size: 15px; color: #666; font-weight: 500; }

        /* Section Title */
        .section-header { margin: 30px 0 20px; display: flex; justify-content: space-between; align-items: center; }
        .section-title { font-size: 22px; font-weight: 600; color: #333; margin: 0; }
        .view-all { font-size: 14px; font-weight: 600; color: #ff6e00; text-decoration: none; } /* Swiggy orange accent for link */

        /* Food Grid (Swiggy Style) */
        .food-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 24px;
            padding-bottom: 40px;
        }

        .food-card {
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: var(--shadow-card);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            position: relative;
            cursor: pointer;
            border: 1px solid rgba(0,0,0,0.03);
        }

        .food-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-hover); }

        .food-img { width: 100%; height: 170px; object-fit: cover; background-color: #f0f0f0; display: block; }
        
        .food-details { padding: 16px; }
        .food-name { font-size: 16px; font-weight: 600; margin-bottom: 4px; text-transform: capitalize; color: #333; line-height: 1.3; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .food-cat { font-size: 13px; color: #888; margin-bottom: 12px; }
        
        .food-footer { display: flex; justify-content: space-between; align-items: center; margin-top: 8px; }
        .food-price { font-size: 15px; font-weight: 600; color: #0a8f08; }
        .food-rating { background-color: #24963f; color: white; padding: 2px 6px; border-radius: 4px; font-size: 12px; font-weight: 600; display: flex; align-items: center; gap: 4px; }
        .food-rating i { font-size: 10px; }

        /* Add Button */
        .btn-add { border: 1px solid #d4d5d9; color: #1ba672; background: white; padding: 6px 20px; border-radius: 4px; font-weight: 600; font-size: 13px; text-transform: uppercase; box-shadow: 0 1px 3px rgba(0,0,0,0.1); transition: all 0.2s; position: absolute; bottom: 16px; right: 16px; }
        .btn-add:hover { box-shadow: 0 2px 6px rgba(0,0,0,0.15); background: #f9f9f9; }

        /* Empty State */
        .empty-state { text-align: center; padding: 60px 20px; width: 100%; grid-column: 1 / -1; }
        .empty-state img { width: 180px; margin-bottom: 20px; opacity: 0.8; }
        .empty-state p { font-size: 18px; color: #888; font-weight: 500; }

        /* Menu Button Override */
        .card-menu { position: absolute; top: 10px; right: 10px; z-index: 10; }
        .menu-btn { background: rgba(255,255,255,0.8); border: none; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; backdrop-filter: blur(4px); }
        .menu-options { /* ...existing styles... */ display: none; background: white; padding: 10px; border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); position: absolute; right: 0; width: 150px; }
        .menu-options.show { display: block; }

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
                    <?php if($user_profile_image): ?>
                        <img src="<?php echo $user_profile_image; ?>" alt="Profile">
                    <?php else: ?>
                        <?php echo $user_initials; ?>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <!-- Dynamic Content -->
        <div class="content-container">
            
            <!-- Welcome Section -->
            <div class="welcome-section">
                <h1 class="welcome-title">Welcome back, <?php echo $user_name; ?>!</h1>
                <p class="welcome-sub">Order your favorite food from local chefs</p>
            </div>

            <!-- Recommended Section -->
            <div class="section-header">
                <h2 class="section-title">Recommended to you</h2>
                <a href="#" class="view-all">View All <i class="fa-solid fa-arrow-right"></i></a>
            </div>

            <div class="food-grid">
                <?php
                $rec_sql = "SELECT id, name, price, image, category FROM foods WHERE status = 'Available' ORDER BY id DESC LIMIT 12";
                if ($stmt = $conn->prepare($rec_sql)) {
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $f_name = htmlspecialchars($row['name']);
                            $f_price = htmlspecialchars($row['price']);
                            $f_image = $row['image'];
                            $f_cat = isset($row['category']) ? htmlspecialchars($row['category']) : 'Delicious';
                            
                            // Fallback image logic - Updated to image-coming-soon.png
                            if (empty($f_image) || !file_exists($f_image)) {
                                $f_image = 'assets/images/image-coming-soon.png';
                            }
                            ?>
                            <div class="food-card">
                                <img src="<?php echo $f_image; ?>" alt="<?php echo $f_name; ?>" class="food-img">
                                <div class="food-details">
                                    <div class="food-name"><?php echo $f_name; ?></div>
                                    <div class="food-cat"><?php echo $f_cat; ?></div>
                                    
                                    <div class="food-footer">
                                        <div class="food-rating"><i class="fa-solid fa-star"></i> 4.5</div>
                                        <div class="food-price">₹<?php echo $f_price; ?></div>
                                    </div>
                                    
                                    <!-- Add button overlay not needed, just simple clean card -->
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        ?>
                        <div class="empty-state">
                            <img src="assets/images/empty-food.svg" alt="No Food">
                            <p>No food items available right now</p>
                        </div>
                        <?php
                    }
                    $stmt->close();
                }
                ?>
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
