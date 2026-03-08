<?php
session_start();
include_once 'helpers.php';
// ACCESS CONTROL
if (!isset($_SESSION['user_id']) || !isset($_SESSION['delivery_approved']) || $_SESSION['delivery_approved'] != 1) {
    header("Location: customer_dashboard.php");
    exit();
}
include 'db_connect.php';
$formatted_name = formatName($_SESSION['name']);
$user_name = htmlspecialchars($formatted_name);
$user_initials = getAvatarInitials($formatted_name);
$profile_img = getProfileImage($_SESSION['user_id'], $conn);
$delivery_id = $_SESSION['user_id'];

// Handle Status Updates
$message = '';
$message_type = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = $_POST['update_status']; // 'Out for Delivery' or 'Delivered'
    
    // Verify this order belongs to this driver
    $check = $conn->prepare("SELECT order_id FROM orders WHERE order_id = ? AND delivery_partner_id = ?");
    $check->bind_param("ii", $order_id, $delivery_id);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $update = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
        $update->bind_param("si", $new_status, $order_id);
        if ($update->execute()) {
            // IF DELIVERED, LOG EARNINGS! -> Flat fee of ₹40
            if ($new_status === 'Delivered') {
                $flat_fee = 40.00;
                $earn_sql = "INSERT INTO delivery_earnings (delivery_partner_id, order_id, amount) VALUES (?, ?, ?)";
                $stmt_earn = $conn->prepare($earn_sql);
                $stmt_earn->bind_param("iid", $delivery_id, $order_id, $flat_fee);
                $stmt_earn->execute();
                $stmt_earn->close();
            }
            
            // Notify Customer of Status Change
            $cust_sql = "SELECT user_id FROM orders WHERE order_id = ?";
            $cust_stmt = $conn->prepare($cust_sql);
            $cust_stmt->bind_param("i", $order_id);
            $cust_stmt->execute();
            $cust_res = $cust_stmt->get_result()->fetch_assoc();
            if ($cust_res) {
                $title = "Delivery Update";
                $msg = "Your Order #$order_id is now: " . htmlspecialchars($new_status) . ".";
                $type = ($new_status === 'Delivered') ? "success" : "info";
                send_notification($conn, $cust_res['user_id'], $title, $msg, $type);
            }
            $cust_stmt->close();

            $message = "Order status updated to " . htmlspecialchars($new_status) . "!";
            $message_type = "success";
        }
    }
}

