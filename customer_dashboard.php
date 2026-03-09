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

        /* Swiggy Style Restaurant Grid */
        .section-title { font-size: 24px; font-weight: 800; color: #111; margin-bottom: 25px; display: flex; align-items: center; gap: 10px; font-family: 'Poppins', sans-serif;}
        
        .food-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 30px 20px;
            padding-bottom: 40px;
        }

        .restaurant-card {
            background: transparent; border-radius: 16px; transition: transform 0.2s ease, filter 0.2s ease; position: relative; display: flex; flex-direction: column; cursor: pointer; text-decoration: none; color: inherit;
        }
        .restaurant-card:hover { transform: scale(0.98); }

        .rest-img-container { position: relative; width: 100%; height: 180px; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.08); margin-bottom: 12px; }
        .rest-img { width: 100%; height: 100%; object-fit: cover; background-color: #f0f0f0; transition: transform 0.3s ease; }
        .rest-overlay-gradient { position: absolute; bottom: 0; left: 0; right: 0; height: 60%; background: linear-gradient(to top, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0) 100%); display: flex; align-items: flex-end; padding: 12px; }
        .rest-offer { color: #fff; font-weight: 800; font-size: 1.1rem; text-shadow: 1px 1px 3px rgba(0,0,0,0.5); letter-spacing: -0.5px; }
        
        .rest-details { padding: 0 4px; flex: 1; display: flex; flex-direction: column; }
        .rest-name { font-size: 18px; font-weight: 700; margin-bottom: 2px; text-transform: capitalize; color: #222; line-height: 1.2; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        
        .rest-meta-row { display: flex; align-items: center; gap: 6px; font-size: 0.9rem; font-weight: 600; color: #444; margin-bottom: 4px; }
        .rating-star { background: var(--brand-green); color: white; border-radius: 50%; width: 18px; height: 18px; display: inline-flex; align-items: center; justify-content: center; font-size: 10px; }
        
        .rest-cuisines { font-size: 0.85rem; color: #777; font-weight: 400; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; margin-bottom: 2px; }
        .rest-location { font-size: 0.85rem; color: #777; font-weight: 400; }
        /* Category Filters */
        .category-filters {
            display: flex;
            gap: 12px;
            overflow-x: auto;
            padding-bottom: 15px;
            margin-bottom: 25px;
            -ms-overflow-style: none;  /* IE and Edge */
            scrollbar-width: none;  /* Firefox */
        }
        .category-filters::-webkit-scrollbar {
            display: none; /* Chrome, Safari and Opera */
        }
        
        .category-pill {
            padding: 8px 20px;
            border-radius: 50px;
            background: #fff;
            border: 1px solid #e0e0e0;
            color: #555;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            white-space: nowrap;
            transition: all 0.2s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.02);
        }
        
        .category-pill:hover {
            border-color: #ccc;
            background: #f9f9f9;
            color: #222;
        }

        .category-pill.active {
            background: var(--brand-green);
            color: #fff;
            border-color: var(--brand-green);
            box-shadow: 0 4px 10px rgba(10, 143, 8, 0.2);
        }

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

            <?php
            // Fetch available categories dynamically
            $cat_sql = "SELECT DISTINCT category FROM foods WHERE status = 'Available' AND is_deleted = 0 AND category IS NOT NULL AND category != '' ORDER BY category ASC";
            $categories = [];
            if ($cat_stmt = $conn->prepare($cat_sql)) {
                $cat_stmt->execute();
                $cat_res = $cat_stmt->get_result();
                while ($c = $cat_res->fetch_assoc()) {
                    $categories[] = $c['category'];
                }
                $cat_stmt->close();
            }

            $selected_category = isset($_GET['category']) ? $_GET['category'] : 'all';
            ?>

            <!-- Recommended Section based on category -->
            <div class="section-header">
                <h2 class="section-title">Restaurants with online food delivery near you</h2>
            </div>

            <!-- Categories -->
            <?php if (!empty($categories)): ?>
            <div class="category-filters">
                <a href="customer_dashboard.php?category=all" class="category-pill <?php echo $selected_category === 'all' ? 'active' : ''; ?>">All Cuisines</a>
                <?php foreach($categories as $cat): ?>
                    <a href="customer_dashboard.php?category=<?php echo urlencode($cat); ?>" class="category-pill <?php echo $selected_category === $cat ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($cat); ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="food-grid">
                <?php
                // Fetch customer's coordinates to calculate distance
                $customer_lat = null;
                $customer_lng = null;
                $cust_stmt = $conn->prepare("SELECT latitude, longitude FROM users WHERE user_id = ?");
                $cust_stmt->bind_param("i", $_SESSION['user_id']);
                $cust_stmt->execute();
                $cust_res = $cust_stmt->get_result();
                if ($cust_row = $cust_res->fetch_assoc()) {
                    $customer_lat = $cust_row['latitude'];
                    $customer_lng = $cust_row['longitude'];
                }
                $cust_stmt->close();

                // Haversine formula to calculate distance in km
                function getDistance($lat1, $lon1, $lat2, $lon2) {
                    if (!$lat1 || !$lon1 || !$lat2 || !$lon2) return null;
                    $earth_radius = 6371; // km
                    $dLat = deg2rad($lat2 - $lat1);
                    $dLon = deg2rad($lon2 - $lon1);
                    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
                    $c = 2 * asin(sqrt($a));
                    return $earth_radius * $c;
                }

                // Fetch Sellers (Restaurants) instead of individual foods
                if ($selected_category !== 'all') {
                    $rec_sql = "SELECT DISTINCT u.user_id, COALESCE(sa.business_name, u.name) as seller_name, u.city, u.current_offer, u.latitude, u.longitude,
                                (SELECT AVG(seller_rating) FROM orders WHERE seller_id = u.user_id AND seller_rating IS NOT NULL) as avg_rating,
                                (SELECT image FROM foods WHERE seller_id = u.user_id AND status = 'Available' AND is_deleted = 0 AND image != '' ORDER BY id DESC LIMIT 1) as cover_image,
                                (SELECT GROUP_CONCAT(DISTINCT category SEPARATOR ', ') FROM foods WHERE seller_id = u.user_id AND status = 'Available' AND is_deleted = 0) as cuisines
                                FROM users u 
                                JOIN foods f ON u.user_id = f.seller_id 
                                LEFT JOIN seller_applications sa ON u.user_id = sa.user_id AND sa.status = 'Approved'
                                WHERE f.category = ? AND f.status = 'Available' AND f.is_deleted = 0";
                    $stmt = $conn->prepare($rec_sql);
                    if ($stmt) {
                        $stmt->bind_param("s", $selected_category);
                    }
                } else {
                    $rec_sql = "SELECT DISTINCT u.user_id, COALESCE(sa.business_name, u.name) as seller_name, u.city, u.current_offer, u.latitude, u.longitude,
                                (SELECT AVG(seller_rating) FROM orders WHERE seller_id = u.user_id AND seller_rating IS NOT NULL) as avg_rating,
                                (SELECT image FROM foods WHERE seller_id = u.user_id AND status = 'Available' AND is_deleted = 0 AND image != '' ORDER BY id DESC LIMIT 1) as cover_image,
                                (SELECT GROUP_CONCAT(DISTINCT category SEPARATOR ', ') FROM foods WHERE seller_id = u.user_id AND status = 'Available' AND is_deleted = 0) as cuisines
                                FROM users u 
                                JOIN foods f ON u.user_id = f.seller_id 
                                LEFT JOIN seller_applications sa ON u.user_id = sa.user_id AND sa.status = 'Approved'
                                WHERE f.status = 'Available' AND f.is_deleted = 0";
                    $stmt = $conn->prepare($rec_sql);
                }

                if ($stmt) {
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $s_id = $row['user_id'];
                            $s_name = htmlspecialchars($row['seller_name']);
                            $s_city = htmlspecialchars($row['city'] ? $row['city'] : 'Local Area');
                            
                            // Prevent overflowing string of cuisines, show 3 max roughly
                            $cuisines_array = explode(", ", $row['cuisines']);
                            $s_cuisines = implode(", ", array_slice(array_unique($cuisines_array), 0, 4));

                            $s_rating = !empty($row['avg_rating']) ? number_format($row['avg_rating'], 1) : "New";
                            
                            $s_image = $row['cover_image'];
                            if (empty($s_image) || !file_exists($s_image)) {
                                $s_image = 'assets/images/image-coming-soon.png';
                            }
                            // Calculate a realistic delivery time based on distance (assume ~3 mins per km + 20 min base prep time)
                            $min_time = 30; // Default min time
                            $max_time = 45; // Default max time
                            $dist_km = getDistance($customer_lat, $customer_lng, $row['latitude'], $row['longitude']);
                            if ($dist_km !== null) {
                                $prep_time = 20; // 20 mins standard prep
                                $travel_time = round($dist_km * 3); // 3 mins per km
                                $min_time = $prep_time + $travel_time;
                                $max_time = $min_time + 10; // add a 10 min upper buffer
                            }

                            $offer_text = !empty($row['current_offer']) ? htmlspecialchars($row['current_offer']) : '';
                            ?>
                            <a href="restaurant_menu.php?seller_id=<?php echo $s_id; ?>" class="restaurant-card">
                                <div class="rest-img-container">
                                    <img src="<?php echo $s_image; ?>" alt="<?php echo $s_name; ?>" class="rest-img">
                                    <?php if (!empty($offer_text)): ?>
                                    <div class="rest-overlay-gradient">
                                        <div class="rest-offer"><?php echo $offer_text; ?></div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="rest-details">
                                    <div class="rest-name"><?php echo $s_name; ?></div>
                                    <div class="rest-meta-row">
                                        <div class="rating-star"><i class="fa-solid fa-star"></i></div>
                                        <span><?php echo $s_rating; ?> • <?php echo $min_time; ?>-<?php echo $max_time; ?> mins</span>
                                    </div>
                                    <div class="rest-cuisines"><?php echo rtrim($s_cuisines, ', '); ?></div>
                                    <div class="rest-location"><?php echo $s_city; ?></div>
                                </div>
                            </a>
                            <?php
                        }
                    } else {
                        ?>
                        <div class="empty-state">
                            <img src="assets/images/empty-food.svg" alt="No Restaurants">
                            <p>No restaurants available for this category right now</p>
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
