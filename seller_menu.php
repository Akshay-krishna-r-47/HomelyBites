<?php
include 'role_check.php';
check_role_access('seller');
include 'db_connect.php';

$seller_id = $_SESSION['user_id'];
$message = "";
$message_type = "";

// Helper to handle file upload
function handleImageUpload($file) {
    if (!isset($file) || $file['error'] != 0) return null;
    $target_dir = "assets/images/foods/";
    if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
    
    $ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($ext, $allowed)) return null;
    
    $new_name = uniqid("food_") . "." . $ext;
    $target_file = $target_dir . $new_name;
    
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return $target_file;
    }
    return null;
}

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add') {
             $name = trim($_POST['name']);
             $price = floatval($_POST['price']);
             $category = trim($_POST['category']);
             $status = $_POST['status'];
             $stock = isset($_POST['stock']) ? intval($_POST['stock']) : 0;
             
             // Validation
             if ($price < 0) {
                 $message = "Price cannot be negative.";
                 $message_type = "error";
             } elseif ($stock < 0) {
                 $message = "Stock quantity cannot be negative.";
                 $message_type = "error";
             } elseif (empty($name) || empty($category)) {
                 $message = "Name and Category are required.";
                 $message_type = "error";
             } elseif (is_numeric($name)) {
                 $message = "Item name cannot be purely numeric (e.g. '111111'). Please enter a valid name.";
                 $message_type = "error";
             } else {
                 $image_path = handleImageUpload($_FILES['image']);
                 
                 $avail_slot1_start = !empty($_POST['avail_slot1_start']) ? $_POST['avail_slot1_start'] : null;
                 $avail_slot1_end = !empty($_POST['avail_slot1_end']) ? $_POST['avail_slot1_end'] : null;
                 $avail_slot2_start = !empty($_POST['avail_slot2_start']) ? $_POST['avail_slot2_start'] : null;
                 $avail_slot2_end = !empty($_POST['avail_slot2_end']) ? $_POST['avail_slot2_end'] : null;
                 
                 $stmt = $conn->prepare("INSERT INTO foods (seller_id, name, price, category, image, status, stock, avail_slot1_start, avail_slot1_end, avail_slot2_start, avail_slot2_end) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                 $stmt->bind_param("isdsssissss", $seller_id, $name, $price, $category, $image_path, $status, $stock, $avail_slot1_start, $avail_slot1_end, $avail_slot2_start, $avail_slot2_end);
                 
                 if ($stmt->execute()) {
                     $message = "Item added successfully."; $message_type = "success";
                 } else {
                     $message = "Error adding item."; $message_type = "error";
                 }
                 $stmt->close();
             }

        } elseif ($_POST['action'] == 'edit') {
            $food_id = intval($_POST['food_id']);
            $name = trim($_POST['name']);
            $price = floatval($_POST['price']);
            $category = trim($_POST['category']);
            $status = $_POST['status'];
            $stock = isset($_POST['stock']) ? intval($_POST['stock']) : 0;
            
            if ($price < 0) {
                $message = "Price cannot be negative.";
                $message_type = "error";
            } elseif ($stock < 0) {
                 $message = "Stock quantity cannot be negative.";
                 $message_type = "error";
            } elseif (empty($name) || empty($category)) {
                 $message = "Name and Category are required.";
                 $message_type = "error";
            } elseif (is_numeric($name)) {
                $message = "Item name cannot be purely numeric. Please enter a valid name.";
                $message_type = "error";
            } else {
                // Check if new image uploaded
                $new_image = handleImageUpload($_FILES['image']);
                
                $avail_slot1_start = !empty($_POST['avail_slot1_start']) ? $_POST['avail_slot1_start'] : null;
                $avail_slot1_end = !empty($_POST['avail_slot1_end']) ? $_POST['avail_slot1_end'] : null;
                $avail_slot2_start = !empty($_POST['avail_slot2_start']) ? $_POST['avail_slot2_start'] : null;
                $avail_slot2_end = !empty($_POST['avail_slot2_end']) ? $_POST['avail_slot2_end'] : null;
                
                if ($new_image) {
                    $sql = "UPDATE foods SET name=?, price=?, category=?, status=?, image=?, stock=?, avail_slot1_start=?, avail_slot1_end=?, avail_slot2_start=?, avail_slot2_end=? WHERE id=? AND seller_id=?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("sdsssissssii", $name, $price, $category, $status, $new_image, $stock, $avail_slot1_start, $avail_slot1_end, $avail_slot2_start, $avail_slot2_end, $food_id, $seller_id);
                } else {
                    $sql = "UPDATE foods SET name=?, price=?, category=?, status=?, stock=?, avail_slot1_start=?, avail_slot1_end=?, avail_slot2_start=?, avail_slot2_end=? WHERE id=? AND seller_id=?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("sdssissssii", $name, $price, $category, $status, $stock, $avail_slot1_start, $avail_slot1_end, $avail_slot2_start, $avail_slot2_end, $food_id, $seller_id);
                }

                if ($stmt->execute()) {
                     $message = "Item updated successfully."; $message_type = "success";
                } else {
                     $message = "Error updating item."; $message_type = "error";
                }
                $stmt->close();
            }



        } elseif ($_POST['action'] == 'delete') {
            $food_id = intval($_POST['food_id_delete']);
            // Soft Delete Implementation
            $stmt = $conn->prepare("UPDATE foods SET is_deleted = 1 WHERE id=? AND seller_id=?");
            $stmt->bind_param("ii", $food_id, $seller_id);
            if ($stmt->execute()) {
                 $message = "Item removed successfully."; $message_type = "success";
            } else {
                 $message = "Error removing item."; $message_type = "error";
            }
            $stmt->close();
        }
    }
}

