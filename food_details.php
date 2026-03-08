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
$stmt = $conn->prepare("SELECT f.*, u.name as seller_name FROM foods f JOIN users u ON f.seller_id = u.user_id WHERE f.id = ?");
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

if (!empty($s1_start) || !empty($s2_start)) {
    $current_time = date('H:i:s');
    $in_slot1 = (!empty($s1_start) && !empty($s1_end) && $current_time >= $s1_start && $current_time <= $s1_end);
    $in_slot2 = (!empty($s2_start) && !empty($s2_end) && $current_time >= $s2_start && $current_time <= $s2_end);
    
    if (!$in_slot1 && !$in_slot2) {
        $is_time_available = false;
        $mssg = [];
        if(!empty($s1_start)) $mssg[] = date('h:i A', strtotime($s1_start)) . " - " . date('h:i A', strtotime($s1_end));
        if(!empty($s2_start)) $mssg[] = date('h:i A', strtotime($s2_start)) . " - " . date('h:i A', strtotime($s2_end));
        $availability_message = "Available: " . implode(" & ", $mssg);
    }
}

$stmt->close();
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
                <p class="f-seller"><i class="fa-solid fa-cookie-bite"></i> Prepared fresh by <strong><?php echo htmlspecialchars($food['seller_name']); ?></strong></p>
                
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
    </div>
    <script>function toggleSidebar(){ document.querySelector('.sidebar').classList.toggle('collapsed'); }</script>
</body>
</html>
