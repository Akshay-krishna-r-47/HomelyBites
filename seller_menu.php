<?php
include 'role_check.php';
check_role_access('seller');
include 'db_connect.php';

$seller_id = $_SESSION['user_id'];
$message = "";
$message_type = "";

// Handle Form Submission (Add/Edit)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        $name = trim($_POST['name']);
        $price = floatval($_POST['price']);
        $category = trim($_POST['category']);
        $status = $_POST['status'];
        
        if ($_POST['action'] == 'add') {
             // Handle Image Upload logic here if needed. For now, if no file is uploaded, set to NULL.
             // We force status = 'Available' as per DB schema
             $image = null; // Default to NULL if no image uploaded
             $status = 'Available'; 

             $stmt = $conn->prepare("INSERT INTO foods (seller_id, name, price, category, image, status) VALUES (?, ?, ?, ?, ?, ?)");
             $stmt->bind_param("isdsss", $seller_id, $name, $price, $category, $image, $status);
             if ($stmt->execute()) {
                 $message = "Item added successfully."; $message_type = "success";
             } else {
                 $message = "Error adding item."; $message_type = "error";
             }
             $stmt->close();
        } elseif ($_POST['action'] == 'edit') {
            $food_id = intval($_POST['food_id']);
             $stmt = $conn->prepare("UPDATE foods SET name=?, price=?, category=?, status=? WHERE id=? AND seller_id=?");
             $stmt->bind_param("sdssii", $name, $price, $category, $status, $food_id, $seller_id);
             if ($stmt->execute()) {
                 $message = "Item updated successfully."; $message_type = "success";
             } else {
                 $message = "Error updating item."; $message_type = "error";
             }
             $stmt->close();
        } elseif ($_POST['action'] == 'delete') {
            $food_id = intval($_POST['food_id_delete']);
            $stmt = $conn->prepare("DELETE FROM foods WHERE id=? AND seller_id=?");
            $stmt->bind_param("ii", $food_id, $seller_id);
            if ($stmt->execute()) {
                 $message = "Item deleted successfully."; $message_type = "success";
            } else {
                 $message = "Error deleting item."; $message_type = "error";
            }
            $stmt->close();
        }
    }
}

