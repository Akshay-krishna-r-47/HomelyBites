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
        :root { --primary-color: #fc8019; --text-dark: #222; --bg-body: #f8f8f8; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: var(--bg-body); color: var(--text-dark); display: flex; min-height: 100vh; }
        .main-content { flex: 1; padding: 40px; }
        
        .details-container { max-width: 900px; margin: 0 auto; background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.05); display: flex; flex-direction: column; }
        .food-hero { height: 350px; width: 100%; object-fit: cover; }
        .info-section { padding: 40px; }
        
        .f-title { font-size: 2.5rem; font-weight: 700; margin-bottom: 10px; color: #333; }
        .f-seller { color: #666; font-size: 1rem; margin-bottom: 20px; }
        .f-price { font-size: 2rem; color: #0a8f08; font-weight: 600; margin-bottom: 20px; }
        
        .btn-add-cart {
            background-color: var(--primary-color); color: white; border: none; padding: 15px 40px; font-size: 1.1rem; font-weight: 600; border-radius: 8px; cursor: pointer; transition: 0.3s;
        }
        .btn-add-cart:hover { background-color: #e67312; }
    </style>
</head>
<body>
    <?php include 'customer_sidebar.php'; ?>
    <div class="main-content">
        <a href="customer_dashboard.php" style="display:inline-block; margin-bottom: 20px; color: #666; text-decoration: none;"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
        
        <div class="details-container">
            <img src="<?php echo $f_image; ?>" alt="<?php echo $f_name; ?>" class="food-hero">
            <div class="info-section">
                <h1 class="f-title"><?php echo $f_name; ?></h1>
                <p class="f-seller">By <?php echo htmlspecialchars($food['seller_name']); ?></p>
                <div class="f-price">₹<?php echo $f_price; ?></div>
                <p style="margin-bottom: 30px; line-height: 1.6; color: #555;"><?php echo $f_desc; ?></p>
                
                <form action="handle_cart.php" method="POST">
                    <input type="hidden" name="food_id" value="<?php echo $food_id; ?>">
                    <button type="submit" name="action" value="add" class="btn-add-cart">ADD TO CART</button>
                </form>
            </div>
        </div>
    </div>
    <script>function toggleSidebar(){ document.querySelector('.sidebar').classList.toggle('collapsed'); }</script>
</body>
</html>
