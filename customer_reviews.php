<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

include_once 'role_check.php';
check_role_access('customer');
include 'db_connect.php';
include_once 'helpers.php';     
$formatted_name = formatName($_SESSION['name']);
$user_name = htmlspecialchars($formatted_name);
$user_initials = getAvatarInitials($formatted_name);
$user_name = htmlspecialchars($formatted_name);
$user_initials = getAvatarInitials($formatted_name);
$user_profile_image = getProfileImage($_SESSION['user_id'], $conn);

// 1. Fetch Food Reviews submitted by this user
$sql_food_rev = "SELECT oi.food_rating, oi.food_review, f.name as item_name, f.image as item_image, o.created_at 
                 FROM order_items oi 
                 JOIN orders o ON oi.order_id = o.order_id 
                 JOIN foods f ON oi.food_id = f.id 
                 WHERE o.user_id = ? AND oi.food_rating IS NOT NULL 
                 ORDER BY o.created_at DESC";
$stmt_food = $conn->prepare($sql_food_rev);
$stmt_food->bind_param("i", $_SESSION['user_id']);
$stmt_food->execute();
$res_food = $stmt_food->get_result();
$my_food_reviews = [];
while ($row = $res_food->fetch_assoc()) { $my_food_reviews[] = $row; }
$stmt_food->close();

// 2. Fetch Seller Reviews
$sql_seller_rev = "SELECT o.seller_rating, o.seller_review, u.name as seller_name, o.created_at 
                   FROM orders o 
                   JOIN users u ON o.seller_id = u.user_id 
                   WHERE o.user_id = ? AND o.seller_rating IS NOT NULL 
                   ORDER BY o.created_at DESC";
$stmt_seller = $conn->prepare($sql_seller_rev);
$stmt_seller->bind_param("i", $_SESSION['user_id']);
$stmt_seller->execute();
$res_seller = $stmt_seller->get_result();
$my_seller_reviews = [];
while ($row = $res_seller->fetch_assoc()) { $my_seller_reviews[] = $row; }
$stmt_seller->close();

// 3. Fetch Delivery Reviews
$sql_del_rev = "SELECT o.delivery_rating, o.delivery_review, u.name as partner_name, o.created_at 
                FROM orders o 
                JOIN users u ON o.delivery_partner_id = u.user_id 
                WHERE o.user_id = ? AND o.delivery_rating IS NOT NULL 
                ORDER BY o.created_at DESC";
$stmt_del = $conn->prepare($sql_del_rev);
$stmt_del->bind_param("i", $_SESSION['user_id']);
$stmt_del->execute();
$res_del = $stmt_del->get_result();
$my_del_reviews = [];
while ($row = $res_del->fetch_assoc()) { $my_del_reviews[] = $row; }
$stmt_del->close();

