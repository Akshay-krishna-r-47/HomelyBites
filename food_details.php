<?php
include 'role_check.php';
// Ensure only logged-in customers (and others possibly) can view
check_role_access('customer'); 

include 'db_connect.php';
include_once 'helpers.php';

if (!isset($_GET['id'])) {
    header("Location: customer_dashboard.php");
    exit();
}

$food_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// Fetch Food Details
$stmt = $conn->prepare("SELECT f.*, u.name as seller_name,
                        (SELECT AVG(food_rating) FROM order_items WHERE food_id = f.id AND food_rating IS NOT NULL) as avg_food_rating,
                        (SELECT COUNT(food_rating) FROM order_items WHERE food_id = f.id AND food_rating IS NOT NULL) as food_rating_count,
                        (SELECT AVG(seller_rating) FROM orders WHERE seller_id = f.seller_id AND seller_rating IS NOT NULL) as avg_seller_rating,
                        (SELECT COUNT(seller_rating) FROM orders WHERE seller_id = f.seller_id AND seller_rating IS NOT NULL) as seller_rating_count
                        FROM foods f JOIN users u ON f.seller_id = u.user_id WHERE f.id = ?");
$stmt->bind_param("i", $food_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "Food item not found.";
    exit();
}

$food = $result->fetch_assoc();
$f_name = htmlspecialchars($food['name']);
$f_price = htmlspecialchars($food['price']);
$f_desc = isset($food['description']) ? htmlspecialchars($food['description']) : 'No description available.';
$f_image = $food['image'];
if (empty($f_image) || !file_exists($f_image)) {
    $f_image = 'assets/images/image-coming-soon.png';
}

// Time Slot Logic
$is_time_available = true;
$availability_message = "";

$s1_start = $food['avail_slot1_start'];
$s1_end = $food['avail_slot1_end'];
$s2_start = $food['avail_slot2_start'];
$s2_end = $food['avail_slot2_end'];
$s3_start = $food['avail_slot3_start'];
$s3_end = $food['avail_slot3_end'];

if (!empty($s1_start) || !empty($s2_start) || !empty($s3_start)) {
    $current_time = date('H:i:s');
    $in_slot1 = (!empty($s1_start) && !empty($s1_end) && $current_time >= $s1_start && $current_time <= $s1_end);
    $in_slot2 = (!empty($s2_start) && !empty($s2_end) && $current_time >= $s2_start && $current_time <= $s2_end);
    $in_slot3 = (!empty($s3_start) && !empty($s3_end) && $current_time >= $s3_start && $current_time <= $s3_end);
    
    if (!$in_slot1 && !$in_slot2 && !$in_slot3) {
        $is_time_available = false;
        $mssg = [];
        if(!empty($s1_start)) $mssg[] = date('h:i A', strtotime($s1_start)) . " - " . date('h:i A', strtotime($s1_end));
        if(!empty($s2_start)) $mssg[] = date('h:i A', strtotime($s2_start)) . " - " . date('h:i A', strtotime($s2_end));
        if(!empty($s3_start)) $mssg[] = date('h:i A', strtotime($s3_start)) . " - " . date('h:i A', strtotime($s3_end));
        $availability_message = "Available: " . implode(" & ", $mssg);
    }
}

$stmt->close();

// Fetch Food Reviews
$reviews_sql = "SELECT oi.food_rating, oi.food_review, u.name as reviewer_name, o.created_at 
                FROM order_items oi 
                JOIN orders o ON oi.order_id = o.order_id 
                JOIN users u ON o.user_id = u.user_id 
                WHERE oi.food_id = ? AND oi.food_rating IS NOT NULL 
                ORDER BY o.created_at DESC";
$stmt_rev = $conn->prepare($reviews_sql);
$stmt_rev->bind_param("i", $food_id);
$stmt_rev->execute();
$res_reviews = $stmt_rev->get_result();
$food_reviews = [];
while ($row = $res_reviews->fetch_assoc()) {
    $food_reviews[] = $row;
}
$stmt_rev->close();

// Fetch Seller Reviews
$seller_rev_sql = "SELECT o.seller_rating, o.seller_review, u.name as reviewer_name, o.created_at 
                   FROM orders o 
                   JOIN users u ON o.user_id = u.user_id 
                   WHERE o.seller_id = ? AND o.seller_rating IS NOT NULL 
                   ORDER BY o.created_at DESC";
$stmt_srev = $conn->prepare($seller_rev_sql);
$stmt_srev->bind_param("i", $food['seller_id']);
$stmt_srev->execute();
$res_srev = $stmt_srev->get_result();
$seller_reviews = [];
while ($row = $res_srev->fetch_assoc()) {
    $seller_reviews[] = $row;
}
$stmt_srev->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $f_name; ?> - Homely Bites</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary-color: #fc8019; --brand-green: #0a8f08; --text-dark: #222; --bg-body: #f4f6f8; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: var(--bg-body); color: var(--text-dark); display: flex; min-height: 100vh; }
        .main-content { flex: 1; padding: 40px; display: flex; flex-direction: column; align-items: center; }
        
        .header-nav { width: 100%; max-width: 1000px; margin-bottom: 25px; }
        .back-btn { display: inline-flex; align-items: center; gap: 8px; color: #555; text-decoration: none; font-weight: 500; transition: color 0.2s; }
        .back-btn:hover { color: var(--brand-green); }
        
        .details-container { 
            max-width: 1000px; 
            width: 100%;
            background: #ffffff; 
            border-radius: 20px; 
            overflow: hidden; 
            box-shadow: 0 10px 40px rgba(0,0,0,0.06); 
            display: flex; 
            flex-direction: row; 
            min-height: 450px;
        }
        
        .image-section {
            flex: 1.2;
            background-color: #f9f9f9;
            position: relative;
            overflow: hidden;
        }
        
        .food-hero { 
            width: 100%; 
            height: 100%; 
            object-fit: cover; 
            position: absolute;
            top: 0;
            left: 0;
        }
        
        .info-section { 
            flex: 1;
            padding: 50px 40px; 
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .f-title { font-size: 2.2rem; font-weight: 700; margin-bottom: 8px; color: #111; text-transform: capitalize; line-height: 1.2; }
        .f-seller { color: #777; font-size: 0.95rem; margin-bottom: 25px; display: flex; align-items: center; gap: 6px; }
        .f-price { font-size: 2.2rem; color: var(--brand-green); font-weight: 700; margin-bottom: 20px; }
        
        .f-desc-label { font-size: 0.85rem; font-weight: 600; text-transform: uppercase; color: #999; letter-spacing: 0.5px; margin-bottom: 8px; }
        .f-desc { margin-bottom: 35px; line-height: 1.7; color: #666; font-size: 1rem; }
        
        .btn-add-cart {
            background-color: var(--brand-green); 
            color: white; 
            border: none; 
            padding: 16px 32px; 
            font-size: 1.1rem; 
            font-weight: 600; 
            border-radius: 12px; 
            cursor: pointer; 
            transition: all 0.2s ease;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(10, 143, 8, 0.2);
        }
        .btn-add-cart:hover { 
            background-color: #087a06; 
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(10, 143, 8, 0.3);
        }
        
        /* Unavailable Button Styling */
        .btn-unavailable {
            background-color: #fff3e0;
            color: #e65100;
            border: 1px solid #ffcc80;
            box-shadow: none;
            cursor: not-allowed;
            pointer-events: none;
        }
        .availability-warning {
            color: #d84315;
            font-size: 0.95rem;
            font-weight: 600;
            margin-top: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: #fbe9e7;
            padding: 12px;
            border-radius: 8px;
        }

        /* Reviews Section Styles */
        .reviews-container {
            max-width: 1000px;
            width: 100%;
            margin-top: 40px;
            background: #ffffff;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.06);
        }
        .reviews-header {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 25px;
            color: #222;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 15px;
        }
        .review-card {
            padding: 20px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .review-card:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        .review-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }
        .reviewer-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .reviewer-avatar {
            width: 40px;
            height: 40px;
            background: #f0f0f0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: #666;
        }
        .reviewer-name {
            font-weight: 600;
            color: #333;
        }
        .review-date {
            font-size: 0.8rem;
            color: #999;
        }
        .review-stars {
            color: #ffca28;
            font-size: 0.9rem;
            letter-spacing: 2px;
        }
        .review-text {
            color: #555;
            line-height: 1.6;
            font-size: 0.95rem;
            padding-left: 50px;
        }
        .empty-reviews {
            text-align: center;
            padding: 40px;
            color: #888;
            font-style: italic;
            background: #fafafa;
            border-radius: 12px;
        }
        
        .review-tab-btn { background: none; border: none; font-size: 1.1rem; font-weight: 600; color: #888; cursor: pointer; position: relative; padding-bottom: 10px; transition: color 0.3s; }
        .review-tab-btn:hover { color: var(--brand-green); }
        .review-tab-btn.active { color: var(--brand-green); }
        .review-tab-btn.active::after { content: ''; position: absolute; bottom: -12px; left: 0; width: 100%; height: 3px; background-color: var(--brand-green); border-radius: 3px; }

        @media (max-width: 850px) {
            .details-container { flex-direction: column; }
            .image-section { padding: 0; min-height: 300px; }
            .info-section { padding: 30px; }
        }
    </style>
</head>
<body>
    <?php include 'customer_sidebar.php'; ?>
    <div class="main-content">
        <div class="header-nav">
            <a href="customer_dashboard.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
        </div>
        
        <div class="details-container">
            <div class="image-section">
                <img src="<?php echo $f_image; ?>" alt="<?php echo $f_name; ?>" class="food-hero">
            </div>

            <div class="info-section">
                <h1 class="f-title"><?php echo $f_name; ?></h1>
                
                <?php if(!empty($food['avg_food_rating'])): ?>
                <div style="margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                    <i class="fa-solid fa-star" style="color: #ffca28; font-size: 1.2rem;"></i>
                    <strong style="font-size: 1.2rem;"><?php echo number_format($food['avg_food_rating'], 1); ?></strong>
                    <a href="#reviews" onclick="switchReviewTab('food')" style="color: #888; font-size: 0.95rem; margin-left: 4px; text-decoration: underline; cursor: pointer;">(<?php echo $food['food_rating_count']; ?> reviews)</a>
                </div>
                <?php endif; ?>

                <p class="f-seller"><i class="fa-solid fa-cookie-bite"></i> Prepared fresh by 
                    <a href="restaurant_menu.php?seller_id=<?php echo $food['seller_id']; ?>" style="color: var(--brand-green); font-weight: 600; text-decoration: none; border-bottom: 1px dotted var(--brand-green); padding-bottom: 2px; transition: opacity 0.2s;" onmouseover="this.style.opacity='0.8';" onmouseout="this.style.opacity='1';">
                        <?php echo htmlspecialchars($food['seller_name']); ?>
                    </a>
                    <?php if(!empty($food['avg_seller_rating'])): ?>
                        <a href="#reviews" onclick="switchReviewTab('seller')" style="text-decoration: none; margin-left: 12px; font-size: 0.85rem; background: #fff3e0; color: #f57c00; padding: 3px 10px; border-radius: 12px; display: inline-flex; align-items: center; gap: 4px; font-weight: 600; cursor: pointer;">
                            <i class="fa-solid fa-star"></i> <?php echo number_format($food['avg_seller_rating'], 1); ?> Seller Rating
                        </a>
                    <?php endif; ?>
                </p>
                
                <div class="f-price">₹<?php echo $f_price; ?></div>
                
                <div class="f-desc-label">About this item</div>
                <p class="f-desc"><?php echo $f_desc; ?></p>
                
                <?php if (!$is_time_available): ?>
                    <button disabled class="btn-add-cart btn-unavailable">
                        <i class="fa-solid fa-ban"></i> CURRENTLY UNAVAILABLE
                    </button>
                    <div class="availability-warning">
                        <i class="fa-regular fa-clock"></i> <?php echo $availability_message; ?>
                    </div>
                <?php else: ?>
                    <form action="handle_cart.php" method="POST">
                        <input type="hidden" name="food_id" value="<?php echo $food_id; ?>">
                        <button type="submit" name="action" value="add" class="btn-add-cart">
                            <i class="fa-solid fa-cart-plus"></i> ADD TO CART
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Customer Reviews Section -->
        <div id="reviews" class="reviews-container">
            <h2 class="reviews-header"><i class="fa-solid fa-comments" style="color: var(--brand-green);"></i> Customer Reviews</h2>
            
            <div style="display: flex; gap: 20px; border-bottom: 2px solid #eee; margin-bottom: 25px; padding-bottom: 10px;">
                <button id="btn-tab-food" class="review-tab-btn active" onclick="switchReviewTab('food')">Item Reviews (<?php echo count($food_reviews); ?>)</button>
                <button id="btn-tab-seller" class="review-tab-btn" onclick="switchReviewTab('seller')"><?php echo htmlspecialchars($food['seller_name']); ?>'s Ratings (<?php echo count($seller_reviews); ?>)</button>
            </div>

            <!-- Food Reviews -->
            <div id="rev-tab-food" style="display: block;">
                <?php if (count($food_reviews) > 0): ?>
                    <?php foreach($food_reviews as $rev): ?>
                        <div class="review-card">
                            <div class="review-top">
                                <div class="reviewer-info">
                                    <div class="reviewer-avatar">
                                        <?php echo strtoupper(substr($rev['reviewer_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div class="reviewer-name"><?php echo htmlspecialchars($rev['reviewer_name']); ?></div>
                                        <div class="review-date"><?php echo date('M d, Y', strtotime($rev['created_at'])); ?></div>
                                    </div>
                                </div>
                                <div class="review-stars">
                                    <?php 
                                    $r_val = intval($rev['food_rating']);
                                    for($i=1; $i<=5; $i++) {
                                        echo $i <= $r_val ? '★' : '<span style="color:#eee;">★</span>';
                                    }
                                    ?>
                                </div>
                            </div>
                            <?php if(!empty($rev['food_review'])): ?>
                                <div class="review-text">"<?php echo htmlspecialchars($rev['food_review']); ?>"</div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-reviews">
                        <i class="fa-regular fa-star" style="font-size: 2rem; color: #ddd; margin-bottom: 10px; display: block;"></i>
                        There are no reviews for this item yet. Be the first to try it!
                    </div>
                <?php endif; ?>
            </div>

            <!-- Seller Reviews -->
            <div id="rev-tab-seller" style="display: none;">
                <?php if (count($seller_reviews) > 0): ?>
                    <?php foreach($seller_reviews as $rev): ?>
                        <div class="review-card">
                            <div class="review-top">
                                <div class="reviewer-info">
                                    <div class="reviewer-avatar" style="background: #e8f5e9; color: #2e7d32;">
                                        <?php echo strtoupper(substr($rev['reviewer_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div class="reviewer-name"><?php echo htmlspecialchars($rev['reviewer_name']); ?></div>
                                        <div class="review-date"><?php echo date('M d, Y', strtotime($rev['created_at'])); ?></div>
                                    </div>
                                </div>
                                <div class="review-stars">
                                    <?php 
                                    $r_val = intval($rev['seller_rating']);
                                    for($i=1; $i<=5; $i++) {
                                        echo $i <= $r_val ? '★' : '<span style="color:#eee;">★</span>';
                                    }
                                    ?>
                                </div>
                            </div>
                            <?php if(!empty($rev['seller_review'])): ?>
                                <div class="review-text">"<?php echo htmlspecialchars($rev['seller_review']); ?>"</div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-reviews">
                        <i class="fa-solid fa-store" style="font-size: 2rem; color: #ddd; margin-bottom: 10px; display: block;"></i>
                        There are no reviews for this seller yet.
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
    </div>
    <script>
        function toggleSidebar(){ document.querySelector('.sidebar').classList.toggle('collapsed'); }
        
        function switchReviewTab(tab) {
            if (tab === 'food') {
                document.getElementById('rev-tab-food').style.display = 'block';
                document.getElementById('rev-tab-seller').style.display = 'none';
                document.getElementById('btn-tab-food').classList.add('active');
                document.getElementById('btn-tab-seller').classList.remove('active');
            } else {
                document.getElementById('rev-tab-food').style.display = 'none';
                document.getElementById('rev-tab-seller').style.display = 'block';
                document.getElementById('btn-tab-food').classList.remove('active');
                document.getElementById('btn-tab-seller').classList.add('active');
            }
        }

        // Smooth scroll for review anchor link
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html>
