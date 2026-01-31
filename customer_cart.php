<?php
session_start();

// Prevent caching to ensure logout works effectively
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Redirect to login if not logged in as Customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Customer') {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';
// Include helpers via role_check or directly if needed. role_check.php includes helpers.php.
// If role_check.php is not included, we should include it or helpers.
include_once 'role_check.php'; // Ensure helpers are available via this

$formatted_name = formatName($_SESSION['name']);
$user_name = htmlspecialchars($formatted_name);
$user_initials = getAvatarInitials($formatted_name);
$user_profile_image = getProfileImage($_SESSION['user_id'], $conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Cart - Homely Bites</title>
    <!-- Fonts -->
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Lemon&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Shared CSS -->
    <style>
        /* SWIGGY-STYLE DESIGN SYSTEM */
        :root {
            --primary-color: #fc8019;
            --brand-green: #0a8f08;
            --bg-body: #f8f8f8;
            --text-dark: #222;
            --text-muted: #666;
            --card-bg: #FFFFFF;
            --header-height: 80px;
            --shadow-card: 0 4px 14px rgba(0,0,0,0.08);
            --shadow-hover: 0 8px 20px rgba(0,0,0,0.12);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: var(--bg-body); color: var(--text-dark); display: flex; min-height: 100vh; }

        .main-content { flex: 1; display: flex; flex-direction: column; width: 0; }
        
        /* Header */
        header {
            height: var(--header-height);
            background-color: var(--card-bg);
            padding: 0 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 900;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
        }

        .search-container {
            display: flex; align-items: center; background: #f1f1f1; border-radius: 12px; padding: 12px 20px; width: 400px; transition: 0.3s;
        }
        .search-container i { color: #888; margin-right: 12px; }
        .search-container input { border: none; background: transparent; outline: none; width: 100%; font-size: 0.95rem; font-weight: 500; color: var(--text-dark); }

        .user-info { display: flex; align-items: center; gap: 15px; text-align: right; }
        .profile-pic { width: 40px; height: 40px; background: #eee; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.9rem; color: #555; overflow: hidden; object-fit: cover; }
        .profile-pic img { width: 100%; height: 100%; object-fit: cover; }

        .content-container { padding: 40px 60px; max-width: 1400px; margin: 0 auto; width: 100%; }
        
        /* Page Specific */
        .page-header h2 { font-size: 28px; font-weight: 700; margin-bottom: 20px; color: #222; }
        
        .empty-state { text-align: center; padding: 60px 20px; width: 100%; background: #fff; border-radius: 16px; box-shadow: var(--shadow-card); }
        .empty-state i { font-size: 4rem; color: #eee; margin-bottom: 20px; }
        .empty-state p { color: var(--text-muted); font-size: 1.1rem; margin-bottom: 20px; }
        .btn-browse { color: var(--brand-green); font-weight: 600; text-decoration: none; border: 1px solid var(--brand-green); padding: 10px 20px; border-radius: 8px; transition: 0.2s; }
        .btn-browse:hover { background: var(--brand-green); color: white; }

        @media (max-width: 768px) {
            header { padding: 0 20px; }
            .content-container { padding: 20px; }
        }
    </style>
</head>
<body>

    <?php include 'customer_sidebar.php'; ?>

    <div class="main-content">
        <header>
            <div class="search-container" style="visibility: hidden;"></div> <!-- Spacer -->
            <div class="user-info">
                <div>
                    <p style="font-weight: 700; font-size: 0.95rem; margin-bottom: 2px; color: var(--text-dark);"><?php echo $user_name; ?></p>
                    <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 500; letter-spacing: 0.5px; text-transform: uppercase;">Customer</span>
                </div>
                <div class="profile-pic">
                    <?php if($user_profile_image): ?>
                        <img src="<?php echo $user_profile_image; ?>" alt="Profile">
                    <?php else: ?>
                        <?php echo $user_initials; ?>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <div class="content-container">
            <div class="page-header">
                <h2>My Cart</h2>
            </div>

            <?php
            // Fetch cart items
            $cart_sql = "SELECT c.id as cart_id, c.quantity, f.name, f.price, f.image, f.seller_id, u.name as seller_name 
                         FROM cart c 
                         JOIN foods f ON c.food_id = f.id 
                         JOIN users u ON f.seller_id = u.user_id 
                         WHERE c.user_id = ?";
            $stmt = $conn->prepare($cart_sql);
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $total_price = 0;
            ?>

            <?php if ($result->num_rows > 0): ?>
                <div style="background: white; border-radius: 16px; box-shadow: var(--shadow-card); overflow: hidden;">
                    <?php while ($row = $result->fetch_assoc()): 
                        $item_total = $row['price'] * $row['quantity'];
                        $total_price += $item_total;
                        $f_image = !empty($row['image']) && file_exists($row['image']) ? $row['image'] : 'assets/images/image-coming-soon.png';
                        // Fix seller name fallback if valid column missing or empty
                        $seller_name = !empty($row['seller_name']) ? htmlspecialchars($row['seller_name']) : 'Homely Chef'; 
                    ?>
                    <div style="display: flex; padding: 20px; border-bottom: 1px solid #f0f0f0; align-items: center;">
                        <img src="<?php echo $f_image; ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" style="width: 80px; height: 80px; border-radius: 8px; object-fit: cover; margin-right: 20px;">
                        <div style="flex: 1;">
                            <h4 style="font-size: 1.1rem; margin-bottom: 4px;"><?php echo htmlspecialchars($row['name']); ?></h4>
                            <p style="font-size: 0.9rem; color: #888; margin-bottom: 4px;">By <?php echo $seller_name; ?></p>
                            <div style="font-weight: 600; color: var(--text-dark); margin-bottom: 5px;">₹<?php echo $row['price']; ?></div>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <form action="handle_cart.php" method="POST" style="margin:0;">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="cart_id" value="<?php echo $row['cart_id']; ?>">
                                    <input type="hidden" name="change" value="-1">
                                    <button type="submit" style="width: 28px; height: 28px; border: 1px solid #ddd; background: #fff; border-radius: 4px; cursor: pointer; color: #555;">-</button>
                                </form>
                                <span style="font-weight: 600; font-size: 0.95rem; min-width: 20px; text-align: center;"><?php echo $row['quantity']; ?></span>
                                <form action="handle_cart.php" method="POST" style="margin:0;">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="cart_id" value="<?php echo $row['cart_id']; ?>">
                                    <input type="hidden" name="change" value="1">
                                    <button type="submit" style="width: 28px; height: 28px; border: 1px solid var(--brand-green); color: var(--brand-green); background: #f0fff0; border-radius: 4px; cursor: pointer;">+</button>
                                </form>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 1.2rem; font-weight: 700; color: var(--brand-green); margin-bottom: 10px;">₹<?php echo $item_total; ?></div>
                            <form action="handle_cart.php" method="POST" style="margin-top: 5px;">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="cart_id" value="<?php echo $row['cart_id']; ?>">
                                <button type="submit" style="background: none; border: none; color: #e74c3c; font-size: 0.85rem; cursor: pointer; padding: 5px 0; font-weight: 500;"><i class="fa-solid fa-trash-can"></i> Remove</button>
                            </form>
                        </div>
                    </div>
                    <?php endwhile; ?>
                    
                    <div style="padding: 20px; background: #fdfdfd; text-align: right;">
                        <p style="font-size: 1.1rem; margin-bottom: 10px;">Total: <span style="font-weight: 700; font-size: 1.5rem;">₹<?php echo $total_price; ?></span></p>
                        <a href="customer_checkout.php" class="btn-browse" style="background: var(--brand-green); color: white; border: none; font-size: 1rem; padding: 12px 30px; cursor: pointer; display: inline-block;">Proceed to Checkout</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fa-solid fa-cart-shopping"></i>
                    <p>Your cart is empty.</p>
                    <a href="customer_dashboard.php" class="btn-browse">Browse Food</a>
                </div>
            <?php endif; ?>
            <?php $stmt->close(); ?>

        </div>
    </div>
    <script>function toggleSidebar(){ document.querySelector('.sidebar').classList.toggle('collapsed'); }</script>
</body>
</html>