$total_reviews = count($my_food_reviews) + count($my_seller_reviews) + count($my_del_reviews);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reviews - Homely Bites</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Lemon&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* SWIGGY-STYLE DESIGN SYSTEM */
        :root {
            --primary-color: #fc8019;
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

        .content-container { padding: 40px 60px; max-width: 1400px; margin: 0 auto; width: 100%; }
        
        .page-header h2 { font-size: 28px; font-weight: 700; margin-bottom: 20px; color: #222; }
        
        /* Dashboard Tabs */
        .reviews-tabs { display: flex; gap: 30px; border-bottom: 2px solid #eee; margin-bottom: 30px; padding-bottom: 15px; }
        .tab-btn { background: none; border: none; font-size: 1.1rem; font-weight: 600; color: #888; cursor: pointer; position: relative; padding-bottom: 10px; transition: color 0.3s; }
        .tab-btn:hover { color: var(--primary-color); }
        .tab-btn.active { color: var(--primary-color); }
        .tab-btn.active::after { content: ''; position: absolute; bottom: -17px; left: 0; width: 100%; height: 3px; background-color: var(--primary-color); border-radius: 3px; }
        
        /* Grid & Cards */
        .reviews-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 25px; }
        
        .review-card {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 25px;
            box-shadow: var(--shadow-card);
            transition: transform 0.3s, box-shadow 0.3s;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .review-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }
        
        .target-entity {
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid #f0f0f0;
            padding-bottom: 15px;
        }
        .target-icon {
            width: 45px;
            height: 45px;
            background: #fff3e0;
            color: #f57c00;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        .target-icon.seller { background: #e8f5e9; color: #2e7d32; }
        .target-icon.delivery { background: #e3f2fd; color: #1565c0; }
        
        .target-details h4 { font-size: 1.05rem; color: #222; margin-bottom: 4px; }
        .target-details span { font-size: 0.8rem; color: #888; }
        
        .stars-row { color: #ffca28; font-size: 1rem; letter-spacing: 2px; }
        .text-content { color: #555; font-size: 0.95rem; line-height: 1.6; font-style: italic; background: #fcfcfc; padding: 15px; border-radius: 8px; border-left: 3px solid #eee; }

        .empty-state { text-align: center; padding: 60px 20px; width: 100%; background: #fff; border-radius: 16px; box-shadow: var(--shadow-card); }
        .empty-state i { font-size: 4rem; color: #eee; margin-bottom: 20px; }
        .empty-state p { color: var(--text-muted); font-size: 1.1rem; margin-bottom: 20px; }

        .tab-content { display: none; }
        .tab-content.active { display: block; }

        @media (max-width: 768px) { header { padding: 0 20px; } .content-container { padding: 20px; } }
    </style>
</head>
<body>
    <?php include 'customer_sidebar.php'; ?>
    <div class="main-content">
        <header>
            <div class="search-container">
                <i class="fa-solid fa-search"></i>
                <input type="text" placeholder="Search reviews...">
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
        <div class="content-container">
            <div class="page-header"><h2>My Reviews (<?php echo $total_reviews; ?>)</h2></div>
            
            <?php if ($total_reviews == 0): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-star-half-stroke" style="font-size: 4rem; color: #eee; margin-bottom: 20px;"></i>
                    <p style="color: #7f8c8d; font-size: 1.1rem; margin-bottom: 20px;">You haven't posted any reviews yet.</p>
                </div>
            <?php else: ?>
                <div class="reviews-tabs">
                    <button class="tab-btn active" onclick="switchTab('food')">Food Items (<?php echo count($my_food_reviews); ?>)</button>
                    <button class="tab-btn" onclick="switchTab('seller')">Restaurants (<?php echo count($my_seller_reviews); ?>)</button>
                    <button class="tab-btn" onclick="switchTab('delivery')">Delivery Partners (<?php echo count($my_del_reviews); ?>)</button>
                </div>

                <!-- Food Reviews Tab -->
                <div id="tab-food" class="tab-content active">
                    <div class="reviews-grid">
                        <?php foreach($my_food_reviews as $r): ?>
                            <div class="review-card">
                                <div class="target-entity">
                                    <img src="<?php echo htmlspecialchars($r['item_image'] ?: 'assets/images/placeholder.jpg'); ?>" class="target-icon" style="object-fit:cover;" alt="Food">
                                    <div class="target-details">
                                        <h4><?php echo htmlspecialchars($r['item_name']); ?></h4>
                                        <span><?php echo date('M d, Y', strtotime($r['created_at'])); ?></span>
                                    </div>
                                </div>
                                <div class="stars-row">
                                    <?php for($i=1; $i<=5; $i++) echo $i <= $r['food_rating'] ? '★' : '<span style="color:#eee;">★</span>'; ?>
                                </div>
                                <?php if(!empty($r['food_review'])): ?>
                                    <div class="text-content">"<?php echo htmlspecialchars($r['food_review']); ?>"</div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        <?php if(count($my_food_reviews) == 0) echo "<p style='color:#777; font-style:italic;'>No food reviews found.</p>"; ?>
                    </div>
                </div>

                <!-- Seller Reviews Tab -->
                <div id="tab-seller" class="tab-content">
                    <div class="reviews-grid">
                        <?php foreach($my_seller_reviews as $r): ?>
                            <div class="review-card">
                                <div class="target-entity">
                                    <div class="target-icon seller"><i class="fa-solid fa-store"></i></div>
                                    <div class="target-details">
                                        <h4><?php echo htmlspecialchars($r['seller_name']); ?></h4>
                                        <span><?php echo date('M d, Y', strtotime($r['created_at'])); ?></span>
                                    </div>
                                </div>
                                <div class="stars-row">
                                    <?php for($i=1; $i<=5; $i++) echo $i <= $r['seller_rating'] ? '★' : '<span style="color:#eee;">★</span>'; ?>
                                </div>
                                <?php if(!empty($r['seller_review'])): ?>
                                    <div class="text-content">"<?php echo htmlspecialchars($r['seller_review']); ?>"</div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        <?php if(count($my_seller_reviews) == 0) echo "<p style='color:#777; font-style:italic;'>No restaurant reviews found.</p>"; ?>
                    </div>
                </div>

                <!-- Delivery Reviews Tab -->
                <div id="tab-delivery" class="tab-content">
                    <div class="reviews-grid">
                        <?php foreach($my_del_reviews as $r): ?>
                            <div class="review-card">
                                <div class="target-entity">
                                    <div class="target-icon delivery"><i class="fa-solid fa-motorcycle"></i></div>
                                    <div class="target-details">
                                        <h4><?php echo htmlspecialchars($r['partner_name']); ?></h4>
                                        <span><?php echo date('M d, Y', strtotime($r['created_at'])); ?></span>
                                    </div>
                                </div>
                                <div class="stars-row">
                                    <?php for($i=1; $i<=5; $i++) echo $i <= $r['delivery_rating'] ? '★' : '<span style="color:#eee;">★</span>'; ?>
                                </div>
                                <?php if(!empty($r['delivery_review'])): ?>
                                    <div class="text-content">"<?php echo htmlspecialchars($r['delivery_review']); ?>"</div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        <?php if(count($my_del_reviews) == 0) echo "<p style='color:#777; font-style:italic;'>No delivery partner reviews found.</p>"; ?>
                    </div>
                </div>

            <?php endif; ?>
        </div>
    </div>
    <script>
        function toggleSidebar(){document.querySelector('.sidebar').classList.toggle('collapsed');}
        
        function switchTab(tabId) {
            // Remove active classes
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            // Add active class to clicked tab and corresponding content
            event.target.classList.add('active');
            document.getElementById('tab-' + tabId).classList.add('active');
        }
    </script>
</body>
</html>
