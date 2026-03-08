<?php
include 'role_check.php';
check_role_access('customer');
include 'db_connect.php';
include_once 'helpers.php';

if (!isset($_GET['id'])) {
    header("Location: customer_orders.php");
    exit();
}

$order_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];
$user_name = htmlspecialchars($_SESSION['name']);

// Fetch Order Details
$sql = "SELECT o.*, u.name as seller_name, u.phone as seller_phone 
        FROM orders o 
        LEFT JOIN users u ON o.seller_id = u.user_id 
        WHERE o.order_id = ? AND o.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: customer_orders.php"); // Order not found or access denied
    exit();
}
$order = $result->fetch_assoc();
$stmt->close();

// Fetch Order Items
$items_sql = "SELECT oi.food_id, oi.quantity, oi.price, oi.food_rating, oi.food_review, f.name, f.image 
              FROM order_items oi 
              JOIN foods f ON oi.food_id = f.id 
              WHERE oi.order_id = ?";
$stmt_items = $conn->prepare($items_sql);
$stmt_items->bind_param("i", $order_id);
$stmt_items->execute();
$res_items = $stmt_items->get_result();
$order_items = [];
while ($row = $res_items->fetch_assoc()) {
    $order_items[] = $row;
}
$stmt_items->close();

// Fallback for legacy items string
if (empty($order_items) && !empty($order['items'])) {
    // Legacy format: "Item1 (2), Item2 (1)"
    // We can just display it as a single line item or parse it if strictly needed.
    // For simplicity, we treat it as one special item row
    $order_items[] = [
        'name' => $order['items'], 
        'quantity' => '-', 
        'price' => $order['total_amount'], // approximate since per-item price lost
        'image' => '' 
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?php echo $order_id; ?> Details - Homely Bites</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary-color: #fc8019; --brand-green: #0a8f08; --bg-body: #f8f8f8; --text-dark: #222; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: var(--bg-body); color: var(--text-dark); display: flex; min-height: 100vh; }
        .main-content { flex: 1; padding: 40px; }
        
        .details-container { max-width: 900px; margin: 0 auto; background: white; border-radius: 12px; padding: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        
        .header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 20px; }
        .order-id { font-size: 1.5rem; font-weight: 700; color: #333; }
        .status-badge { padding: 8px 16px; border-radius: 20px; font-weight: 600; font-size: 0.9rem; text-transform: uppercase; }
        .status-badge.Preparing { background: #fff3e0; color: #f57c00; }
        .status-badge.Out { background: #e3f2fd; color: #1976d2; } /* Out for Delivery */
        .status-badge.Delivered { background: #e8f5e9; color: #2e7d32; }
        .status-badge.Cancelled { background: #ffebee; color: #c62828; }
        
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px; }
        .info-box h4 { color: #888; font-size: 0.9rem; text-transform: uppercase; margin-bottom: 8px; }
        .info-box p { font-size: 1rem; font-weight: 500; }
        
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .items-table th { text-align: left; padding: 12px; border-bottom: 2px solid #f0f0f0; color: #888; }
        .items-table td { padding: 15px 12px; border-bottom: 1px solid #f9f9f9; }
        .item-row { display: flex; align-items: center; gap: 15px; }
        .item-img { width: 50px; height: 50px; border-radius: 8px; background: #eee; object-fit: cover; }
        
        .total-section { text-align: right; border-top: 1px solid #eee; padding-top: 20px; }
        .grand-total { font-size: 1.3rem; font-weight: 700; color: var(--brand-green); }
        
        .back-link { display: inline-flex; align-items: center; gap: 5px; text-decoration: none; color: #666; font-weight: 500; margin-bottom: 20px; }
        .back-link:hover { color: var(--brand-green); }

        /* Print Styles */
        @media print {
            .sidebar, .back-link, .btn-download { display: none !important; }
            .main-content { padding: 0; }
            .details-container { box-shadow: none; border: 1px solid #eee; margin: 0; width: 100%; max-width: 100%; }
            body { background: white; }
        }

        .btn-download {
            background: #fff;
            border: 1px solid var(--brand-green);
            color: var(--brand-green);
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.3s;
        }
        .btn-download:hover { background: #f0fff0; }

        /* Rating System Styles */
        .feedback-section { margin-top: 40px; background: white; border-radius: 12px; padding: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .feedback-title { font-size: 1.25rem; font-weight: 700; margin-bottom: 20px; color: var(--text-dark); border-bottom: 2px solid #f0f0f0; padding-bottom: 10px; }
        .rating-group { margin-bottom: 25px; padding: 20px; border: 1px solid #f0f0f0; border-radius: 10px; background: #fafafa; }
        .rating-target { font-weight: 600; font-size: 1.05rem; margin-bottom: 10px; display: flex; align-items: center; gap: 8px; color: #333; }
        .star-rating {
            display: inline-flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
            margin-bottom: 10px;
        }
        .star-rating input { display: none; }
        .star-rating label { color: #ddd; font-size: 24px; padding: 0 2px; cursor: pointer; transition: color 0.2s; }
        .star-rating input:checked ~ label,
        .star-rating label:hover,
        .star-rating label:hover ~ label { color: #ffca28; }
        
        .static-stars { color: #ffca28; font-size: 18px; letter-spacing: 2px; }
        .static-stars.unrated { color: #ddd; }

        .review-textarea { width: 100%; border: 1px solid #ddd; border-radius: 8px; padding: 12px; margin-top: 10px; font-size: 0.95rem; resize: vertical; min-height: 80px; outline: none; }
        .review-textarea:focus { border-color: var(--primary-color); }
        .btn-submit-review { background: var(--primary-color); color: white; border: none; padding: 12px 30px; border-radius: 8px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; font-size: 1rem; transition: 0.3s; margin-top: 10px; }
        .btn-submit-review:hover { background: #e67312; }
        .submitted-review-text { color: #666; font-style: italic; background: #fff; padding: 10px 15px; border-left: 3px solid #ddd; margin-top: 8px; font-size: 0.95rem; }
    </style>
</head>
<body>
    <?php include 'customer_sidebar.php'; ?>
    <div class="main-content">
        <div style="max-width: 900px; margin: 0 auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <a href="customer_orders.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back to Orders</a>
                <a href="download_receipt.php?id=<?php echo $order['order_id']; ?>" target="_blank" class="btn-download">
                    <i class="fa-solid fa-download"></i> Download Receipt
                </a>
            </div>
            <div class="details-container" id="receiptContent">
                <div class="header-row">
                    <div>
                        <div class="order-id">Order #HB-<?php echo 1000 + $order['order_id']; ?></div>
                        <div style="color: #777; font-size: 0.9rem; margin-top: 4px;">Placed on <?php echo date("d M Y, h:i A", strtotime($order['created_at'])); ?></div>
                    </div>
                    <?php 
                        $status_label = !empty($order['status']) ? $order['status'] : 'Pending';
                        $status_class = $status_label;
                        if($status_label == 'Out for Delivery') $status_class = 'Out';
                        if($status_label == 'Pending') $status_class = 'Preparing'; // Use preparing/orange style for pending
                    ?>
                    <span class="status-badge <?php echo $status_class; ?>"><?php echo $status_label; ?></span>
                </div>
                
                <div class="info-grid">
                    <div class="info-box">
                        <h4>Delivery Address</h4>
                        <p><?php echo !empty($order['address']) ? nl2br(htmlspecialchars($order['address'])) : 'No address provided'; ?></p>
                    </div>
                    <div class="info-box">
                        <h4>Seller Details</h4>
                        <p><?php echo htmlspecialchars($order['seller_name']); ?></p>
                        <?php if($order['seller_phone']): ?>
                            <p style="font-size: 0.9rem; color: #666;"><i class="fa-solid fa-phone"></i> <?php echo htmlspecialchars($order['seller_phone']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <h4 style="margin-bottom: 15px;">Items Ordered</h4>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Quantity</th>
                            <th style="text-align: right;">Price</th>
                            <th style="text-align: right;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($order_items as $item): 
                            $item_total = is_numeric($item['quantity']) ? $item['price'] * $item['quantity'] : $item['price'];
                        ?>
                        <tr>
                            <td>
                                <div class="item-row">
                                    <?php 
                                        $img_src = !empty($item['image']) ? $item['image'] : 'assets/images/placeholder-food.png';
                                        // If image path doesn't start with assets/ or http, assume it's in assets/images/ if it's a seed data
                                        if (!empty($item['image']) && !filter_var($item['image'], FILTER_VALIDATE_URL) && strpos($item['image'], '/') === false) {
                                            $img_src = 'assets/images/' . $item['image'];
                                        }
                                    ?>
                                    <img src="<?php echo htmlspecialchars($img_src); ?>" class="item-img" alt="" onerror="this.src='assets/images/placeholder-food.png'">
                                    <span><?php echo htmlspecialchars($item['name']); ?></span>
                                </div>
                            </td>
                            <td>x <?php echo $item['quantity']; ?></td>
                            <td style="text-align: right;">₹<?php echo $item['price']; ?></td>
                            <td style="text-align: right; font-weight: 600;">₹<?php echo $item_total; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="total-section">
                    <div style="margin-bottom: 5px; color: #666;">Payment: <?php echo htmlspecialchars($order['payment_method'] ?? 'COD'); ?></div>
                    <div class="grand-total">Total Bill: ₹<?php echo number_format($order['total_amount']); ?></div>
                </div>
            </div>

            <?php if ($order['status'] === 'Delivered'): ?>
            <div class="feedback-section" id="feedbackSection">
                <h3 class="feedback-title"><i class="fa-solid fa-star" style="color: #ffca28; margin-right: 8px;"></i> Rate Your Experience</h3>
                
                <?php 
                // Check if already reviewed (Seller rating serves as proxy for overall order reviewed state)
                $is_reviewed = !empty($order['seller_rating']) || !empty($order['delivery_rating']); 
                
                if ($is_reviewed): 
                ?>
                    <!-- DISPLAY SUBMITTED REVIEWS -->
                    
                    <!-- Food Feedback -->
                    <div class="rating-group">
                        <div class="rating-target"><i class="fa-solid fa-utensils"></i> Food Provided</div>
                        <?php foreach($order_items as $item): ?>
                            <div style="margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px dashed #eee;">
                                <div style="font-weight: 500; margin-bottom: 5px;"><?php echo htmlspecialchars($item['name']); ?></div>
                                <div class="static-stars <?php echo empty($item['food_rating']) ? 'unrated' : ''; ?>">
                                    <?php 
                                    $f_rating = intval($item['food_rating']);
                                    for($i=1; $i<=5; $i++) {
                                        echo $i <= $f_rating ? '★' : '☆';
                                    }
                                    ?>
                                </div>
                                <?php if(!empty($item['food_review'])): ?>
                                    <div class="submitted-review-text">"<?php echo htmlspecialchars($item['food_review']); ?>"</div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Seller Feedback -->
                    <div class="rating-group">
                        <div class="rating-target"><i class="fa-solid fa-store"></i> Seller: <?php echo htmlspecialchars($order['seller_name']); ?></div>
                        <div class="static-stars <?php echo empty($order['seller_rating']) ? 'unrated' : ''; ?>">
                            <?php 
                            $s_rating = intval($order['seller_rating']);
                            for($i=1; $i<=5; $i++) {
                                echo $i <= $s_rating ? '★' : '☆';
                            }
                            ?>
                        </div>
                        <?php if(!empty($order['seller_review'])): ?>
                            <div class="submitted-review-text">"<?php echo htmlspecialchars($order['seller_review']); ?>"</div>
                        <?php endif; ?>
                    </div>

                    <!-- Delivery Feedback -->
                    <div class="rating-group">
                        <div class="rating-target"><i class="fa-solid fa-motorcycle"></i> Delivery Partner</div>
                        <div class="static-stars <?php echo empty($order['delivery_rating']) ? 'unrated' : ''; ?>">
                            <?php 
                            $d_rating = intval($order['delivery_rating']);
                            for($i=1; $i<=5; $i++) {
                                echo $i <= $d_rating ? '★' : '☆';
                            }
                            ?>
                        </div>
                        <?php if(!empty($order['delivery_review'])): ?>
                            <div class="submitted-review-text">"<?php echo htmlspecialchars($order['delivery_review']); ?>"</div>
                        <?php endif; ?>
                    </div>
                
                <?php else: ?>
                    <!-- REVIEW SUBMISSION FORM -->
                    <form action="submit_review.php" method="POST">
                        <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                        
                        <!-- 1. Rate Food Items -->
                        <div class="rating-group">
                            <div class="rating-target"><i class="fa-solid fa-utensils"></i> Rate the Food</div>
                            <?php foreach($order_items as $item): 
                                // Skip legacy fallback item which doesn't have a real food_id
                                if(!isset($item['food_id'])) continue; 
                                $fid = $item['food_id'];
                            ?>
                                <div style="margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px dashed #eee;">
                                    <div style="font-weight: 500; margin-bottom: 10px;"><?php echo htmlspecialchars($item['name']); ?></div>
                                    <div class="star-rating">
                                        <input type="radio" id="f5_<?php echo $fid; ?>" name="food_ratings[<?php echo $fid; ?>]" value="5"><label for="f5_<?php echo $fid; ?>">★</label>
                                        <input type="radio" id="f4_<?php echo $fid; ?>" name="food_ratings[<?php echo $fid; ?>]" value="4"><label for="f4_<?php echo $fid; ?>">★</label>
                                        <input type="radio" id="f3_<?php echo $fid; ?>" name="food_ratings[<?php echo $fid; ?>]" value="3"><label for="f3_<?php echo $fid; ?>">★</label>
                                        <input type="radio" id="f2_<?php echo $fid; ?>" name="food_ratings[<?php echo $fid; ?>]" value="2"><label for="f2_<?php echo $fid; ?>">★</label>
                                        <input type="radio" id="f1_<?php echo $fid; ?>" name="food_ratings[<?php echo $fid; ?>]" value="1"><label for="f1_<?php echo $fid; ?>">★</label>
                                    </div>
                                    <textarea name="food_reviews[<?php echo $fid; ?>]" class="review-textarea" placeholder="How did this taste? Is there anything you loved about it?"></textarea>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- 2. Rate Seller -->
                        <div class="rating-group">
                            <div class="rating-target"><i class="fa-solid fa-store"></i> Rate the Seller (<?php echo htmlspecialchars($order['seller_name']); ?>)</div>
                            <div class="star-rating">
                                <input type="radio" id="s5" name="seller_rating" value="5"><label for="s5">★</label>
                                <input type="radio" id="s4" name="seller_rating" value="4"><label for="s4">★</label>
                                <input type="radio" id="s3" name="seller_rating" value="3"><label for="s3">★</label>
                                <input type="radio" id="s2" name="seller_rating" value="2"><label for="s2">★</label>
                                <input type="radio" id="s1" name="seller_rating" value="1"><label for="s1">★</label>
                            </div>
                            <textarea name="seller_review" class="review-textarea" placeholder="How was the packaging and overall service from this seller?"></textarea>
                        </div>

                        <!-- 3. Rate Delivery -->
                        <div class="rating-group">
                            <div class="rating-target"><i class="fa-solid fa-motorcycle"></i> Rate the Delivery Partner</div>
                            <div class="star-rating">
                                <input type="radio" id="op5" name="delivery_rating" value="5"><label for="op5">★</label>
                                <input type="radio" id="op4" name="delivery_rating" value="4"><label for="op4">★</label>
                                <input type="radio" id="op3" name="delivery_rating" value="3"><label for="op3">★</label>
                                <input type="radio" id="op2" name="delivery_rating" value="2"><label for="op2">★</label>
                                <input type="radio" id="op1" name="delivery_rating" value="1"><label for="op1">★</label>
                            </div>
                            <textarea name="delivery_review" class="review-textarea" placeholder="How was your delivery experience? Was the driver polite and timely?"></textarea>
                        </div>

                        <button type="submit" class="btn-submit-review">
                            <i class="fa-solid fa-paper-plane"></i> Submit Feedback
                        </button>
                    </form>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <script>function toggleSidebar(){ document.querySelector('.sidebar').classList.toggle('collapsed'); }</script>
</body>
</html>
