<?php
include 'role_check.php';
check_role_access('admin');
include 'db_connect.php';

$admin_name = htmlspecialchars($_SESSION['name']);

// Fetch all activity logs joined with user details
$logs = [];
$sql = "SELECT a.log_id, u.name as user_name, u.email, u.role, a.action, a.details, a.created_at 
        FROM activity_logs a 
        JOIN users u ON a.user_id = u.user_id 
        ORDER BY a.created_at DESC";
$stmt = $conn->prepare($sql);
if ($stmt) {
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs - Homely Bites</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary-color: #27ae60; --bg-body: #fdfbf7; --card-bg: #FFFFFF; --text-dark: #2c3e50; --border-radius: 12px; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: var(--bg-body); color: var(--text-dark); display: flex; min-height: 100vh; }
        .main-content { flex: 1; padding: 40px; overflow-y: auto; }
        
        h2 { font-size: 28px; font-weight: 700; margin-bottom: 30px; }
        
        .logs-table { width: 100%; border-collapse: collapse; background: white; border-radius: var(--border-radius); overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .logs-table th, .logs-table td { padding: 15px 20px; text-align: left; }
        .logs-table th { background-color: #f8f9fa; font-weight: 600; color: #555; }
        .logs-table tr { border-bottom: 1px solid #eee; }
        .logs-table tr:hover { background-color: #f9f9f9; }
        
        .badge { padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; display: inline-block; }
        .action-badge { background: #e8f5e9; color: #27ae60; border: 1px solid #c8e6c9; }
        
        header { display: flex; justify-content: flex-end; align-items: center; margin-bottom: 30px; }
        .admin-profile { display: flex; align-items: center; gap: 10px; }
        .profile-pic { width: 40px; height: 40px; background: #eee; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
        
        .user-info { display: flex; flex-direction: column; }
        .user-name { font-weight: 600; color: #333; }
        .user-email { font-size: 0.8rem; color: #777; }
        .log-details { font-size: 0.9rem; color: #555; max-width: 400px; line-height: 1.4; }
        .log-timestamp { font-size: 0.85rem; color: #888; }
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

        <h2><i class="fa-solid fa-list-check" style="color: var(--primary-color); margin-right: 10px;"></i> Activity Logs</h2>
        
        <table class="logs-table">
            <thead>
                <tr>
                    <th>Log ID</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Details</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($logs as $log): ?>
                <tr>
                    <td style="color: #888; font-size: 0.9rem;">#<?php echo $log['log_id']; ?></td>
                    <td>
                        <div class="user-info">
                            <span class="user-name"><?php echo htmlspecialchars($log['user_name']); ?></span>
                            <span class="user-email"><?php echo htmlspecialchars($log['email']); ?></span>
                        </div>
                    </td>
                    <td><span class="badge action-badge"><?php echo htmlspecialchars($log['action']); ?></span></td>
                    <td class="log-details"><?php echo htmlspecialchars($log['details']); ?></td>
                    <td class="log-timestamp"><?php echo date('d M Y, h:i A', strtotime($log['created_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if(empty($logs)): ?>
            <div style="text-align: center; margin-top: 50px; color: #888; padding: 40px; background: white; border-radius: 12px;">
                <i class="fa-solid fa-clipboard" style="font-size: 3rem; color: #ddd; margin-bottom: 15px;"></i>
                <p>No activity logs found yet. Actions performed by users will appear here.</p>
            </div>
        <?php endif; ?>
    </div>
    <script>function toggleSidebar(){document.querySelector('.sidebar').classList.toggle('collapsed');}</script>
</body>
</html>
