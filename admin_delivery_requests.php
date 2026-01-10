<?php
session_start();
// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}
$admin_name = htmlspecialchars($_SESSION['name']);
include_once 'db_connect.php';

// Fetch Pending Applications
$pending_sql = "SELECT da.*, u.email, u.name as user_real_name FROM delivery_applications da JOIN users u ON da.user_id = u.user_id WHERE da.status = 'Pending' ORDER BY da.created_at DESC";
$pending_result = $conn->query($pending_sql);
$pending_count = $pending_result ? $pending_result->num_rows : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Requests - Homely Bites</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Lemon&family=Lato:wght@300;400;700&family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary-color: #27ae60; --brand-green: #008000; --bg-body: #fdfbf7; --card-bg: #FFFFFF; --text-dark: #2c3e50; --text-muted: #7f8c8d; --header-height: 80px; --border-radius: 16px; --shadow-sm: 0 2px 8px rgba(0,0,0,0.04); }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Lato', sans-serif; }
        body { background-color: var(--bg-body); color: var(--text-dark); display: flex; min-height: 100vh; }
        .main-content { flex: 1; display: flex; flex-direction: column; width: 0; transition: all 0.4s ease; }
        header { height: var(--header-height); background-color: var(--card-bg); padding: 0 40px; display: flex; align-items: center; justify-content: flex-end; position: sticky; top: 0; z-index: 900; border-bottom: 1px solid rgba(0,0,0,0.06); }
        .admin-profile { display: flex; align-items: center; gap: 15px; }
        .profile-pic { width: 42px; height: 42px; background: linear-gradient(135deg, var(--brand-green), #27ae60); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.1rem; }
        .content-container { padding: 40px 50px; max-width: 1600px; margin: 0 auto; width: 100%; }
        .dashboard-box { background-color: var(--card-bg); padding: 30px; border-radius: var(--border-radius); box-shadow: var(--shadow-sm); border: 1px solid rgba(0,0,0,0.06); }
        .box-title { font-family: 'Playfair Display', serif; font-size: 1.4rem; margin-bottom: 25px; color: var(--text-dark); display: flex; justify-content: space-between; align-items: center; }
        table { width: 100%; border-collapse: separate; border-spacing: 0; }
        th { text-align: left; padding: 15px; color: var(--text-muted); font-weight: 600; font-size: 0.8rem; text-transform: uppercase; border-bottom: 2px solid #f0f0f0; }
        td { padding: 20px 15px; font-size: 0.95rem; border-bottom: 1px solid #f5f5f5; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        .status-badge { background: #fff3e0; color: #f39c12; padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }
        .btn-action { border: none; padding: 8px 16px; border-radius: 6px; font-weight: 600; cursor: pointer; transition: opacity 0.2s; font-size: 0.85rem; }
        .btn-approve { background-color: #e8f5e9; color: #2e7d32; margin-right: 8px; }
        .btn-reject { background-color: #ffebee; color: #c62828; }
        .btn-action:hover { opacity: 0.8; }
        @media (max-width: 768px) { .sidebar { width: 70px; } .sidebar-logo, .nav-links span { display: none; } .main-content { margin-left: 0; } }
    </style>
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>
    <div class="main-content">
        <header>
            <div class="admin-profile">
                <div style="text-align: right;">
                    <p style="font-weight: 700; font-size: 0.95rem; margin-bottom: 2px;"><?php echo $admin_name; ?></p>
                    <span style="font-size: 0.75rem; color: #7f8c8d; font-weight: 500; text-transform: uppercase;">Administrator</span>
                </div>
                <div class="profile-pic"><i class="fa-solid fa-user-shield"></i></div>
            </div>
        </header>

        <div class="content-container">
            <h2 style="font-family: 'Playfair Display', serif; font-size: 2.2rem; margin-bottom: 30px;">Delivery Requests</h2>
            <?php if (isset($_SESSION['message'])): ?>
                <div style="padding: 10px; margin-bottom: 20px; border-radius: 6px; background: <?php echo ($_SESSION['message_type'] == 'success') ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo ($_SESSION['message_type'] == 'success') ? '#155724' : '#721c24'; ?>;">
                    <?php echo $_SESSION['message']; unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
                </div>
            <?php endif; ?>

            <div class="dashboard-box">
                <h3 class="box-title">
                    Pending Applications
                    <span style="font-size: 0.9rem; background: #e74c3c; color: white; padding: 2px 8px; border-radius: 4px;"><?php echo $pending_count; ?> Pending</span>
                </h3>
                <table>
                    <thead>
                        <tr>
                            <th>Applicant</th>
                            <th>Vehicle Details</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($pending_count > 0): ?>
                            <?php while($row = $pending_result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['user_real_name']); ?></strong><br>
                                    <span style="font-size: 0.8rem; color: #888;"><?php echo htmlspecialchars($row['availability']); ?></span>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['vehicle_type']); ?></strong><br>
                                    <span style="font-size: 0.85rem; color: #555;"><?php echo htmlspecialchars($row['vehicle_number']); ?></span><br>
                                    <span style="font-size: 0.75rem; color: #aaa;">Lic: <?php echo htmlspecialchars($row['license_number']); ?></span>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($row['email']); ?><br>
                                    <span style="font-size: 0.8rem;"><?php echo htmlspecialchars($row['phone']); ?></span>
                                </td>
                                <td><span class="status-badge">Pending</span></td>
                                <td>
                                    <form action="admin_handle_delivery.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="application_id" value="<?php echo $row['id']; ?>">
                                        <input type="hidden" name="applicant_user_id" value="<?php echo $row['user_id']; ?>">
                                        <button type="submit" name="action" value="approve" class="btn-action btn-approve" onclick="return confirm('Approve this delivery partner? This will change their role immediately.');">Approve</button>
                                        <button type="submit" name="action" value="reject" class="btn-action btn-reject" onclick="return confirm('Reject this application?');">Reject</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align:center; color: #999; padding: 40px;">No pending delivery requests found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script>function toggleSidebar(){document.querySelector('.sidebar').classList.toggle('collapsed');}</script>
</body>
</html>
