<?php
include 'role_check.php';
check_role_access('customer');
include 'db_connect.php';
include_once 'helpers.php';

$user_id = $_SESSION['user_id'];
$user_name = htmlspecialchars($_SESSION['name']);

// Fetch Cart items to display summary
$cart_sql = "SELECT c.id, c.quantity, f.name, f.price, u.name as seller_name 
             FROM cart c 
             JOIN foods f ON c.food_id = f.id 
             JOIN users u ON f.seller_id = u.user_id 
             WHERE c.user_id = ?";
$stmt = $conn->prepare($cart_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$total_price = 0;
$cart_items = [];
while ($row = $result->fetch_assoc()) {
    $row['total'] = $row['price'] * $row['quantity'];
    $total_price += $row['total'];
    $cart_items[] = $row;
}
$stmt->close();

if (empty($cart_items)) {
    header("Location: customer_cart.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Homely Bites</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary-color: #fc8019; --brand-green: #0a8f08; --bg-body: #f8f8f8; --text-dark: #222; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: var(--bg-body); color: var(--text-dark); display: flex; min-height: 100vh; }
        .main-content { flex: 1; padding: 40px; }
        
        .checkout-container { max-width: 1000px; margin: 0 auto; display: flex; gap: 30px; }
        .checkout-form { flex: 2; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .order-summary { flex: 1; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); height: fit-content; }
        
        h2 { margin-bottom: 20px; font-size: 1.5rem; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; }
        .form-group input, .form-group textarea { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem; }
        
        .summary-item { display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 0.95rem; }
        .total-row { display: flex; justify-content: space-between; font-weight: 700; font-size: 1.2rem; margin-top: 20px; border-top: 1px solid #eee; padding-top: 15px; }
        
        .btn-pay { width: 100%; background: var(--brand-green); color: white; padding: 15px; border: none; border-radius: 8px; font-size: 1.1rem; font-weight: 600; cursor: pointer; margin-top: 20px; }
        .btn-pay:hover { background: #087f06; }
    </style>
</head>
<body>
    <?php include 'customer_sidebar.php'; ?>
    <div class="main-content">
        <div class="checkout-container">
            <div class="checkout-form">
                <h2>Delivery Details</h2>
                <form action="place_order.php" method="POST">
                    <div class="form-group">
                        <label>Delivery Address</label>
                        <textarea name="address" rows="3" required placeholder="Enter your full address"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Payment Method</label>
                        <div style="padding: 15px; border: 1px solid #ddd; border-radius: 8px;">
                            <label style="display: flex; align-items: center; gap: 10px; margin: 0;">
                                <input type="radio" name="payment_method" value="COD" checked> 
                                <i class="fa-solid fa-money-bill-wave" style="color: #2ecc71;"></i> Cash on Delivery
                            </label>
                        </div>
                    </div>
                    <input type="hidden" name="total_amount" value="<?php echo $total_price; ?>">
                    <button type="submit" class="btn-pay">Place Order</button>
                </form>
            </div>
            
            <div class="order-summary">
                <h2>Order Summary</h2>
                <?php foreach ($cart_items as $item): 
                     // Fix seller name fallback if valid column missing or empty
                     $seller_name = !empty($item['seller_name']) ? htmlspecialchars($item['seller_name']) : 'Homely Chef';
                ?>
                <div class="summary-item">
                    <div style="flex: 1;">
                        <span style="font-weight: 600;"><?php echo htmlspecialchars($item['name']); ?></span>
                        <div style="font-size: 0.8rem; color: #777;">x <?php echo $item['quantity']; ?></div>
                    </div>
                    <span>₹<?php echo $item['total']; ?></span>
                </div>
                <?php endforeach; ?>
                
                <div class="total-row">
                    <span>To Pay</span>
                    <span>₹<?php echo $total_price; ?></span>
                </div>
            </div>
        </div>
    </div>
    <script>function toggleSidebar(){ document.querySelector('.sidebar').classList.toggle('collapsed'); }</script>
</body>
</html>
