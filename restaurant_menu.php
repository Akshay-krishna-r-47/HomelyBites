<?php
include 'role_check.php';
// Ensure only logged-in users have access
check_role_access('customer'); 

include 'db_connect.php';
include_once 'helpers.php';

if (!isset($_GET['seller_id'])) {
    header("Location: customer_dashboard.php");
    exit();
}

$seller_id = intval($_GET['seller_id']);

// Fetch Seller Info (checking for both 'seller' and 'Seller', or seller_approved flag if applicable. To be safe, any user who authored foods.)
$stmt = $conn->prepare("SELECT name, email, phone, role FROM users WHERE user_id = ?");
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$seller_res = $stmt->get_result();

if ($seller_res->num_rows == 0) {
    echo "<h2>Restaurant not found or is no longer active.</h2>";
    echo "<a href='customer_dashboard.php'>Back to Dashboard</a>";
    exit();
}
$sellerInfo = $seller_res->fetch_assoc();
$seller_name = htmlspecialchars($sellerInfo['name']);
$seller_profile_image = getProfileImage($seller_id, $conn);
$seller_initials = getAvatarInitials($seller_name);

// Fetch Seller Stats (Aggregate Ratings)
$stats_sql = "SELECT AVG(seller_rating) as avg_rating, COUNT(seller_rating) as rating_count FROM orders WHERE seller_id = ? AND seller_rating IS NOT NULL";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("i", $seller_id);
$stats_stmt->execute();
$stats_res = $stats_stmt->get_result();
$seller_stats = $stats_res->fetch_assoc();
$avg_rating = $seller_stats['avg_rating'] ? number_format($seller_stats['avg_rating'], 1) : null;
$rating_count = $seller_stats['rating_count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $seller_name; ?> - Homely Bites</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root {
            --brand-green: #0a8f08;
            --bg-body: #f8f8f8;
            --text-dark: #222;
            --text-muted: #666;
            --card-bg: #FFFFFF;
            --shadow-card: 0 4px 14px rgba(0,0,0,0.08);
            --shadow-hover: 0 8px 20px rgba(0,0,0,0.12);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: var(--bg-body); color: var(--text-dark); display: flex; min-height: 100vh; }
        
        .main-content { flex: 1; padding: 40px; display: flex; flex-direction: column; align-items: center; }
        .content-container { max-width: 1400px; width: 100%; margin: 0 auto; }

        .header-nav { width: 100%; margin-bottom: 25px; }
        .back-btn { display: inline-flex; align-items: center; gap: 8px; color: #555; text-decoration: none; font-weight: 500; transition: color 0.2s; }
        .back-btn:hover { color: var(--brand-green); }

        /* Restaurant Header Profile */
        .restaurant-header {
            background: white;
            border-radius: 20px;
            padding: 40px;
            display: flex;
            align-items: center;
            gap: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            margin-bottom: 40px;
            position: relative;
            overflow: hidden;
        }

        .restaurant-header::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 60px;
            background: linear-gradient(to right, #e8f5e9, #c8e6c9);
            z-index: 0;
        }

        .restaurant-avatar {
            width: 120px;
            height: 120px;
            background: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--brand-green);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            overflow: hidden;
            z-index: 1;
            border: 4px solid white;
        }
        .restaurant-avatar img { width: 100%; height: 100%; object-fit: cover; }

        .restaurant-info { flex: 1; z-index: 1; margin-top: 20px; }
        .r-name { font-size: 28px; font-weight: 700; color: #222; margin-bottom: 5px; }
        .r-meta { color: #666; font-size: 14px; margin-bottom: 15px; display: flex; align-items: center; gap: 15px; }
        
        .r-stats { display: flex; gap: 20px; }
        .stat-box { display: flex; flex-direction: column; gap: 2px; }
        .stat-val { font-size: 16px; font-weight: 700; color: #333; display: flex; align-items: center; gap: 5px; }
        .stat-label { font-size: 12px; color: #888; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px; }

        .rating-badge { background: #4caf50; color: white; padding: 3px 8px; border-radius: 6px; font-size: 14px; display: inline-flex; align-items: center; gap: 4px; }

        /* Swiggy Style Food Grid (Reused from Dashboard) */
        .section-title { font-size: 22px; font-weight: 700; color: #333; margin-bottom: 25px; display: flex; align-items: center; gap: 10px; }
        
        .food-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 24px;
            padding-bottom: 40px;
        }

        .food-card {
            background: #fff; border-radius: 16px; overflow: hidden; box-shadow: var(--shadow-card); transition: transform 0.2s ease, box-shadow 0.2s ease; position: relative; border: 1px solid rgba(0,0,0,0.03); display: flex; flex-direction: column;
        }
        .food-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-hover); }

        .food-img-link { display: block; overflow: hidden; height: 170px; }
        .food-img { width: 100%; height: 100%; object-fit: cover; background-color: #f0f0f0; transition: transform 0.3s ease; }
        .food-card:hover .food-img { transform: scale(1.05); }
        
        .food-details { padding: 16px; flex: 1; display: flex; flex-direction: column; }
        .food-name { font-size: 16px; font-weight: 600; margin-bottom: 8px; text-transform: capitalize; color: #333; line-height: 1.3; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; text-decoration: none; }
        .food-name:hover { color: var(--brand-green); }
        .food-cat { font-size: 13px; color: #888; font-weight: 500; }
        
        .food-footer { display: flex; justify-content: space-between; align-items: center; margin-top: auto; padding-top: 15px; }
        .food-price { font-size: 16px; font-weight: 700; color: #222; }
        
        /* Add Button */
        .btn-add { border: 1px solid #d4d5d9; color: var(--brand-green); background: white; padding: 6px 20px; border-radius: 4px; font-weight: 600; font-size: 13px; text-transform: uppercase; box-shadow: 0 1px 3px rgba(0,0,0,0.1); transition: all 0.2s; cursor: pointer; width: 100%; }
        .btn-add:hover { box-shadow: 0 2px 6px rgba(0,0,0,0.15); background: #f9f9f9; }
        .btn-unavailable { width:100%; padding: 8px; background:#f5f5f5; border:1px solid #ddd; color:#999; border-radius:4px; font-weight:600; cursor:not-allowed; text-align: center; font-size: 13px; }

        .empty-state { text-align: center; padding: 60px 20px; width: 100%; grid-column: 1 / -1; }
        .empty-state i { font-size: 3rem; color: #ddd; margin-bottom: 20px; }
        .empty-state p { font-size: 18px; color: #888; font-weight: 500; }
    </style>
</head>
<body>

    <?php include 'customer_sidebar.php'; ?>

    <div class="main-content">
        <div class="content-container">
            <div class="header-nav">
                <a href="customer_dashboard.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
            </div>

            <!-- Restaurant Header Info -->
            <div class="restaurant-header">
                <div class="restaurant-avatar">
                    <?php if($seller_profile_image): ?>
                        <img src="<?php echo $seller_profile_image; ?>" alt="Profile">
                    <?php else: ?>
                        <?php echo $seller_initials; ?>
                    <?php endif; ?>
                </div>
                
                <div class="restaurant-info">
                    <h1 class="r-name"><?php echo $seller_name; ?></h1>
                    <div class="r-meta">
                        <span><i class="fa-solid fa-store" style="color: #888;"></i> Homely Bites Partner</span>
                        <?php if($avg_rating): ?>
                            <div class="rating-badge"><i class="fa-solid fa-star" style="font-size: 10px;"></i> <?php echo $avg_rating; ?></div>
                        <?php else: ?>
                            <span style="font-size: 13px; color: #aaa;">New Seller</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="r-stats" style="margin-top: 20px;">
                    <div class="stat-box">
                        <span class="stat-val"><i class="fa-solid fa-envelope" style="color: #ccc;"></i> <?php echo htmlspecialchars($sellerInfo['email']); ?></span>
                        <span class="stat-label">Contact</span>
                    </div>
                </div>
            </div>

            <!-- Food Items Grid -->
            <h2 class="section-title"><i class="fa-solid fa-utensils" style="color: var(--brand-green);"></i> Full Menu</h2>
            <div class="food-grid">
                <?php
                $menu_sql = "SELECT f.*, 
                            (SELECT AVG(food_rating) FROM order_items WHERE food_id = f.id AND food_rating IS NOT NULL) as avg_food_rating,
                            (SELECT COUNT(food_rating) FROM order_items WHERE food_id = f.id AND food_rating IS NOT NULL) as rating_count 
                            FROM foods f 
                            WHERE f.seller_id = ? AND f.status = 'Available' AND f.is_deleted = 0 
                            ORDER BY f.id DESC";
                $stmt_menu = $conn->prepare($menu_sql);
                $stmt_menu->bind_param("i", $seller_id);
                $stmt_menu->execute();
                $menu_res = $stmt_menu->get_result();

                if ($menu_res->num_rows > 0) {
                    while ($food = $menu_res->fetch_assoc()) {
                        $f_id = $food['id'];
                        $f_name = htmlspecialchars($food['name']);
                        $f_price = htmlspecialchars($food['price']);
                        $f_cat = isset($food['category']) ? htmlspecialchars($food['category']) : 'Delicious';
                        $f_stock = (int)$food['stock'];
                        $f_image = $food['image'];
                        if (empty($f_image) || !file_exists($f_image)) {
                            $f_image = 'assets/images/image-coming-soon.png';
                        }
                        
                        // Availability Logic
                        $is_time_available = true;
                        $availability_message = "";
                        $s1_start = $food['avail_slot1_start']; $s1_end = $food['avail_slot1_end'];
                        $s2_start = $food['avail_slot2_start']; $s2_end = $food['avail_slot2_end'];
                        $s3_start = $food['avail_slot3_start']; $s3_end = $food['avail_slot3_end'];
                        
                        if (!empty($s1_start) || !empty($s2_start) || !empty($s3_start)) {
                            $current_time = date('H:i:s');
                            $in_slot1 = (!empty($s1_start) && !empty($s1_end) && $current_time >= $s1_start && $current_time <= $s1_end);
                            $in_slot2 = (!empty($s2_start) && !empty($s2_end) && $current_time >= $s2_start && $current_time <= $s2_end);
                            $in_slot3 = (!empty($s3_start) && !empty($s3_end) && $current_time >= $s3_start && $current_time <= $s3_end);
                            if (!$in_slot1 && (!$in_slot2) && (!$in_slot3)) {
                                $is_time_available = false;
                                $mssg = [];
                                if(!empty($s1_start)) $mssg[] = date('h:i A', strtotime($s1_start)) . " - " . date('h:i A', strtotime($s1_end));
                                if(!empty($s2_start)) $mssg[] = date('h:i A', strtotime($s2_start)) . " - " . date('h:i A', strtotime($s2_end));
                                if(!empty($s3_start)) $mssg[] = date('h:i A', strtotime($s3_start)) . " - " . date('h:i A', strtotime($s3_end));
                                $availability_message = "Available: " . implode(" & ", $mssg);
                            }
                        }
                        ?>
                        <div class="food-card">
                            <a href="food_details.php?id=<?php echo $f_id; ?>" class="food-img-link">
                                <img src="<?php echo $f_image; ?>" alt="<?php echo $f_name; ?>" class="food-img">
                            </a>
                            <div class="food-details">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 5px;">
                                    <a href="food_details.php?id=<?php echo $f_id; ?>" style="text-decoration: none; display: block; flex:1; width: 0;">
                                        <div class="food-name"><?php echo $f_name; ?></div>
                                    </a>
                                    <?php if(!empty($food['avg_food_rating'])): ?>
                                        <div style="font-size: 12px; font-weight: 600; color: #ff9800; background: #fff3e0; padding: 2px 6px; border-radius: 4px; display: inline-flex; align-items: center; gap: 3px;">
                                            <i class="fa-solid fa-star"></i> <?php echo number_format($food['avg_food_rating'], 1); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="food-cat"><?php echo $f_cat; ?></div>
                                
                                <div class="food-footer">
                                    <div class="food-price">₹<?php echo $f_price; ?></div>
                                    <?php if ($f_stock > 0): ?>
                                        <span style="font-size: 0.75rem; font-weight: 600; color: #0a8f08; background: #e8f5e9; padding: 2px 8px; border-radius: 12px;"><?php echo $f_stock; ?> left</span>
                                    <?php else: ?>
                                        <span style="font-size: 0.75rem; font-weight: 600; color: #d32f2f; background: #ffebee; padding: 2px 8px; border-radius: 12px;">Sold Out</span>
                                    <?php endif; ?>
                                </div>

                                <?php if(!$is_time_available): ?>
                                    <div style="font-size: 0.7rem; color: #f57c00; font-weight: 600; margin-top: 10px; line-height: 1.3;">
                                        <i class="fa-regular fa-clock"></i> <?php echo $availability_message; ?>
                                    </div>
                                <?php endif; ?>

                            </div>
                            <div style="padding: 0 16px 16px 16px; margin-top: auto;">
                                <?php if (!$is_time_available): ?>
                                    <div class="btn-unavailable">UNAVAILABLE</div>
                                <?php elseif ($f_stock > 0): ?>
                                    <form action="handle_cart.php" method="POST">
                                        <input type="hidden" name="food_id" value="<?php echo $f_id; ?>">
                                        <button type="submit" name="action" value="add" class="btn-add">ADD TO CART</button>
                                    </form>
                                <?php else: ?>
                                    <div class="btn-unavailable" style="color:#d32f2f; background:#ffebee;">OUT OF STOCK</div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-store-slash"></i>
                        <p>This seller hasn't listed any available food items yet.</p>
                    </div>
                    <?php
                }
                $stmt_menu->close();
                ?>
            </div>
            
        </div>
    </div>
    
    <script>
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('collapsed');
        }
    </script>
</body>
</html>
