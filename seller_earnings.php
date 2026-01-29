<?php
include 'role_check.php';
check_role_access('seller');
include 'db_connect.php';

$seller_id = $_SESSION['user_id'];

// Calculate Earnings
// Logic: Sum of (quantity * price) for completed orders where food belongs to seller
// Note: 'order_items' should generally have 'price' at time of purchase. Assuming it does.
// If not, we join 'foods.price', but that might change over time.
// Assuming 'order_items' has 'price' column based on typical e-commerce schemas. If not, I'll use foods.price.
// Safest bet for "Completed" status.

$total_earnings = 0;
$today_earnings = 0;
$monthly_earnings = 0;

$sql_earnings = "
    SELECT 
        SUM(oi.quantity * f.price) as amount, /* Using current food price if item price not stored, slightly risky but standard fallback */
        DATE(o.created_at) as order_date
    FROM orders o
    JOIN order_items oi ON o.order_id = oi.order_id
    JOIN foods f ON oi.food_id = f.id
    WHERE f.seller_id = ? AND o.status IN ('Completed', 'Delivered')
    GROUP BY DATE(o.created_at)
";
// Note: Ideally order_items has 'price' fixed. I'll assume f.price for now to ensure query works with our known schema.

$result = $conn->execute_query($sql_earnings, [$seller_id]);
$daily_data = [];

while ($row = $result->fetch_assoc()) {
    $amount = floatval($row['amount']);
    $date = $row['order_date'];
    
    $total_earnings += $amount;
    
    if ($date == date('Y-m-d')) {
        $today_earnings += $amount;
    }
    
    if (date('Y-m', strtotime($date)) == date('Y-m')) {
        $monthly_earnings += $amount;
    }
    
    $daily_data[] = $row;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Earnings - Homely Bites</title>
    <link href="https://fonts.googleapis.com/css2?family=Lemon&family=Lato:wght@300;400;700&family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary-color: #27ae60; --brand-green: #008000; --bg-body: #fdfbf7; --card-bg: #FFFFFF; --text-dark: #2c3e50; --text-muted: #7f8c8d; --header-height: 80px; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Lato', sans-serif; }
        body { background-color: var(--bg-body); color: var(--text-dark); display: flex; min-height: 100vh; }
        .main-content { flex: 1; display: flex; flex-direction: column; width: 0; transition: all 0.4s; }
        header { height: var(--header-height); background-color: var(--card-bg); padding: 0 40px; display: flex; align-items: center; justify-content: flex-end; position: sticky; top: 0; z-index: 900; border-bottom: 1px solid rgba(0,0,0,0.06); }
        .content-container { padding: 40px 50px; width: 100%; max-width: 1600px; margin: 0 auto; }
        
        .page-header { margin-bottom: 30px; }
        .page-header h2 { font-family: 'Playfair Display', serif; font-size: 2.2rem; }

        .earnings-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px; margin-bottom: 40px; }
        .stat-card { background: var(--card-bg); padding: 30px; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); text-align: center; border: 1px solid rgba(0,0,0,0.05); }
        .stat-card h3 { font-size: 2.5rem; color: var(--brand-green); margin-bottom: 5px; font-family: 'Playfair Display', serif; }
        .stat-card p { color: var(--text-muted); font-weight: 600; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.5px; }

        .history-section { background: var(--card-bg); padding: 30px; border-radius: 16px; border: 1px solid rgba(0,0,0,0.05); }
        .history-section h3 { font-family: 'Playfair Display', serif; font-size: 1.5rem; margin-bottom: 20px; }
        
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; border-bottom: 2px solid #f0f0f0; color: #888; text-transform: uppercase; font-size: 0.8rem; }
        td { padding: 15px; border-bottom: 1px solid #f5f5f5; font-size: 0.95rem; }
    </style>
</head>
<body>
    <?php include 'seller_sidebar.php'; ?>
    <div class="main-content">
        <header>
            <div style="text-align: right; margin-right: 15px;">
                <p style="font-weight: 700; margin-bottom: 2px;"><?php echo htmlspecialchars($_SESSION['name']); ?></p>
                <span style="font-size: 0.8rem; color: #888;">Seller Panel</span>
            </div>
            <div style="width: 40px; height: 40px; background: #ddd; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                <i class="fa-solid fa-store"></i>
            </div>
        </header>

        <div class="content-container">
            <div class="page-header">
                <h2>My Earnings</h2>
            </div>
            
            <div class="earnings-grid">
                <div class="stat-card">
                    <h3>₹<?php echo number_format($today_earnings, 2); ?></h3>
                    <p>Today's Earnings</p>
                </div>
                <div class="stat-card">
                    <h3>₹<?php echo number_format($monthly_earnings, 2); ?></h3>
                    <p>This Month</p>
                </div>
                <div class="stat-card">
                    <h3>₹<?php echo number_format($total_earnings, 2); ?></h3>
                    <p>Total Revenue</p>
                </div>
            </div>
            
            <div class="history-section">
                <h3>Earnings History</h3>
                <?php if (count($daily_data) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($daily_data as $data): ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($data['order_date'])); ?></td>
                            <td><span style="color: green; font-weight: 600;">Settled</span></td>
                            <td><strong>₹<?php echo number_format($data['amount'], 2); ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <p style="color: #999; text-align: center; padding: 20px;">No earnings history available.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script>function toggleSidebar(){document.querySelector('.sidebar').classList.toggle('collapsed');}</script>
</body>
</html>