// Fetch Foods
$foods = [];
$stmt = $conn->prepare("SELECT * FROM foods WHERE seller_id = ? ORDER BY created_at DESC");
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
    <title>Manage Menu - Homely Bites    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root {
            --brand-green: #0a8f08;
            --bg-body: #f8f8f8;
            --text-dark: #222;
            --text-muted: #666;
            --card-bg: #FFFFFF;
            --shadow-card: 0 4px 14px rgba(0,0,0,0.08);
            --shadow-hover: 0 8px 20px rgba(0,0,0,0.12);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        
        body { background-color: var(--bg-body); color: var(--text-dark); display: flex; min-height: 100vh; }
        
        .main-content { flex: 1; padding: 40px; overflow-y: auto; }
        
        h2 { font-size: 28px; font-weight: 700; margin-bottom: 30px; color: #222; }

        /* Form Section */
        .form-section { background: white; padding: 30px; border-radius: 16px; box-shadow: var(--shadow-card); margin-bottom: 40px; display: none; }
        .form-section.active { display: block; animation: slideDown 0.3s ease-out; }
        @keyframes slideDown { from { transform: translateY(-20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; color: #444; }
        .form-group input, .form-group select { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; transition: 0.2s; }
        .form-group input:focus, .form-group select:focus { border-color: var(--brand-green); outline: none; box-shadow: 0 0 0 3px rgba(10, 143, 8, 0.1); }
        
        /* Grid */
        .food-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 24px; padding-bottom: 40px; }
        
        /* Card */
        .food-card { background: white; border-radius: 16px; overflow: hidden; box-shadow: var(--shadow-card); transition: transform 0.2s ease; border: 1px solid rgba(0,0,0,0.03); }
        .food-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-hover); }

        .food-img { width: 100%; height: 170px; object-fit: cover; background: #f0f0f0; display: block; }
        
        .food-details { padding: 16px; }
        .food-name { font-size: 16px; font-weight: 600; margin-bottom: 4px; color: #333; }
        .food-meta { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; font-size: 13px; color: #888; }
        
        .food-price { font-size: 15px; font-weight: 600; color: var(--brand-green); }

        .badge { padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        .badge-available { background: #e8f5e9; color: #2e7d32; }
        .badge-unavailable { background: #ffebee; color: #c62828; }
        
        .card-actions { border-top: 1px solid #f5f5f5; padding: 12px 16px; display: flex; gap: 10px; justify-content: flex-end; background: #fafafa; }
        .btn-action { padding: 8px 12px; border-radius: 6px; border: 1px solid #ddd; background: white; cursor: pointer; color: #555; transition: all 0.2s; }
        .btn-action:hover { background: #f0f0f0; color: #222; }
        .btn-delete:hover { background: #ffebee; color: #c62828; border-color: #ffcdd2; }

        .btn-add { background: var(--brand-green); color: white; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; box-shadow: 0 4px 10px rgba(10, 143, 8, 0.3); } 

        
        /* Modal Styles Placeholder (Simple form display for now) */
        .form-section { background: #fff; padding: 30px; border-radius: 16px; margin-bottom: 30px; display: none; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 700; }
        
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error { background: #f8d7da; color: #721c24; }
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
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
            <?php endif; ?>

            <div class="page-header">
                <h2>Manage Menu</h2>
                <button class="btn-add" onclick="toggleForm('add')"><i class="fa-solid fa-plus"></i> Add New Item</button>
            </div>
            
            <!-- Add Item Form -->
            <div id="addForm" class="form-section">
                <h3>Add New Food Item</h3>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add">
                    <div class="form-group">
                        <label>Item Name</label>
                        <input type="text" name="name" required>
                    </div>
                    <div class="form-group">
                        <label>Price (₹)</label>
                        <input type="number" step="0.01" name="price" required>
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category" required>
                            <option value="Main Course">Main Course</option>
                            <option value="Snacks">Snacks</option>
                            <option value="Dessert">Dessert</option>
                            <option value="Beverage">Beverage</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="Available">Available</option>
                            <option value="Unavailable">Unavailable</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-add" style="width: auto;">Save Item</button>
                    <button type="button" class="btn-action" onclick="toggleForm('add')">Cancel</button>
                </form>
            </div>

            <div class="food-grid">
                <?php foreach($foods as $food): ?>
                <div class="food-card">
                    <img src="<?php echo htmlspecialchars((!empty($food['image']) && file_exists($food['image'])) ? $food['image'] : 'assets/images/image-coming-soon.png'); ?>" alt="<?php echo htmlspecialchars($food['name']); ?>" class="food-img">
                    <div class="food-details">
                        <div class="food-name"><?php echo htmlspecialchars($food['name']); ?></div>
                        <div class="food-meta">
                            <span><?php echo htmlspecialchars($food['category']); ?></span>
                            <span class="badge badge-<?php echo strtolower($food['status']); ?>"><?php echo htmlspecialchars($food['status']); ?></span>
                        </div>
                        <div class="food-price">₹<?php echo number_format($food['price'], 2); ?></div>
                    </div>
                    <div class="card-actions">
                        <form method="POST" onsubmit="return confirm('Delete this item?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="food_id_delete" value="<?php echo $food['id']; ?>">
                            <button type="submit" class="btn-action btn-delete"><i class="fa-solid fa-trash"></i></button>
                        </form>
                        <button class="btn-action" onclick="alert('Edit functionality would open a modal pre-filled with this data.');"><i class="fa-solid fa-pen"></i></button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php if (count($foods) === 0): ?>
                <div style="text-align: center; padding: 40px; color: #999;">No items in your menu yet. Add one to get started!</div>
            <?php endif; ?>
        </div>
    </div>
    <script>
        function toggleSidebar(){ document.querySelector('.sidebar').classList.toggle('collapsed'); }
        function toggleForm(id) {
            const form = document.getElementById(id + 'Form');
            form.style.display = form.style.display === 'none' || form.style.display === '' ? 'block' : 'none';
        }
    </script>
</body>
</html>
