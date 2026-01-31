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
$items_sql = "SELECT oi.quantity, oi.price, f.name, f.image 
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
        </div>
    </div>
    <script>function toggleSidebar(){ document.querySelector('.sidebar').classList.toggle('collapsed'); }</script>
</body>
</html>
