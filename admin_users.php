<?php
include 'role_check.php';
check_role_access('admin');
include 'db_connect.php';

$admin_name = htmlspecialchars($_SESSION['name']);

// Fetch All Users with Role Capabilities
$users = [];
$sql = "SELECT u.user_id, u.name, u.email, u.phone, u.role, u.status, u.created_at,
        (SELECT COUNT(*) FROM seller_applications sa WHERE sa.user_id = u.user_id AND sa.status = 'Approved') as is_seller,
        (SELECT COUNT(*) FROM delivery_applications da WHERE da.user_id = u.user_id AND da.status = 'Approved') as is_delivery
        FROM users u 
        ORDER BY u.user_id DESC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Sellers - Homely Bites</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary-color: #27ae60; --bg-body: #fdfbf7; --card-bg: #FFFFFF; --text-dark: #2c3e50; --border-radius: 12px; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: var(--bg-body); color: var(--text-dark); display: flex; min-height: 100vh; }
        .main-content { flex: 1; padding: 40px; overflow-y: auto; }
        
        h2 { font-size: 28px; font-weight: 700; margin-bottom: 30px; }
        
        .users-table { width: 100%; border-collapse: collapse; background: white; border-radius: var(--border-radius); overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .users-table th, .users-table td { padding: 15px 20px; text-align: left; }
        .users-table th { background-color: #f8f9fa; font-weight: 600; color: #555; }
        .users-table tr { border-bottom: 1px solid #eee; }
        .users-table tr:hover { background-color: #f9f9f9; }
        
        .btn-view { display: inline-block; padding: 8px 16px; background: var(--primary-color); color: white; border-radius: 6px; text-decoration: none; font-size: 0.9rem; transition: 0.3s; }
        .btn-view:hover { background: #219150; }
        
        .badge { padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: 600; }
        .badge-active { background: #d4edda; color: #155724; }
        .badge-pending { background: #fff3cd; color: #856404; }
        
        header { display: flex; justify-content: flex-end; align-items: center; margin-bottom: 30px; }
        .admin-profile { display: flex; align-items: center; gap: 10px; }
        .profile-pic { width: 40px; height: 40px; background: #eee; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
    </style>
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>
    <div class="main-content">
        <header>
            <div class="admin-profile">
                <div style="text-align: right;">
                    <p style="font-weight: 700; margin-bottom: 2px;"><?php echo $admin_name; ?></p>
                    <span style="font-size: 0.8rem; color: #888;">Admin Panel</span>
                </div>
                <div class="profile-pic"><i class="fa-solid fa-user-shield"></i></div>
            </div>
        </header>

        <h2>Manage Users</h2>
        
        <table class="users-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Role</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($users as $user): 
                    $roles = ['Customer']; // Everyone is a customer
                    if ($user['role'] == 'Admin') $roles = ['Admin']; // Admin overrides
                    else {
                        if ($user['is_seller'] > 0) $roles[] = 'Seller';
                        if ($user['is_delivery'] > 0) $roles[] = 'Delivery';
                    }
                    // Remove duplicates just in case
                    $roles = array_unique($roles);
                ?>
                <tr>
                    <td>#<?php echo $user['user_id']; ?></td>
                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                    <td>
                        <?php foreach($roles as $role): ?>
                            <span class="badge" style="background: #eee; color: #333; margin-right: 4px;"><?php echo $role; ?></span>
                        <?php endforeach; ?>
                    </td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td><span class="badge badge-<?php echo strtolower($user['status'] ?? 'active'); ?>"><?php echo htmlspecialchars($user['status'] ?? 'Active'); ?></span></td>
                    <td><?php echo date('d M Y', strtotime($user['created_at'])); ?></td>
                    <td>
                        <?php if(in_array('Seller', $roles)): ?>
                            <a href="admin_seller_menu.php?seller_id=<?php echo $user['user_id']; ?>" class="btn-view">View Menu</a>
                        <?php else: ?>
                            <span style="color: #ccc;">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if(empty($users)): ?>
            <p style="text-align: center; margin-top: 30px; color: #888;">No users found.</p>
        <?php endif; ?>
    </div>
    <script>function toggleSidebar(){document.querySelector('.sidebar').classList.toggle('collapsed');}</script>
</body>
</html>