// Fetch Currently Active Orders for this driver
$active_orders = [];
$sql = "SELECT o.order_id, o.status, o.total_amount, o.address as dropoff_address, o.latitude, o.longitude, o.created_at, 
               s.name as seller_name, CONCAT(s.street, ', ', s.city, ' - ', s.pincode) as pickup_address, s.phone as seller_phone,
               c.name as customer_name, c.phone as customer_phone
        FROM orders o 
        JOIN users s ON o.seller_id = s.user_id 
        JOIN users c ON o.user_id = c.user_id
        WHERE o.delivery_partner_id = ? AND o.status IN ('Accepted by Delivery', 'Arrived at Restaurant', 'Out for Delivery')
        ORDER BY o.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $delivery_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $active_orders[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Active Deliveries - Homely Bites</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root { --brand-green: #008000; --bg-body: #f8f8f8; --card-bg: #FFFFFF; --shadow-card: 0 4px 14px rgba(0,0,0,0.08); }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: var(--bg-body); color: #222; display: flex; min-height: 100vh; }
        .main-content { flex: 1; display: flex; flex-direction: column; width: 0; }
        header { height: 80px; background-color: var(--card-bg); padding: 0 40px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 900; box-shadow: 0 2px 10px rgba(0,0,0,0.04); }
        .header-title h2 { font-size: 1.5rem; font-weight: 700; color: #333; }
        .user-info { display: flex; align-items: center; gap: 15px; text-align: right; }
        .profile-pic { width: 40px; height: 40px; background: #eee; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.9rem; color: #555; overflow: hidden; }
        .content-container { padding: 40px 60px; max-width: 1400px; margin: 0 auto; width: 100%; }
        
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-weight: 500;}
        .alert-error { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
        .alert-success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        
        .active-card { background: white; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); border: 2px solid var(--brand-green); overflow: hidden; margin-bottom: 30px; }
        .active-header { background: var(--brand-green); color: white; padding: 20px 24px; display: flex; justify-content: space-between; align-items: center; }
        .active-header h3 { font-size: 1.2rem; margin: 0; }
        .status-badge { background: rgba(255,255,255,0.2); padding: 5px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; letter-spacing: 0.5px; }
        
        .active-body { padding: 30px; display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
        
        .contact-box { background: #f9f9f9; padding: 20px; border-radius: 12px; border: 1px solid #eee; }
        .contact-box h4 { font-size: 1rem; color: #333; margin-bottom: 15px; display: flex; align-items: center; gap: 8px; }
        .contact-box .person-name { font-weight: 700; font-size: 1.1rem; color: #222; margin-bottom: 5px; }
        .contact-box .address { color: #555; font-size: 0.95rem; margin-bottom: 15px; line-height: 1.5; }
        .call-btn { display: inline-flex; align-items: center; gap: 8px; background: white; color: #3b82f6; border: 1px solid #bfdbfe; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.9rem; transition: all 0.2s; }
        .call-btn:hover { background: #eff6ff; }
        
        .action-bar { padding: 20px 30px; background: #fafafa; border-top: 1px solid #eee; display: flex; justify-content: flex-end; gap: 15px; }
        .btn { padding: 12px 24px; border-radius: 8px; font-weight: 600; font-size: 1rem; cursor: pointer; transition: all 0.2s; border: none; }
        
        /* Swipe to deliver imitation - simple buttons for now */
        .btn-pickup { background: #3b82f6; color: white; box-shadow: 0 4px 10px rgba(59, 130, 246, 0.3); }
        .btn-pickup:hover { background: #2563eb; transform: translateY(-2px); }
        
        .btn-deliver { background: var(--brand-green); color: white; box-shadow: 0 4px 10px rgba(0, 128, 0, 0.3); }
        .btn-deliver:hover { background: #006600; transform: translateY(-2px); }
        
        .empty-state { text-align: center; padding: 60px 20px; background: white; border-radius: 16px; box-shadow: var(--shadow-card); }
        .empty-state p { font-size: 1.1rem; color: #888; margin-top: 15px; }
    </style>
</head>
<body>

    <?php include 'delivery_sidebar.php'; ?>

    <div class="main-content">
        <header>
            <div class="header-title"><h2>Active Deliveries</h2></div>
            <div class="user-info">
                <div><p style="font-weight: 700; font-size: 0.95rem; margin-bottom: 2px; color: var(--text-dark);"><?php echo $user_name; ?></p></div>
                <div class="profile-pic"><?php if ($profile_img): ?><img src="<?php echo $profile_img; ?>" style="width: 100%; height: 100%; object-fit: cover;"><?php else: ?><?php echo $user_initials; ?><?php endif; ?></div>
            </div>
        </header>

        <div class="content-container">
            <?php if ($message): ?> <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div> <?php endif; ?>
            
            <?php foreach($active_orders as $order): ?>
                <div class="active-card">
                    <div class="active-header">
                        <h3>Order #<?php echo $order['order_id']; ?></h3>
                        <span class="status-badge"><i class="fa-solid fa-motorcycle" style="margin-right: 5px;"></i> <?php echo htmlspecialchars($order['status']); ?></span>
                    </div>
                    
                    <div class="active-body">
                        <!-- Pickup Details -->
                        <div class="contact-box">
                            <h4><i class="fa-solid fa-store" style="color: #3b82f6;"></i> Pickup From Seller</h4>
                            <div class="person-name"><?php echo htmlspecialchars($order['seller_name']); ?></div>
                            <div class="address" title="<?php echo htmlspecialchars($order['pickup_address']); ?>"><?php echo htmlspecialchars($order['pickup_address']); ?></div>
                            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                <a href="tel:<?php echo htmlspecialchars($order['seller_phone']); ?>" class="call-btn"><i class="fa-solid fa-phone"></i> Call Seller</a>
                                <button type="button" class="call-btn" onclick="copyToClipboard('<?php echo htmlspecialchars($order['seller_phone']); ?>')" style="cursor: pointer; background: #f8fafc; color: #475569; border: 1px solid #cbd5e1;"><i class="fa-regular fa-copy"></i> Copy</button>
                            </div>
                        </div>
                        
                        <!-- Dropoff Details -->
                        <div class="contact-box">
                            <h4><i class="fa-solid fa-house" style="color: #f97316;"></i> Deliver To Customer</h4>
                            <div class="person-name"><?php echo htmlspecialchars($order['customer_name']); ?></div>
                            <div class="address" title="<?php echo htmlspecialchars($order['dropoff_address']); ?>"><?php echo htmlspecialchars($order['dropoff_address']); ?></div>
                            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                <a href="tel:<?php echo htmlspecialchars($order['customer_phone']); ?>" class="call-btn"><i class="fa-solid fa-phone"></i> Call</a>
                                <button type="button" class="call-btn" onclick="copyToClipboard('<?php echo htmlspecialchars($order['customer_phone']); ?>')" style="cursor: pointer; background: #f8fafc; color: #475569; border: 1px solid #cbd5e1;"><i class="fa-regular fa-copy"></i> Copy</button>
                                <?php if (!empty($order['latitude']) && !empty($order['longitude'])): ?>
                                <a href="https://www.google.com/maps/dir/?api=1&destination=<?php echo urlencode($order['latitude'] . ',' . $order['longitude']); ?>" target="_blank" class="call-btn" style="color: #0a8f08; border-color: #bbf7d0;"><i class="fa-solid fa-map-location-dot"></i> Navigate</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="action-bar">
                        <form method="POST">
                            <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                            <?php if ($order['status'] === 'Accepted by Delivery'): ?>
                                <button type="submit" name="update_status" value="Arrived at Restaurant" class="btn btn-pickup" style="background: #8e44ad; box-shadow: 0 4px 10px rgba(142, 68, 173, 0.3);"><i class="fa-solid fa-location-dot" style="margin-right: 8px;"></i> Arrived at Restaurant</button>
                            <?php elseif ($order['status'] === 'Arrived at Restaurant'): ?>
                                <button type="submit" name="update_status" value="Out for Delivery" class="btn btn-pickup"><i class="fa-solid fa-box-open" style="margin-right: 8px;"></i> Mark as Picked Up</button>
                            <?php elseif ($order['status'] === 'Out for Delivery'): ?>
                                <button type="submit" name="update_status" value="Delivered" class="btn btn-deliver"><i class="fa-solid fa-check-double" style="margin-right: 8px;"></i> Mark as Delivered</button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if(empty($active_orders)): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-route" style="font-size: 3.5rem; color: #ddd; margin-bottom: 20px;"></i>
                    <h3 style="color: #333; margin-bottom: 8px;">No Active Deliveries</h3>
                    <p style="margin-top:0;">You are not currently delivering any orders. Check the Available Orders page.</p>
                    <a href="delivery_orders.php" class="btn" style="background: var(--brand-green); color: white; display: inline-block; margin-top: 15px; text-decoration: none;">Find Orders</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleSidebar() { document.querySelector('.sidebar').classList.toggle('collapsed'); }
        
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                showToast('Phone number copied: ' + text);
            }).catch(err => {
                console.error('Failed to copy: ', err);
            });
        }

        function showToast(message) {
            let toast = document.createElement('div');
            toast.textContent = message;
            toast.style.position = 'fixed';
            toast.style.bottom = '20px';
            toast.style.right = '20px';
            toast.style.backgroundColor = '#4caf50';
            toast.style.color = '#fff';
            toast.style.padding = '12px 24px';
            toast.style.borderRadius = '8px';
            toast.style.boxShadow = '0 4px 6px rgba(0,0,0,0.1)';
            toast.style.fontFamily = 'Arial, sans-serif';
            toast.style.fontSize = '14px';
            toast.style.zIndex = '9999';
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 0.3s ease-in-out';
            document.body.appendChild(toast);
            
            setTimeout(() => { toast.style.opacity = '1'; }, 10);
            
            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
    </script>
</body>
</html>