// Fetch Foods (Active Only)
$foods = [];
$stmt = $conn->prepare("SELECT * FROM foods WHERE seller_id = ? AND is_deleted = 0 ORDER BY created_at DESC");
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
    <title>Manage Menu - Homely Bites</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --brand-green: #27ae60; --bg-body: #f8f8f8; --card-bg: #FFFFFF; --shadow-card: 0 4px 14px rgba(0,0,0,0.08); }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: var(--bg-body); color: #222; display: flex; min-height: 100vh; }
        .main-content { flex: 1; padding: 40px; overflow-y: auto; }
        
        h2 { font-size: 28px; font-weight: 700; margin-bottom: 30px; color: #222; }

        /* Modal Overlay */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); align-items: center; justify-content: center; backdrop-filter: blur(4px); padding: 20px; overflow-y: auto; }
        .modal.active { display: flex; align-items: flex-start; }
        .modal-content { background-color: #fefefe; padding: 30px; border-radius: 16px; width: 500px; max-width: 90%; margin: 40px auto; animation: slideIn 0.3s; position: relative; }
        @keyframes slideIn { from{transform:translateY(-50px);opacity:0} to{transform:translateY(0);opacity:1} }
        
        .close-modal { position: absolute; top: 20px; right: 20px; font-size: 1.5rem; cursor: pointer; color: #888; z-index: 10; background: white; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .close-modal:hover { background: #f0f0f0; color: #333; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; color: #444; font-size: 0.9rem; }
        .form-group input, .form-group select { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; }
        .form-group input:focus, .form-group select:focus { border-color: var(--brand-green); outline: none; }
        .time-slots-container { background: #f9f9f9; padding: 15px; border-radius: 8px; border: 1px solid #eee; margin-bottom: 20px; }
        .time-slots-title { font-weight: 600; font-size: 0.9rem; margin-bottom: 10px; color: #333; }
        .time-group { display: flex; gap: 10px; margin-bottom: 10px; align-items: center; }
        .time-group input[type="time"] { padding: 8px; font-size: 0.9rem; }
        .time-group span { font-size: 0.9rem; color: #666; font-weight: 500; }
        
        .food-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 24px; padding-bottom: 40px; }
        
        .food-card { background: white; border-radius: 16px; overflow: hidden; box-shadow: var(--shadow-card); transition: transform 0.2s ease; border: 1px solid rgba(0,0,0,0.03); }
        .food-card:hover { transform: translateY(-4px); box-shadow: 0 8px 20px rgba(0,0,0,0.12); }

        .food-img { width: 100%; height: 170px; object-fit: cover; background: #eee; }
        
        .food-details { padding: 16px; }
        .food-name { font-size: 16px; font-weight: 600; margin-bottom: 4px; color: #333; }
        .food-meta { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; font-size: 13px; color: #888; }
        .food-price { font-size: 15px; font-weight: 600; color: var(--brand-green); }

        .badge { padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        .badge-Available { background: #e8f5e9; color: #2e7d32; }
        .badge-Unavailable { background: #ffebee; color: #c62828; }
        
        .card-actions { border-top: 1px solid #f5f5f5; padding: 12px 16px; display: flex; gap: 10px; justify-content: flex-end; background: #fafafa; }
        .btn-action { padding: 8px 12px; border-radius: 6px; border: 1px solid #ddd; background: white; cursor: pointer; color: #555; transition: all 0.2s; }
        .btn-action:hover { background: #f0f0f0; color: #222; }
        .btn-delete:hover { background: #ffebee; color: #c62828; border-color: #ffcdd2; }

        .btn-primary { background: var(--brand-green); color: white; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; box-shadow: 0 4px 10px rgba(39, 174, 96, 0.3); } 
        
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error { background: #f8d7da; color: #721c24; }
        .alert-error { background: #f8d7da; color: #721c24; }
        
        .btn-cancel { background: #f1f1f1; color: #333; border: 1px solid #ddd; padding: 12px 24px; border-radius: 8px; font-weight: 500; cursor: pointer; }
        .btn-cancel:hover { background: #e2e2e2; }
        .btn-danger { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; }
        .btn-danger:hover { background: #fee2e2; color: #b91c1c; border-color: #fca5a5; }
        
        .delete-confirm-content { text-align: center; padding: 20px; }
        .delete-icon { width: 60px; height: 60px; background: #fee2e2; color: #dc2626; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; margin: 0 auto 20px; }
    </style>
</head>
<body>
    <?php include 'seller_sidebar.php'; ?>
    <div class="main-content">
        <header style="display: flex; justify-content: flex-end; align-items: center; margin-bottom: 30px;">
             <div style="text-align: right; margin-right: 15px;">
                <p style="font-weight: 700; margin-bottom: 2px; color: #333;"><?php echo htmlspecialchars($_SESSION['name']); ?></p>
                <span style="font-size: 0.8rem; color: #888;">Seller Panel</span>
            </div>
            <div style="width: 40px; height: 40px; background: #eee; border-radius: 50%; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                <?php 
                $header_name = formatName($_SESSION['name']);
                $header_initials = getAvatarInitials($header_name);
                $header_img = getProfileImage($_SESSION['user_id'], $conn);
                if ($header_img): ?>
                    <img src="<?php echo $header_img; ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                <?php else: ?>
                    <span style="font-weight: 600; color: #555;"><?php echo $header_initials; ?></span>
                <?php endif; ?>
            </div>
        </header>

        <?php if ($message): ?> <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div> <?php endif; ?>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h2>Manage Menu</h2>
            <button class="btn-primary" onclick="openModal('add')"><i class="fa-solid fa-plus"></i> Add New Item</button>
        </div>
        
        <div class="food-grid">
            <?php foreach($foods as $food): ?>
            <div class="food-card">
                <img src="<?php echo htmlspecialchars((!empty($food['image']) && file_exists($food['image'])) ? $food['image'] : 'assets/images/image-coming-soon.png'); ?>" class="food-img">
                <div class="food-details">
                    <div class="food-name"><?php echo htmlspecialchars($food['name']); ?></div>
                    <div class="food-meta">
                        <span><?php echo htmlspecialchars($food['category']); ?></span>
                        <span class="badge badge-<?php echo $food['status']; ?>"><?php echo htmlspecialchars($food['status']); ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span class="food-price">₹<?php echo number_format($food['price'], 2); ?></span>
                        <span style="font-size: 0.85rem; color: <?php echo ($food['stock'] > 0) ? '#27ae60' : '#e74c3c'; ?>;">
                            <i class="fa-solid fa-box"></i> Stock: <?php echo $food['stock']; ?>
                        </span>
                    </div>
                    <?php if(!empty($food['avail_slot1_start']) || !empty($food['avail_slot2_start'])): ?>
                        <div style="margin-top: 10px; font-size: 0.8rem; color: #f39c12; background: #fff8e1; padding: 4px 8px; border-radius: 4px; display: inline-block;">
                            <i class="fa-regular fa-clock"></i> Time Restricted
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-actions">
                    <button class="btn-action" onclick='openEditModal(<?php echo json_encode($food); ?>)'><i class="fa-solid fa-pen"></i></button>
                    <button class="btn-action btn-delete" onclick="openDeleteModal(<?php echo $food['id']; ?>)"><i class="fa-solid fa-trash"></i></button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (empty($foods)): ?>
            <div style="text-align: center; color: #999; margin-top: 50px;">
                <i class="fa-solid fa-utensils" style="font-size: 3rem; margin-bottom: 20px; color: #eee;"></i>
                <p>No items in menu yet.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- ADD/EDIT MODAL -->
    <div id="foodModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal()">&times;</span>
            <h3 id="modalTitle" style="margin-bottom: 25px;">Add New Item</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="food_id" id="foodId">
                
                <div class="form-group">
                    <label>Item Name</label>
                    <input type="text" name="name" id="itemName" required>
                </div>
                <div class="form-group" style="display: flex; gap: 15px;">
                    <div style="flex: 1;">
                        <label>Price (₹)</label>
                        <input type="number" step="0.01" min="0" name="price" id="itemPrice" required>
                    </div>
                    <div style="flex: 1;">
                        <label>Stock Qty</label>
                        <input type="number" min="0" name="stock" id="itemStock" required value="0">
                    </div>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select name="category" id="itemCategory" required>
                        <option value="Breakfast">Breakfast</option>
                        <option value="Lunch">Lunch</option>
                        <option value="Dinner">Dinner</option>
                        <option value="Snacks">Snacks</option>
                        <option value="Dessert">Dessert</option>
                        <option value="Beverage">Beverage</option>
                        <option value="Main Course">Main Course</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="itemStatus">
                        <option value="Available">Available</option>
                        <option value="Unavailable">Unavailable</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Image</label>
                    <input type="file" name="image" accept="image/*">
                    <small style="color: #999">Leave empty to keep current image (for edits)</small>
                </div>
                
                <div class="time-slots-container">
                    <div class="time-slots-title"><i class="fa-regular fa-clock"></i> Availability Time Slots (Optional)</div>
                    <p style="font-size: 0.8rem; color: #666; margin-bottom: 15px;">Leave blank to make this item available to order 24/7.</p>
                    
                    <label style="font-size: 0.85rem; font-weight: 500; color: #555; display: block; margin-bottom: 5px;">Slot 1 (e.g. Morning)</label>
                    <div class="time-group">
                        <input type="time" name="avail_slot1_start" id="slot1Start">
                        <span>to</span>
                        <input type="time" name="avail_slot1_end" id="slot1End">
                    </div>

                    <label style="font-size: 0.85rem; font-weight: 500; color: #555; display: block; margin-bottom: 5px; margin-top: 15px;">Slot 2 (e.g. Evening)</label>
                    <div class="time-group">
                        <input type="time" name="avail_slot2_start" id="slot2Start">
                        <span>to</span>
                        <input type="time" name="avail_slot2_end" id="slot2End">
                    </div>
                </div>

                <button type="submit" class="btn-primary" style="width: 100%;">Save Item</button>
            </form>
        </div>
    </div>

    <!-- DELETE CONFIRMATION MODAL -->
    <div id="deleteModal" class="modal">
        <div class="modal-content" style="width: 400px;">
            <div class="delete-confirm-content">
                <div class="delete-icon"><i class="fa-solid fa-trash-alt"></i></div>
                <h3 style="margin-bottom: 10px; color: #111;">Delete Item?</h3>
                <p style="color: #666; margin-bottom: 25px; font-size: 14px;">Are you sure you want to delete this item? This action cannot be undone.</p>
                
                <form method="POST" style="display: flex; gap: 15px; justify-content: center;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="food_id_delete" id="deleteFoodId">
                    <button type="button" class="btn-cancel" onclick="closeDeleteModal()">Cancel</button>
                    <button type="submit" class="btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar(){ document.querySelector('.sidebar').classList.toggle('collapsed'); }
        
        const modal = document.getElementById('foodModal');
        const modalTitle = document.getElementById('modalTitle');
        const formAction = document.getElementById('formAction');
        const foodId = document.getElementById('foodId');
        
        function openModal(mode) {
            modal.classList.add('active');
            if(mode === 'add') {
                modalTitle.textContent = "Add New Item";
                formAction.value = "add";
                document.getElementById('itemName').value = "";
                document.getElementById('itemPrice').value = "";
                document.getElementById('itemStock').value = "0";
                document.getElementById('itemCategory').value = "Main Course";
                document.getElementById('itemStatus').value = "Available";
                
                // Clear times
                document.getElementById('slot1Start').value = "";
                document.getElementById('slot1End').value = "";
                document.getElementById('slot2Start').value = "";
                document.getElementById('slot2End').value = "";
            }
        }
        
        function openEditModal(food) {
            modal.classList.add('active');
            modalTitle.textContent = "Edit Item";
            formAction.value = "edit";
            foodId.value = food.id;
            
            document.getElementById('itemName').value = food.name;
            document.getElementById('itemPrice').value = food.price;
            document.getElementById('itemStock').value = food.stock;
            document.getElementById('itemCategory').value = food.category;
            document.getElementById('itemStatus').value = food.status;
            
            // Populate times
            document.getElementById('slot1Start').value = food.avail_slot1_start ? food.avail_slot1_start.substring(0,5) : "";
            document.getElementById('slot1End').value = food.avail_slot1_end ? food.avail_slot1_end.substring(0,5) : "";
            document.getElementById('slot2Start').value = food.avail_slot2_start ? food.avail_slot2_start.substring(0,5) : "";
            document.getElementById('slot2End').value = food.avail_slot2_end ? food.avail_slot2_end.substring(0,5) : "";
        }

        function closeModal() {
            modal.classList.remove('active');
        }
        
        window.onclick = function(e) { 
            if(e.target == modal) closeModal(); 
            if(e.target == deleteModal) closeDeleteModal();
        }

        // Delete Modal Logic
        const deleteModal = document.getElementById('deleteModal');
        const deleteFoodId = document.getElementById('deleteFoodId');
        
        function openDeleteModal(id) {
            deleteFoodId.value = id;
            deleteModal.classList.add('active');
        }
        
        function closeDeleteModal() {
            deleteModal.classList.remove('active');
        }
    </script>
</body>
</html>
