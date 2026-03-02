<?php
session_start();
include_once 'helpers.php';
// ACCESS CONTROL: Strict check for Delivery Approval
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

// Handle Acceptance
$message = '';
$message_type = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'accept') {
    $order_id = intval($_POST['order_id']);
    
    // Ensure order is actually pending and unassigned to prevent race conditions
    $check = $conn->prepare("SELECT order_id FROM orders WHERE order_id = ? AND status IN ('Pending', 'Preparing', 'Ready for Pickup') AND delivery_partner_id IS NULL");
    $check->bind_param("i", $order_id);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $update = $conn->prepare("UPDATE orders SET status = 'Accepted by Delivery', delivery_partner_id = ? WHERE order_id = ?");
        $update->bind_param("ii", $delivery_id, $order_id);
        if ($update->execute()) {
            $_SESSION['active_delivery'] = $order_id;
            header("Location: delivery_active.php");
            exit();
        } else {
             $message = "Error assigning order to you.";
             $message_type = "error";
        }
    } else {
        $message = "This order was already accepted by someone else or is no longer available.";
        $message_type = "error";
    }
}

// Fetch Available Orders
$available_orders = [];
$sql = "SELECT o.order_id, o.total_amount, o.address as dropoff_address, o.created_at, 
               s.name as seller_name, CONCAT(s.street, ', ', s.city, ' - ', s.pincode) as pickup_address, s.phone as seller_phone
        FROM orders o 
        JOIN users s ON o.seller_id = s.user_id 
        WHERE o.status IN ('Pending', 'Preparing', 'Ready for Pickup') AND o.delivery_partner_id IS NULL 
        ORDER BY o.created_at ASC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $available_orders[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Orders - Delivery</title>
    <!-- Fonts -->
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
        
        .orders-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 24px; }
        
        .order-card { background: white; border-radius: 16px; padding: 24px; box-shadow: var(--shadow-card); border: 1px solid rgba(0,0,0,0.03); transition: transform 0.2s; position: relative; overflow: hidden; }
        .order-card:hover { transform: translateY(-4px); box-shadow: 0 8px 20px rgba(0,0,0,0.12); }
        
        .order-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px dashed #eee; }
        .order-id { font-weight: 700; font-size: 1.1rem; color: #333; }
        .order-amount { font-weight: 700; font-size: 1.2rem; color: var(--brand-green); }
        
        .location-step { display: flex; gap: 15px; margin-bottom: 20px; position: relative; }
        .location-step::before { content: ''; position: absolute; left: 11px; top: 25px; bottom: -15px; border-left: 2px dashed #ddd; }
        .location-step:last-child::before { display: none; }
        
        .loc-icon { width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 10px; color: white; z-index: 2; }
        .pickup-icon { background: #3b82f6; } /* Blue for pickup */
        .dropoff-icon { background: #f97316; } /* Orange for dropoff */
        
        .loc-details h4 { font-size: 0.9rem; color: #666; font-weight: 600; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.5px; }
        .loc-details p { font-size: 1rem; color: #222; font-weight: 500; line-height: 1.4; }
        .loc-details .sub-text { font-size: 0.85rem; color: #888; margin-top: 4px; }
        
        .btn-accept { width: 100%; background: var(--brand-green); color: white; border: none; padding: 14px; border-radius: 8px; font-weight: 600; font-size: 1rem; cursor: pointer; transition: all 0.2s; margin-top: 10px; box-shadow: 0 4px 10px rgba(0, 128, 0, 0.2); }
        .btn-accept:hover { background: #006600; transform: translateY(-2px); box-shadow: 0 6px 15px rgba(0, 128, 0, 0.3); }
        
        .empty-state { text-align: center; padding: 60px 20px; background: white; border-radius: 16px; box-shadow: var(--shadow-card); grid-column: 1 / -1; }
        .empty-state p { font-size: 1.1rem; color: #888; margin-top: 15px; }
    </style>
</head>
<body>

    <?php include 'delivery_sidebar.php'; ?>

    <div class="main-content">
        <header>
            <div class="header-title">
                <h2>Available Orders</h2>
            </div>
            <div class="user-info">
                <div>
                    <p style="font-weight: 700; font-size: 0.95rem; margin-bottom: 2px; color: var(--text-dark);"><?php echo $user_name; ?></p>
                </div>
                <div class="profile-pic">
                    <?php if ($profile_img): ?><img src="<?php echo $profile_img; ?>" style="width: 100%; height: 100%; object-fit: cover;"><?php else: ?><?php echo $user_initials; ?><?php endif; ?>
                </div>
            </div>
        </header>

        <div class="content-container">
            <?php if ($message): ?> <div class="alert alert-<?php echo $message_type; ?>"><i class="fa-solid fa-circle-exclamation" style="margin-right: 8px;"></i> <?php echo $message; ?></div> <?php endif; ?>
            
            <div class="orders-grid">
                <?php foreach($available_orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <div class="order-id">Order #<?php echo $order['order_id']; ?></div>
                            <div style="font-size: 0.8rem; color: #888; margin-top: 4px;"><i class="fa-regular fa-clock"></i> <?php echo time_elapsed_string($order['created_at']); ?></div>
                        </div>
                        <div class="order-amount">₹<?php echo number_format($order['total_amount'], 2); ?></div>
                    </div>
                    
                    <div class="location-step">
                        <div class="loc-icon pickup-icon"><i class="fa-solid fa-store"></i></div>
                        <div class="loc-details">
                            <h4>Pickup From</h4>
                            <p><?php echo htmlspecialchars($order['seller_name']); ?></p>
                            <div class="sub-text"><i class="fa-solid fa-location-dot" style="margin-right:4px;"></i><?php echo htmlspecialchars($order['pickup_address']); ?></div>
                        </div>
                    </div>
                    
                    <div class="location-step">
                        <div class="loc-icon dropoff-icon"><i class="fa-solid fa-house"></i></div>
                        <div class="loc-details">
                            <h4>Deliver To</h4>
                            <p><?php echo htmlspecialchars($order['dropoff_address']); ?></p>
                        </div>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="accept">
                        <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                        <button type="submit" class="btn-accept">Accept Delivery</button>
                    </form>
                </div>
                <?php endforeach; ?>
                
                <?php if(empty($available_orders)): ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-mug-hot" style="font-size: 3.5rem; color: #ddd; margin-bottom: 20px;"></i>
                        <h3 style="color: #333; margin-bottom: 8px;">No Orders Right Now</h3>
                        <p style="margin-top:0;">Take a quick break. New orders will appear here automatically.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() { document.querySelector('.sidebar').classList.toggle('collapsed'); }
    </script>
</body>
</html>
<?php
// Quick helper for time elapsed
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;
    $string = array('y' => 'year','m' => 'month','w' => 'week','d' => 'day','h' => 'hr','i' => 'min','s' => 'sec');
    foreach ($string as $k => &$v) {
        if ($diff->$k) { $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : ''); } else { unset($string[$k]); }
    }
    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}
?>
