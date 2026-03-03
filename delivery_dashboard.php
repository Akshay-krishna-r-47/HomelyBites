<?php
session_start();
include_once 'helpers.php';
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// ACCESS CONTROL: Strict check for Delivery Approval
if (!isset($_SESSION['user_id']) || !isset($_SESSION['delivery_approved']) || $_SESSION['delivery_approved'] != 1) {
    header("Location: customer_dashboard.php");
    exit();
}
include 'db_connect.php';
$formatted_name = formatName($_SESSION['name']);
$user_name = htmlspecialchars($formatted_name);
$user_initials = getAvatarInitials($formatted_name);
$profile_img = getProfileImage($_SESSION['user_id'], $conn);

// Get current online status
$is_online = 0;
$stmt_online = $conn->prepare("SELECT is_online FROM users WHERE user_id = ?");
$stmt_online->bind_param("i", $_SESSION['user_id']);
$stmt_online->execute();
$res_online = $stmt_online->get_result();
if ($res_online->num_rows > 0) {
    $is_online = $res_online->fetch_assoc()['is_online'];
}
$stmt_online->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Dashboard - Homely Bites</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Lemon&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* DASHBOARD STYLES */
        :root {
            --brand-green: #008000;
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

        .header-title h2 { font-size: 1.5rem; font-weight: 700; color: #333; }
        .header-title span { font-size: 0.9rem; color: #888; }

        .user-info { display: flex; align-items: center; gap: 15px; text-align: right; }
        .profile-pic { width: 40px; height: 40px; background: #eee; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.9rem; color: #555; overflow: hidden; }
        
        /* Content Layout */
        .content-container { padding: 40px 60px; max-width: 1400px; margin: 0 auto; width: 100%; }

        /* Stats Grid */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 24px; margin-bottom: 40px; }
        
        .stat-card {
            background: white;
            padding: 24px;
            border-radius: 16px;
            box-shadow: var(--shadow-card);
            border: 1px solid rgba(0,0,0,0.03);
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .stat-icon {
            width: 60px; height: 60px;
            border-radius: 12px;
            background: #e8f5e9;
            color: #27ae60;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.8rem;
        }

        .stat-info h3 { font-size: 2rem; font-weight: 700; color: #333; margin-bottom: 2px; }
        .stat-info p { color: #888; font-size: 0.9rem; font-weight: 500; }

        /* Available Orders Section */
        .section-header { margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .section-title { font-size: 1.4rem; font-weight: 700; color: #333; }
        
        .empty-state { text-align: center; padding: 60px 20px; background: white; border-radius: 16px; box-shadow: var(--shadow-card); }
        .empty-state p { font-size: 1.1rem; color: #888; margin-top: 15px; }

        /* Online/Offline Toggle */
        .status-toggle {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #fff;
            padding: 8px 16px;
            border-radius: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border: 1px solid #eee;
        }
        .status-text {
            font-weight: 600;
            font-size: 0.9rem;
            color: #555;
        }
        .status-text.online { color: #27ae60; }
        .switch { position: relative; display: inline-block; width: 44px; height: 24px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 24px; }
        .slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: var(--brand-green); }
        input:checked + .slider:before { transform: translateX(20px); }

        /* Full Screen Pop-Up */
        .order-popup-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.85);
            z-index: 9999;
            display: none; /* hidden by default */
            align-items: center; justify-content: center;
            backdrop-filter: blur(5px);
        }
        .order-popup-card {
            background: white;
            border-radius: 20px;
            width: 90%; max-width: 400px;
            padding: 30px;
            text-align: center;
            animation: popIn 0.3s cubic-bezier(0.18, 0.89, 0.32, 1.28);
        }
        @keyframes popIn { 0% { transform: scale(0.8); opacity: 0; } 100% { transform: scale(1); opacity: 1; } }
        
        .timer-ring {
            width: 100px; height: 100px;
            border-radius: 50%;
            border: 6px solid #e0e0e0;
            border-top-color: var(--brand-green);
            margin: 0 auto 20px;
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem; font-weight: 700; color: #333;
            animation: spinTimer 20s linear forwards;
        }
        @keyframes spinTimer { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .timer-text { position: absolute; animation: counterSpin 20s linear forwards; }
        @keyframes counterSpin { 0% { transform: rotate(0deg); } 100% { transform: rotate(-360deg); } }

        .popup-title { font-size: 1.4rem; font-weight: 700; color: #333; margin-bottom: 10px; }
        .popup-details { text-align: left; background: #f9f9f9; padding: 15px; border-radius: 12px; margin-bottom: 25px; }
        .popup-details p { font-size: 0.95rem; margin-bottom: 8px; font-weight: 500; color: #555; }
        
        .popup-actions { display: flex; gap: 15px; }
        .btn-reject { flex: 1; padding: 14px; background: #fff; border: 2px solid #ddd; border-radius: 10px; font-weight: 600; color: #555; cursor: pointer; transition: 0.2s; }
        .btn-reject:hover { background: #fee2e2; border-color: #ef4444; color: #dc2626; }
        .btn-accept { flex: 2; padding: 14px; background: var(--brand-green); border: none; border-radius: 10px; font-weight: 700; color: #fff; cursor: pointer; box-shadow: 0 4px 12px rgba(0, 128, 0, 0.3); font-size: 1.1rem; transition: 0.2s; }
        .btn-accept:hover { background: #006600; transform: translateY(-2px); }

    </style>
</head>
<body>

    <?php include 'delivery_sidebar.php'; ?>

    <div class="main-content">
        <!-- Header -->
        <header>
            <div class="header-title" style="display: flex; gap: 30px; align-items: center;">
                <div>
                    <h2>Overview</h2>
                    <span>Welcome back, Delivery Partner!</span>
                </div>
                
                <div class="status-toggle">
                    <span class="status-text <?php echo $is_online ? 'online' : ''; ?>" id="statusLabel"><?php echo $is_online ? 'Online' : 'Offline'; ?></span>
                    <label class="switch">
                        <input type="checkbox" id="onlineToggle" <?php echo $is_online ? 'checked' : ''; ?>>
                        <span class="slider"></span>
                    </label>
                </div>
            </div>

            <div class="user-info">
                <div>
                    <p style="font-weight: 700; font-size: 0.95rem; margin-bottom: 2px; color: var(--text-dark);"><?php echo $user_name; ?></p>
                    <div style="display:flex; justify-content:end; align-items:center; gap:5px;">
                        <span style="font-size: 0.75rem; color: #fff; background-color: #27ae60; padding: 2px 6px; border-radius: 4px; font-weight: 600; text-transform: uppercase;">Delivery</span>
                    </div>
                </div>
                <div class="profile-pic">
                    <?php if ($profile_img): ?>
                        <img src="<?php echo $profile_img; ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        <?php echo $user_initials; ?>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <div class="content-container">
            
        <div class="content-container">
            <?php
            // Active Orders Count (Accepted or Out for delivery)
            $active_count = 0;
            $stmt1 = $conn->prepare("SELECT COUNT(order_id) as c FROM orders WHERE delivery_partner_id = ? AND status IN ('Accepted by Delivery', 'Out for Delivery')");
            $stmt1->bind_param("i", $_SESSION['user_id']);
            $stmt1->execute();
            $res1 = $stmt1->get_result();
            if ($res1->num_rows > 0) { $active_count = $res1->fetch_assoc()['c']; }
            
            // Completed Count
            $completed_count = 0;
            $stmt2 = $conn->prepare("SELECT COUNT(order_id) as c FROM orders WHERE delivery_partner_id = ? AND status = 'Delivered'");
            $stmt2->bind_param("i", $_SESSION['user_id']);
            $stmt2->execute();
            $res2 = $stmt2->get_result();
            if ($res2->num_rows > 0) { $completed_count = $res2->fetch_assoc()['c']; }
            
            // Total Earnings
            $total_earned = 0.00;
            $stmt3 = $conn->prepare("SELECT SUM(amount) as t FROM delivery_earnings WHERE delivery_partner_id = ?");
            $stmt3->bind_param("i", $_SESSION['user_id']);
            $stmt3->execute();
            $res3 = $stmt3->get_result();
            if ($res3->num_rows > 0) { 
                $row3 = $res3->fetch_assoc();
                if ($row3['t']) { $total_earned = $row3['t']; }
            }
            ?>
            
            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fa-solid fa-box"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $active_count; ?></h3>
                        <p>Active Orders</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fa-solid fa-check-double"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $completed_count; ?></h3>
                        <p>Completed</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fa-solid fa-indian-rupee-sign"></i></div>
                    <div class="stat-info">
                        <h3><?php echo number_format($total_earned, 2); ?></h3>
                        <p>Total Earnings</p>
                    </div>
                </div>
            </div>

            <!-- Active Orders Quick Link -->
            <div class="section-header">
                <h3 class="section-title">Current Assignments</h3>
            </div>

            <?php if ($active_count > 0): ?>
                <div class="empty-state" style="padding: 40px 20px;">
                    <i class="fa-solid fa-motorcycle" style="font-size: 3rem; color: var(--brand-green); margin-bottom: 15px;"></i>
                    <p style="color: #333; font-weight: 600;">You have <?php echo $active_count; ?> active delivery in progress.</p>
                    <a href="delivery_active.php" style="display: inline-block; margin-top: 15px; padding: 10px 25px; background: var(--brand-green); color: white; text-decoration: none; border-radius: 8px; font-weight: 600;">Track Active Deliveries</a>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fa-solid fa-bicycle" style="font-size: 3rem; color: #ddd; margin-bottom: 15px;"></i>
                    <p>No active delivery assignments. Check available orders!</p>
                    <a href="delivery_orders.php" style="display: inline-block; margin-top: 15px; padding: 10px 25px; background: #3b82f6; color: white; text-decoration: none; border-radius: 8px; font-weight: 600;">Find Orders</a>
                </div>
            <?php endif; ?>

        </div>
    </div>

        </div>
    </div>

    <!-- NEW ORDER POPUP -->
    <div class="order-popup-overlay" id="orderPopup">
        <div class="order-popup-card">
            <h3 style="color: #27ae60; font-size: 1.1rem; font-weight: 700; text-transform: uppercase; margin-bottom: 20px; letter-spacing: 1px;"><i class="fa-solid fa-bell fa-shake"></i> New Delivery Request</h3>
            
            <div class="timer-ring">
                <div class="timer-text" id="popupTimer">20</div>
            </div>
            
            <div class="popup-title" id="popValAmount">₹0.00</div>
            
            <div class="popup-details">
                <p><i class="fa-solid fa-store" style="color: #3b82f6; width: 20px;"></i> <b>Pickup:</b> <span id="popValPickup">...</span></p>
                <p><i class="fa-solid fa-house" style="color: #f97316; width: 20px;"></i> <b>Dropoff:</b> <span id="popValDropoff">...</span></p>
            </div>
            
            <div class="popup-actions">
                <button class="btn-reject" onclick="handleRequest('reject')">Reject</button>
                <button class="btn-accept" id="btnAccept" onclick="handleRequest('accept')">Accept Order</button>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('collapsed');
        }

        let isOnline = <?php echo $is_online ? 'true' : 'false'; ?>;
        const toggleBtn = document.getElementById('onlineToggle');
        const statusLabel = document.getElementById('statusLabel');

        // Location Tracking Variables
        let currentLat = null;
        let currentLng = null;
        let watchId = null;

        // Toggle Status Logic
        toggleBtn.addEventListener('change', function() {
            isOnline = this.checked;
            statusLabel.textContent = isOnline ? 'Online' : 'Offline';
            if(isOnline) {
                statusLabel.classList.add('online');
                startLocationTracking();
            } else {
                statusLabel.classList.remove('online');
                stopLocationTracking();
                updateServerStatus(0, null, null);
            }
        });

        function startLocationTracking() {
            if ("geolocation" in navigator) {
                watchId = navigator.geolocation.watchPosition(
                    (position) => {
                        currentLat = position.coords.latitude;
                        currentLng = position.coords.longitude;
                        updateServerStatus(1, currentLat, currentLng);
                    },
                    (error) => {
                        console.error("Location error:", error);
                        // Fallback to update online status without GPS
                        updateServerStatus(1, null, null);
                    },
                    { enableHighAccuracy: true, maximumAge: 10000, timeout: 5000 }
                );
            } else {
                updateServerStatus(1, null, null);
            }
        }

        function stopLocationTracking() {
            if (watchId !== null) {
                navigator.geolocation.clearWatch(watchId);
                watchId = null;
            }
        }

        function updateServerStatus(status, lat, lng) {
            fetch('api_delivery_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ status: status, lat: lat, lng: lng })
            }).catch(e => console.error(e));
        }

        // Initialize tracking if already online from previous session
        if (isOnline) startLocationTracking();


        // POLING FOR ORDER REQUESTS
        let currentRequestId = null;
        let pingerInterval = null;
        let countdownInterval = null;
        let timeRemaining = 0;
        const popup = document.getElementById('orderPopup');

        function startPolling() {
            pingerInterval = setInterval(() => {
                if (isOnline && !currentRequestId) {
                    fetch('api_check_requests.php')
                        .then(res => res.json())
                        .then(data => {
                            if (data.success && data.has_request) {
                                showPopup(data.request, data.time_remaining);
                            }
                        })
                        .catch(err => console.error(err));
                }
            }, 5000); // Check every 5 seconds
        }

        function showPopup(request, timeLimit) {
            currentRequestId = request.request_id;
            timeRemaining = Math.floor(timeLimit);
            
            document.getElementById('popValAmount').textContent = '₹' + request.total_amount;
            document.getElementById('popValPickup').textContent = request.seller_name; // Truncate formatting if needed
            document.getElementById('popValDropoff').textContent = request.dropoff_address.substring(0, 30) + '...';
            
            document.getElementById('popupTimer').textContent = timeRemaining;
            popup.style.display = 'flex';
            
            countdownInterval = setInterval(() => {
                timeRemaining--;
                document.getElementById('popupTimer').textContent = timeRemaining;
                
                if (timeRemaining <= 0) {
                    clearInterval(countdownInterval);
                    popup.style.display = 'none';
                    currentRequestId = null;
                    // API implicitly times out or we force a reload
                }
            }, 1000);
        }

        function handleRequest(action) {
            if (!currentRequestId) return;
            
            // disable buttons
            document.getElementById('btnAccept').disabled = true;
            
            fetch('api_handle_request.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ request_id: currentRequestId, action: action })
            })
            .then(res => res.json())
            .then(data => {
                clearInterval(countdownInterval);
                popup.style.display = 'none';
                currentRequestId = null;
                document.getElementById('btnAccept').disabled = false;
                
                if (data.success) {
                    if (action === 'accept' && data.redirect) {
                        window.location.href = data.redirect;
                    } 
                    // if reject, just dismiss popup and polling continues
                }
            });
        }

        // Start polling on page load
        startPolling();

    </script>
</body>
</html>
