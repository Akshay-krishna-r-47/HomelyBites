<?php
include 'role_check.php';
check_role_access('admin');
include 'db_connect.php';

if (!isset($_GET['seller_id'])) {
    header("Location: admin_users.php");
    exit();
}

$seller_id = intval($_GET['seller_id']);

// Fetch Seller Name
$seller_name = "Unknown Seller";
$s_stmt = $conn->prepare("SELECT name FROM users WHERE user_id = ?");
$s_stmt->bind_param("i", $seller_id);
$s_stmt->execute();
$s_res = $s_stmt->get_result();
if ($row = $s_res->fetch_assoc()) $seller_name = $row['name'];
$s_stmt->close();

// Fetch Foods (Including Deleted)
$foods = [];
$stmt = $conn->prepare("SELECT * FROM foods WHERE seller_id = ? ORDER BY is_deleted ASC, created_at DESC");
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $foods[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Menu - Homely Bites</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary-color: #27ae60; --bg-body: #fdfbf7; --card-bg: #FFFFFF; --text-dark: #2c3e50; --danger: #e74c3c; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: var(--bg-body); color: var(--text-dark); display: flex; min-height: 100vh; }
        .main-content { flex: 1; padding: 40px; overflow-y: auto; }
        
        .header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        h2 { font-size: 28px; font-weight: 700; }
        .back-link { color: #555; text-decoration: none; font-weight: 500; display: flex; align-items: center; gap: 8px; }
        .back-link:hover { color: var(--primary-color); }

        .food-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 24px; }
        
        .food-card { background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 14px rgba(0,0,0,0.05); border: 1px solid rgba(0,0,0,0.03); transition: 0.2s; position: relative; }
        .food-card.deleted { opacity: 0.7; border: 1px solid #ffcccc; background: #fffcfc; }
        
        .deleted-overlay { position: absolute; top: 10px; right: 10px; background: var(--danger); color: white; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; z-index: 10; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
        
        .food-img { width: 100%; height: 170px; object-fit: cover; background: #eee; }
        .food-img.grayscale { filter: grayscale(100%); }
        
        .food-details { padding: 16px; }
        .food-name { font-size: 16px; font-weight: 600; margin-bottom: 4px; color: #333; }
        .food-category { font-size: 13px; color: #888; margin-bottom: 12px; }
        .food-price { font-size: 15px; font-weight: 600; color: var(--primary-color); display: flex; justify-content: space-between; align-items: center; }
        
        .badge { padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        .badge-available { background: #e8f5e9; color: #2e7d32; }
        .badge-unavailable { background: #ffebee; color: #c62828; }
        .badge-deleted { background: #333; color: #fff; }

        .admin-profile { display: flex; align-items: center; gap: 10px; }
        .profile-pic { width: 40px; height: 40px; background: #eee; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
    </style>
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>
    <div class="main-content">
        <header style="display: flex; justify-content: flex-end; align-items: center; margin-bottom: 30px;">
             <div class="admin-profile">
                <div style="text-align: right;">
                    <p style="font-weight: 700; margin-bottom: 2px;">Admin</p>
                    <span style="font-size: 0.8rem; color: #888;">Panel</span>
                </div>
                <div class="profile-pic"><i class="fa-solid fa-user-shield"></i></div>
            </div>
        </header>

        <div class="header-row">
            <div>
                <a href="admin_users.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back to Sellers</a>
                <h2 style="margin-top: 10px;">Menu: <?php echo htmlspecialchars($seller_name); ?></h2>
            </div>
        </div>

        <div class="food-grid">
            <?php foreach($foods as $food): 
                $is_deleted = !empty($food['is_deleted']);
            ?>
            <div class="food-card <?php echo $is_deleted ? 'deleted' : ''; ?>">
                <?php if($is_deleted): ?>
                    <div class="deleted-overlay"><i class="fa-solid fa-trash"></i> DELETED</div>
                <?php endif; ?>
                
                <img src="<?php echo htmlspecialchars((!empty($food['image']) && file_exists($food['image'])) ? $food['image'] : 'assets/images/image-coming-soon.png'); ?>" class="food-img <?php echo $is_deleted ? 'grayscale' : ''; ?>">
                
                <div class="food-details">
                    <div class="food-name">
                        <?php echo htmlspecialchars($food['name']); ?>
                        <?php if($is_deleted): ?><span style="font-size: 0.8em; color: #e74c3c;">(Removed)</span><?php endif; ?>
                    </div>
                    <div class="food-category">
                        <?php echo htmlspecialchars($food['category']); ?>
                    </div>
                    <div class="food-price">
                        <span>₹<?php echo number_format($food['price'], 2); ?></span>
                        <?php if(!$is_deleted): ?>
                            <span class="badge badge-<?php echo strtolower($food['status']); ?>"><?php echo htmlspecialchars($food['status']); ?></span>
                        <?php else: ?>
                            <span class="badge badge-deleted">Deleted</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php if(empty($foods)): ?>
            <p style="text-align: center; margin-top: 50px; color: #888;">No items found for this seller.</p>
        <?php endif; ?>
    </div>
    <script>function toggleSidebar(){document.querySelector('.sidebar').classList.toggle('collapsed');}</script>
</body>
</html>
