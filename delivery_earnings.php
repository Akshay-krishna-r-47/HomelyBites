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

// Fetch Total Earnings
$total_earned = 0.00;
$stmt_total = $conn->prepare("SELECT SUM(amount) as total FROM delivery_earnings WHERE delivery_partner_id = ?");
$stmt_total->bind_param("i", $delivery_id);
$stmt_total->execute();
$res_total = $stmt_total->get_result();
if ($res_total->num_rows > 0) {
    $row_total = $res_total->fetch_assoc();
    if ($row_total['total']) { $total_earned = $row_total['total']; }
}
$stmt_total->close();

// Fetch History
$history = [];
$sql = "SELECT e.amount, e.created_at, o.order_id, o.address as dropoff_address 
        FROM delivery_earnings e 
        JOIN orders o ON e.order_id = o.order_id 
        WHERE e.delivery_partner_id = ? 
        ORDER BY e.created_at DESC";
$stmt_hist = $conn->prepare($sql);
$stmt_hist->bind_param("i", $delivery_id);
$stmt_hist->execute();
$res_hist = $stmt_hist->get_result();
while ($row = $res_hist->fetch_assoc()) {
    $history[] = $row;
}
$stmt_hist->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Earnings - Delivery Partner</title>
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
        
        /* Wallet Box */
        .wallet-card { background: linear-gradient(135deg, var(--brand-green), #0a8f08); border-radius: 20px; padding: 40px; color: white; margin-bottom: 40px; box-shadow: 0 10px 30px rgba(0, 128, 0, 0.2); position: relative; overflow: hidden; }
        .wallet-card::after { content: '\f53d'; font-family: 'Font Awesome 6 Free'; font-weight: 900; position: absolute; right: 20px; top: 50%; transform: translateY(-50%); font-size: 12rem; opacity: 0.1; line-height: 1; }
        .wallet-title { font-size: 1.1rem; font-weight: 500; opacity: 0.9; margin-bottom: 5px; }
        .wallet-amount { font-size: 3.5rem; font-weight: 700; letter-spacing: -1px; margin-bottom: 20px; display: flex; align-items: center; }
        
        .btn-withdraw { background: white; color: var(--brand-green); border: none; padding: 12px 30px; border-radius: 30px; font-weight: 700; font-size: 1rem; cursor: pointer; transition: transform 0.2s; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .btn-withdraw:hover { transform: translateY(-2px); }
        
        /* History Table */
        .history-card { background: white; border-radius: 16px; padding: 24px; box-shadow: var(--shadow-card); }
        .history-card h3 { font-size: 1.3rem; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
        
        .table { width: 100%; border-collapse: collapse; }
        .table th { text-align: left; padding: 15px; color: #888; font-weight: 600; font-size: 0.9rem; text-transform: uppercase; border-bottom: 1px solid #eee; }
        .table td { padding: 16px 15px; border-bottom: 1px solid #f5f5f5; vertical-align: middle; }
        
        .td-order { font-weight: 600; color: #333; }
        .td-date { color: #888; font-size: 0.9rem; }
        .td-loc { color: #555; max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block; }
        .td-amount { font-weight: 700; color: var(--brand-green); font-size: 1.1rem; }
        
        .empty-state { text-align: center; padding: 40px; color: #888; }
    </style>
</head>
<body>

    <?php include 'delivery_sidebar.php'; ?>

    <div class="main-content">
        <header>
            <div class="header-title"><h2>Earnings & Payouts</h2></div>
            <div class="user-info">
                <div><p style="font-weight: 700; font-size: 0.95rem; margin-bottom: 2px; color: var(--text-dark);"><?php echo $user_name; ?></p></div>
                <div class="profile-pic"><?php if ($profile_img): ?><img src="<?php echo $profile_img; ?>" style="width: 100%; height: 100%; object-fit: cover;"><?php else: ?><?php echo $user_initials; ?><?php endif; ?></div>
            </div>
        </header>

        <div class="content-container">
            
            <div class="wallet-card">
                <div class="wallet-title">Total Lifetime Earnings</div>
                <div class="wallet-amount"><i class="fa-solid fa-indian-rupee-sign" style="font-size: 2.5rem; margin-right: 10px; opacity: 0.9;"></i> <?php echo number_format($total_earned, 2); ?></div>
                <button class="btn-withdraw" onclick="alert('Withdrawal request system coming soon!')">Withdraw to Bank</button>
            </div>
            
            <div class="history-card">
                <h3>Delivery History</h3>
                
                <?php if(count($history) > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Delivery Location</th>
                            <th>Date Completed</th>
                            <th>Commission Earned</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($history as $item): ?>
                        <tr>
                            <td class="td-order">#<?php echo $item['order_id']; ?></td>
                            <td><span class="td-loc" title="<?php echo htmlspecialchars($item['dropoff_address']); ?>"><i class="fa-solid fa-location-dot" style="color:#f97316; margin-right:6px;"></i> <?php echo htmlspecialchars($item['dropoff_address']); ?></span></td>
                            <td class="td-date"><?php echo date('M d, Y - h:i A', strtotime($item['created_at'])); ?></td>
                            <td class="td-amount">+ ₹<?php echo number_format($item['amount'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fa-solid fa-receipt" style="font-size: 3rem; color: #ddd; margin-bottom: 15px;"></i>
                    <p>You haven't completed any deliveries yet. Start accepting orders to build your history!</p>
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
