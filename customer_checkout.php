<?php
include 'role_check.php';
check_role_access('customer');
include 'db_connect.php';
include_once 'helpers.php';

$user_id = $_SESSION['user_id'];
$user_name = htmlspecialchars($_SESSION['name']);

// Fetch User's Home Address
$user_stmt = $conn->prepare("SELECT street, city, pincode FROM users WHERE user_id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();
$home_address = "";
if ($user_data) {
    $parts = array_filter([trim($user_data['street']), trim($user_data['city']), trim($user_data['pincode'])]);
    $home_address = htmlspecialchars(implode(', ', $parts));
}
$user_stmt->close();

// Fetch Cart items to display summary
$cart_sql = "SELECT c.id, c.quantity, f.name, f.price, u.name as seller_name 
             FROM cart c 
             JOIN foods f ON c.food_id = f.id 
             JOIN users u ON f.seller_id = u.user_id 
             WHERE c.user_id = ?";
$stmt = $conn->prepare($cart_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$total_price = 0;
$cart_items = [];
while ($row = $result->fetch_assoc()) {
    $row['total'] = $row['price'] * $row['quantity'];
    $total_price += $row['total'];
    $cart_items[] = $row;
}
$stmt->close();

if (empty($cart_items)) {
    header("Location: customer_cart.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Homely Bites</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        :root { --primary-color: #fc8019; --brand-green: #0a8f08; --bg-body: #f8f8f8; --text-dark: #222; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: var(--bg-body); color: var(--text-dark); display: flex; min-height: 100vh; }
        .main-content { flex: 1; padding: 40px; }
        
        .checkout-container { max-width: 1000px; margin: 0 auto; display: flex; gap: 30px; }
        .checkout-form { flex: 2; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .order-summary { flex: 1; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); height: fit-content; }
        
        h2 { margin-bottom: 20px; font-size: 1.5rem; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; }
        .form-group input, .form-group textarea { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem; }
        
        .summary-item { display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 0.95rem; }
        .total-row { display: flex; justify-content: space-between; font-weight: 700; font-size: 1.2rem; margin-top: 20px; border-top: 1px solid #eee; padding-top: 15px; }
        
        
        .btn-pay { width: 100%; background: var(--brand-green); color: white; padding: 15px; border: none; border-radius: 8px; font-size: 1.1rem; font-weight: 600; cursor: pointer; margin-top: 20px; }
        .btn-pay:hover { background: #087f06; }
        
        /* Payment Options Styling */
        .payment-options { display: flex; flex-direction: column; gap: 12px; }
        .payment-option {
            display: flex;
            align-items: center;
            padding: 16px;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
            background: #fff;
        }
        .payment-option:hover {
            border-color: #fc8019;
            background: #fffaf5;
        }
        .payment-option.selected {
            border-color: #0a8f08;
            background: #f0fdf4;
            box-shadow: 0 0 0 1px #0a8f08;
        }
        .payment-option input[type="radio"] {
            margin-right: 15px;
            transform: scale(1.2);
            accent-color: #0a8f08;
        }
        .payment-icon {
            font-size: 1.4rem;
            margin-right: 15px;
            width: 30px;
            text-align: center;
        }
        .payment-details {
            display: flex;
            flex-direction: column;
        }
        .payment-title {
            font-weight: 600;
            font-size: 1rem;
            color: #333;
        }
        .payment-subtitle {
            font-size: 0.8rem;
            color: #777;
            margin-top: 2px;
        }
    </style>
</head>
<body>
    <?php include 'customer_sidebar.php'; ?>
    <div class="main-content">
        <div class="checkout-container">
            <div class="checkout-form">
                <h2>Delivery Details</h2>
                <form action="place_order.php" method="POST">
                    <div class="form-group">
                        <label style="display: flex; justify-content: space-between; align-items: center;">
                            <span>Delivery Address</span>
                            <div style="display: flex; gap: 15px;">
                                <?php if (!empty($home_address)): ?>
                                    <button type="button" id="btn-home" style="background: none; border: none; color: #fc8019; font-weight: 600; cursor: pointer; font-size: 0.85rem;"><i class="fa-solid fa-house"></i> Home Address</button>
                                <?php endif; ?>
                                <button type="button" id="btn-locate" style="background: none; border: none; color: #0a8f08; font-weight: 600; cursor: pointer; font-size: 0.85rem;"><i class="fa-solid fa-location-crosshairs"></i> Use Current Location</button>
                            </div>
                        </label>
                        <textarea name="address" id="address-field" rows="3" required placeholder="Enter your full address"></textarea>
                        <div id="location-status" style="font-size: 0.85rem; color: #666; margin-top: 5px; display: none;"></div>
                        <div id="map" style="height: 250px; border-radius: 8px; margin-top: 15px; border: 1px solid #ddd; z-index: 1;"></div>
                        <div style="font-size: 0.8rem; color: #888; margin-top: 5px; text-align: right;"><i class="fa-solid fa-circle-info"></i> You can drag the pin or click on the map to adjust your exact location.</div>
                        <input type="hidden" name="latitude" id="latitude">
                        <input type="hidden" name="longitude" id="longitude">
                    </div>
                    <div class="form-group">
                        <label>Payment Method</label>
                        <div class="payment-options">
                            <label class="payment-option selected" id="opt-cod">
                                <input type="radio" name="payment_method" value="COD" checked onclick="updatePaymentSelection(this)">
                                <i class="fa-solid fa-money-bill-wave payment-icon" style="color: #2ecc71;"></i>
                                <div class="payment-details">
                                    <span class="payment-title">Cash on Delivery</span>
                                    <span class="payment-subtitle">Pay with cash when your food arrives.</span>
                                </div>
                            </label>

                            <label class="payment-option" id="opt-upi">
                                <input type="radio" name="payment_method" value="UPI" onclick="updatePaymentSelection(this)">
                                <i class="fa-solid fa-qrcode payment-icon" style="color: #8e44ad;"></i>
                                <div class="payment-details">
                                    <span class="payment-title">UPI / QR Code</span>
                                    <span class="payment-subtitle">Google Pay, PhonePe, Paytm, etc.</span>
                                </div>
                            </label>

                            <label class="payment-option" id="opt-card">
                                <input type="radio" name="payment_method" value="Card" onclick="updatePaymentSelection(this)">
                                <i class="fa-solid fa-credit-card payment-icon" style="color: #2980b9;"></i>
                                <div class="payment-details">
                                    <span class="payment-title">Credit / Debit Card</span>
                                    <span class="payment-subtitle">Visa, MasterCard, RuPay accepted.</span>
                                </div>
                            </label>
                            
                            <label class="payment-option" id="opt-netbanking">
                                <input type="radio" name="payment_method" value="Net Banking" onclick="updatePaymentSelection(this)">
                                <i class="fa-solid fa-building-columns payment-icon" style="color: #e67e22;"></i>
                                <div class="payment-details">
                                    <span class="payment-title">Net Banking</span>
                                    <span class="payment-subtitle">All major Indian banks supported.</span>
                                </div>
                            </label>
                        </div>
                    </div>
                    <input type="hidden" name="total_amount" value="<?php echo $total_price; ?>">
                    <button type="submit" class="btn-pay">Place Order</button>
                </form>
            </div>
            
            <div class="order-summary">
                <h2>Order Summary</h2>
                <?php foreach ($cart_items as $item): 
                     // Fix seller name fallback if valid column missing or empty
                     $seller_name = !empty($item['seller_name']) ? htmlspecialchars($item['seller_name']) : 'Homely Chef';
                ?>
                <div class="summary-item">
                    <div style="flex: 1;">
                        <span style="font-weight: 600;"><?php echo htmlspecialchars($item['name']); ?></span>
                        <div style="font-size: 0.8rem; color: #777;">x <?php echo $item['quantity']; ?></div>
                    </div>
                    <span>₹<?php echo $item['total']; ?></span>
                </div>
                <?php endforeach; ?>
                
                <div class="total-row">
                    <span>To Pay</span>
                    <span>₹<?php echo $total_price; ?></span>
                </div>
            </div>
        </div>
    </div>
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        function toggleSidebar(){ document.querySelector('.sidebar').classList.toggle('collapsed'); }

        // Initialize Map
        const defaultLat = 20.5937;
        const defaultLng = 78.9629;
        
        const map = L.map('map').setView([defaultLat, defaultLng], 4);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        let marker;

        function setMapLocation(lat, lng, addressString = '') {
            if (marker) {
                marker.setLatLng([lat, lng]);
            } else {
                marker = L.marker([lat, lng], {draggable: true}).addTo(map);
                
                marker.on('dragend', function(e) {
                    const position = marker.getLatLng();
                    const newLat = position.lat;
                    const newLng = position.lng;
                    
                    const statusDiv = document.getElementById('location-status');
                    statusDiv.style.display = 'block';
                    statusDiv.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Fetching address for pinned location...';
                    
                    document.getElementById('latitude').value = newLat;
                    document.getElementById('longitude').value = newLng;
                    
                    fetch(`reverse_geocode.php?lat=${newLat}&lon=${newLng}`)
                        .then(response => {
                            if (!response.ok) throw new Error("Network response not ok");
                            return response.json();
                        })
                        .then(data => {
                            if (data && data.display_name) {
                                document.getElementById('address-field').value = data.display_name;
                                statusDiv.innerHTML = '<i class="fa-solid fa-check" style="color: #0a8f08;"></i> Address updated from pin!';
                                setTimeout(() => statusDiv.style.display = 'none', 3000);
                            } else {
                                statusDiv.innerHTML = '<i class="fa-solid fa-triangle-exclamation" style="color: #d32f2f;"></i> Could not determine address.';
                            }
                        })
                        .catch(error => {
                            statusDiv.innerHTML = '<i class="fa-solid fa-triangle-exclamation" style="color: #d32f2f;"></i> Error fetching address.';
                        });
                });
            }
            map.setView([lat, lng], 15);
            
            document.getElementById('latitude').value = lat;
            document.getElementById('longitude').value = lng;
            
            if (addressString) {
                document.getElementById('address-field').value = addressString;
            }
        }

        // Map Click Event
        map.on('click', function(e) {
            const lat = e.latlng.lat;
            const lng = e.latlng.lng;
            
            const statusDiv = document.getElementById('location-status');
            statusDiv.style.display = 'block';
            statusDiv.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Fetching address for selected location...';
            
            setMapLocation(lat, lng);
            
            fetch(`reverse_geocode.php?lat=${lat}&lon=${lng}`)
                .then(response => {
                    if (!response.ok) throw new Error("Network response not ok");
                    return response.json();
                })
                .then(data => {
                    if (data && data.display_name) {
                        document.getElementById('address-field').value = data.display_name;
                        statusDiv.innerHTML = '<i class="fa-solid fa-check" style="color: #0a8f08;"></i> Address updated from map!';
                        setTimeout(() => statusDiv.style.display = 'none', 3000);
                    } else {
                        statusDiv.innerHTML = '<i class="fa-solid fa-triangle-exclamation" style="color: #d32f2f;"></i> Could not determine address.';
                    }
                })
                .catch(error => {
                    statusDiv.innerHTML = '<i class="fa-solid fa-triangle-exclamation" style="color: #d32f2f;"></i> Error fetching address.';
                });
        });

        // Location Logic
        const btnHome = document.getElementById('btn-home');
        if (btnHome) {
            btnHome.addEventListener('click', function() {
                const statusDiv = document.getElementById('location-status');
                const addressField = document.getElementById('address-field');
                const homeAddr = <?php echo json_encode($home_address); ?>;
                
                addressField.value = homeAddr;
                statusDiv.style.display = 'block';
                statusDiv.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Locating home address on map...';
                
                document.getElementById('latitude').value = '';
                document.getElementById('longitude').value = '';
                
                fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(homeAddr)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data && data.length > 0) {
                            const lat = data[0].lat;
                            const lon = data[0].lon;
                            setMapLocation(lat, lon, homeAddr);
                            statusDiv.innerHTML = '<i class="fa-solid fa-house" style="color: #fc8019;"></i> Using saved home address!';
                        } else {
                            statusDiv.innerHTML = '<i class="fa-solid fa-house" style="color: #fc8019;"></i> Using saved home address (Map location not found).';
                        }
                        setTimeout(() => statusDiv.style.display = 'none', 3000);
                    })
                    .catch(error => {
                        console.error(error);
                        statusDiv.innerHTML = '<i class="fa-solid fa-house" style="color: #fc8019;"></i> Using saved home address!';
                        setTimeout(() => statusDiv.style.display = 'none', 3000);
                    });
            });
        }

        const btnLocate = document.getElementById('btn-locate');
        if (btnLocate) {
            btnLocate.addEventListener('click', function() {
                const statusDiv = document.getElementById('location-status');
                
                statusDiv.style.display = 'block';
                statusDiv.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Fetching location...';
                
                if ("geolocation" in navigator) {
                    navigator.geolocation.getCurrentPosition(function(position) {
                        const lat = position.coords.latitude;
                        const lon = position.coords.longitude;
                        
                        setMapLocation(lat, lon);
                        
                        fetch(`reverse_geocode.php?lat=${lat}&lon=${lon}`)
                            .then(response => {
                                if (!response.ok) throw new Error("Network response not ok");
                                return response.json();
                            })
                            .then(data => {
                                if (data && data.display_name) {
                                    document.getElementById('address-field').value = data.display_name;
                                    statusDiv.innerHTML = '<i class="fa-solid fa-check" style="color: #0a8f08;"></i> Location found!';
                                    setTimeout(() => statusDiv.style.display = 'none', 3000);
                                } else {
                                    statusDiv.innerHTML = '<i class="fa-solid fa-triangle-exclamation" style="color: #d32f2f;"></i> Could not determine address.';
                                }
                            })
                            .catch(error => {
                                console.error(error);
                                statusDiv.innerHTML = '<i class="fa-solid fa-triangle-exclamation" style="color: #d32f2f;"></i> Error fetching address: ' + error.message;
                            });
                    }, function(error) {
                        statusDiv.innerHTML = '<i class="fa-solid fa-triangle-exclamation" style="color: #d32f2f;"></i> ' + error.message;
                    }, {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 0
                    });
                } else {
                    statusDiv.innerHTML = '<i class="fa-solid fa-triangle-exclamation" style="color: #d32f2f;"></i> Geolocation is not supported by your browser.';
                }
            });
        }

        // Payment UI update
        function updatePaymentSelection(selectedRadio) {
            // Remove 'selected' class from all options
            document.querySelectorAll('.payment-option').forEach(el => {
                el.classList.remove('selected');
            });
            // Add 'selected' class to the parent label of the clicked radio
            selectedRadio.closest('.payment-option').classList.add('selected');
        }
    </script>
</body>
</html>
