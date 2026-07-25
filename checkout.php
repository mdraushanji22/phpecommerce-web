<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

requireUserLogin();

$db = getDB();
$userId = $_SESSION['user_id'];

// Get cart items
$stmt = $db->prepare("
    SELECT c.id as cart_id, c.quantity, p.id as product_id, p.title, p.price, p.stock
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ? AND p.status = 'active'
");
$stmt->execute([$userId]);
$cartItems = $stmt->fetchAll();

if (count($cartItems) === 0) {
    header('Location: ' . SITE_URL . '/cart.php');
    exit;
}

// Calculate totals
$subtotal = 0;
foreach ($cartItems as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$shipping = $subtotal > 500 ? 0 : 50;
$total = $subtotal + $shipping;

// Get user details
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$error = '';

// Process COD order (form submit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['payment_method'] ?? '') === 'COD') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $mobile = sanitize($_POST['mobile'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $city = sanitize($_POST['city'] ?? '');
    $state = sanitize($_POST['state'] ?? '');
    $pincode = sanitize($_POST['pincode'] ?? '');

    if (empty($name) || empty($email) || empty($mobile) || empty($address) || empty($city) || empty($state) || empty($pincode)) {
        $error = 'Please fill all required fields';
    } else {
        try {
            $db->beginTransaction();

            // Validate stock
            foreach ($cartItems as $item) {
                $stockCheck = $db->prepare("SELECT stock FROM products WHERE id = ? AND status = 'active' FOR UPDATE");
                $stockCheck->execute([$item['product_id']]);
                $currentStock = $stockCheck->fetchColumn();
                
                if ($currentStock === false || $currentStock < $item['quantity']) {
                    $db->rollBack();
                    $error = 'Sorry, "' . htmlspecialchars($item['title']) . '" has insufficient stock. Available: ' . ($currentStock !== false ? $currentStock : 0);
                    break;
                }
            }

            if (empty($error)) {
                $orderNumber = generateOrderNumber();

                $stmt = $db->prepare("
                    INSERT INTO orders (
                        user_id, order_number, total_amount, payment_method, payment_status,
                        shipping_name, shipping_email, shipping_mobile, shipping_address, 
                        shipping_city, shipping_state, shipping_pincode
                    ) VALUES (?, ?, ?, 'COD', 'pending', ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $userId, $orderNumber, $total,
                    $name, $email, $mobile, $address, $city, $state, $pincode
                ]);

                $orderId = $db->lastInsertId();

                foreach ($cartItems as $item) {
                    $stmt = $db->prepare("
                        INSERT INTO order_items (order_id, product_id, product_title, product_price, quantity, subtotal)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $itemSubtotal = $item['price'] * $item['quantity'];
                    $stmt->execute([
                        $orderId, $item['product_id'], $item['title'], 
                        $item['price'], $item['quantity'], $itemSubtotal
                    ]);

                    $stmt = $db->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                    $stmt->execute([$item['quantity'], $item['product_id']]);
                }

                $stmt = $db->prepare("DELETE FROM cart WHERE user_id = ?");
                $stmt->execute([$userId]);

                $db->commit();

                setFlashMessage('success', 'Order placed successfully! Order Number: ' . $orderNumber);
                header('Location: ' . USER_URL . '/orders.php');
                exit;
            }

        } catch (Exception $e) {
            $db->rollBack();
            $error = 'Failed to place order. Please try again.';
        }
    }
}

$pageTitle = 'Checkout';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Razorpay SDK - loaded from CDN -->
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>

<div class="container my-5">
    <h2 class="mb-4"><i class="bi bi-credit-card"></i> Checkout</h2>

    <div class="row">
        <div class="col-md-7">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Shipping Information</h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <button type="button" class="btn btn-outline-primary w-100" id="useCurrentLocationBtn" onclick="useCurrentLocation()">
                            <i class="bi bi-geo-alt"></i> Use My Current Location
                        </button>
                        <div id="locationFillStatus" class="mt-2" style="display:none;"></div>
                    </div>

                    <form id="checkoutForm" method="POST" action="">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Full Name *</label>
                                <input type="text" class="form-control" id="name" name="name" required
                                       value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : htmlspecialchars($user['name']); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email" required
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : htmlspecialchars($user['email']); ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="mobile" class="form-label">Mobile Number *</label>
                            <input type="text" class="form-control" id="mobile" name="mobile" required
                                   value="<?php echo isset($_POST['mobile']) ? htmlspecialchars($_POST['mobile']) : htmlspecialchars($user['mobile']); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">Address *</label>
                            <textarea class="form-control" id="address" name="address" rows="3" required><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : htmlspecialchars($user['address'] ?? ''); ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="city" class="form-label">City *</label>
                                <input type="text" class="form-control" id="city" name="city" required
                                       value="<?php echo isset($_POST['city']) ? htmlspecialchars($_POST['city']) : htmlspecialchars($user['city'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="state" class="form-label">State *</label>
                                <input type="text" class="form-control" id="state" name="state" required
                                       value="<?php echo isset($_POST['state']) ? htmlspecialchars($_POST['state']) : htmlspecialchars($user['state'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="pincode" class="form-label">Pincode *</label>
                            <input type="text" class="form-control" id="pincode" name="pincode" required
                                   value="<?php echo isset($_POST['pincode']) ? htmlspecialchars($_POST['pincode']) : htmlspecialchars($user['pincode'] ?? ''); ?>">
                        </div>

                        <!-- Payment Method Selection -->
                        <div class="card bg-light mb-3">
                            <div class="card-body">
                                <h6>Payment Method</h6>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="payment_method" id="cod" value="COD" checked onchange="togglePaymentMethod()">
                                    <label class="form-check-label" for="cod">
                                        <i class="bi bi-cash"></i> Cash on Delivery (COD)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" id="razorpay" value="Razorpay" onchange="togglePaymentMethod()">
                                    <label class="form-check-label" for="razorpay">
                                        <i class="bi bi-credit-card"></i> Pay Online (Razorpay) - UPI / Card / NetBanking / Wallets
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- COD Submit Button -->
                        <button type="submit" id="codButton" class="btn btn-success w-100">
                            <i class="bi bi-check-circle"></i> Place Order (COD)
                        </button>

                        <!-- Razorpay Pay Now Button -->
                        <button type="button" id="razorpayButton" class="btn btn-success w-100" style="display:none;" onclick="initiateRazorpayPayment()">
                            <i class="bi bi-shield-lock"></i> Pay <?php echo formatPrice($total); ?> Now
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-5">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Order Summary</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6>Items (<?php echo count($cartItems); ?>):</h6>
                        <?php foreach ($cartItems as $item): ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted"><?php echo htmlspecialchars($item['title']); ?> x <?php echo $item['quantity']; ?></span>
                            <span><?php echo formatPrice($item['price'] * $item['quantity']); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <hr>

                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal:</span>
                        <span><?php echo formatPrice($subtotal); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Shipping:</span>
                        <span><?php echo $shipping > 0 ? formatPrice($shipping) : 'FREE'; ?></span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <strong>Total Amount:</strong>
                        <strong class="text-success"><?php echo formatPrice($total); ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * Current Location Feature
 * Auto-fills shipping fields from saved location or live geolocation
 */
(function() {
    var saved = localStorage.getItem('userLocation');
    if (saved) {
        try {
            var loc = JSON.parse(saved);
            if (loc.address && !document.getElementById('address').value.trim()) {
                document.getElementById('address').value = loc.address;
            }
            if (loc.city && !document.getElementById('city').value.trim()) {
                document.getElementById('city').value = loc.city;
            }
            if (loc.state && !document.getElementById('state').value.trim()) {
                document.getElementById('state').value = loc.state;
            }
            if (loc.pincode && !document.getElementById('pincode').value.trim()) {
                document.getElementById('pincode').value = loc.pincode;
            }
        } catch(e) {}
    }
})();

function useCurrentLocation() {
    var btn = document.getElementById('useCurrentLocationBtn');
    var statusEl = document.getElementById('locationFillStatus');

    if (!navigator.geolocation) {
        statusEl.style.display = 'block';
        statusEl.innerHTML = '<span class="text-danger small">Geolocation is not supported by your browser.</span>';
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Fetching location...';
    statusEl.style.display = 'block';
    statusEl.innerHTML = '<span class="text-muted small">Please allow location access when prompted.</span>';

    navigator.geolocation.getCurrentPosition(function(position) {
        var lat = position.coords.latitude;
        var lon = position.coords.longitude;
        statusEl.innerHTML = '<span class="text-muted small">Fetching address details...</span>';

        fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat=' + lat + '&lon=' + lon + '&zoom=18&addressdetails=1', {
            headers: { 'Accept': 'application/json' }
        })
        .then(function(resp) { return resp.json(); })
        .then(function(data) {
            var addr = data.address || {};
            var address = [addr.house_number, addr.road, addr.neighbourhood, addr.suburb].filter(Boolean).join(', ') || data.display_name || '';
            var city = addr.city || addr.town || addr.village || addr.county || '';
            var state = addr.state || '';
            var pincode = addr.postcode || '';

            if (address) document.getElementById('address').value = address;
            if (city) document.getElementById('city').value = city;
            if (state) document.getElementById('state').value = state;
            if (pincode) document.getElementById('pincode').value = pincode;

            localStorage.setItem('userLocation', JSON.stringify({
                lat: lat, lon: lon, address: address, city: city, state: state, pincode: pincode
            }));

            btn.innerHTML = '<i class="bi bi-check-lg"></i> Location Filled!';
            btn.classList.remove('btn-outline-primary');
            btn.classList.add('btn-success');
            statusEl.innerHTML = '<span class="text-success small"><i class="bi bi-check-circle"></i> Shipping address auto-filled from your current location.</span>';

            setTimeout(function() {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-geo-alt"></i> Use My Current Location';
                btn.classList.remove('btn-success');
                btn.classList.add('btn-outline-primary');
                statusEl.style.display = 'none';
            }, 3000);
        })
        .catch(function() {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-geo-alt"></i> Use My Current Location';
            statusEl.innerHTML = '<span class="text-danger small">Could not fetch address. Please enter manually.</span>';
        });
    }, function(error) {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-geo-alt"></i> Use My Current Location';
        var msg = 'Location access was denied. Please enter your address manually.';
        if (error.code === error.TIMEOUT) msg = 'Location request timed out. Please try again.';
        statusEl.innerHTML = '<span class="text-danger small">' + msg + '</span>';
    }, { enableHighAccuracy: true, timeout: 10000, maximumAge: 300000 });
}

/**
 * Razorpay Payment Integration Script
 * 
 * HOW IT WORKS (Step by Step):
 * 
 * STEP 1: User fills shipping form and selects "Razorpay" payment method
 * STEP 2: User clicks "Pay Now" button
 * STEP 3: JavaScript validates the form
 * STEP 4: JS calls razorpay-create-order.php via AJAX to create a Razorpay order
 * STEP 5: Razorpay returns an order_id
 * STEP 6: JS opens the Razorpay payment popup with the order_id
 * STEP 7: User completes payment (UPI/Card/NetBanking) in the popup
 * STEP 8: Razorpay sends payment details (payment_id, signature) back to JS
 * STEP 9: JS sends payment details to razorpay-verify.php for verification
 * STEP 10: Server verifies the signature, saves order, and returns success
 * STEP 11: User is redirected to order confirmation page
 */

// Toggle between COD and Razorpay payment buttons
function togglePaymentMethod() {
    const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
    const codButton = document.getElementById('codButton');
    const razorpayButton = document.getElementById('razorpayButton');
    
    if (paymentMethod === 'Razorpay') {
        codButton.style.display = 'none';
        razorpayButton.style.display = 'block';
    } else {
        codButton.style.display = 'block';
        razorpayButton.style.display = 'none';
    }
}

// Validate shipping form before payment
function validateForm() {
    const fields = ['name', 'email', 'mobile', 'address', 'city', 'state', 'pincode'];
    for (const field of fields) {
        const element = document.getElementById(field);
        if (!element.value.trim()) {
            element.focus();
            alert('Please fill in the ' + field.charAt(0).toUpperCase() + field.slice(1) + ' field');
            return false;
        }
    }
    return true;
}

/**
 * Initiate Razorpay Payment
 * 
 * This function:
 * 1. Validates the form
 * 2. Sends amount to server to create a Razorpay order
 * 3. Opens the Razorpay payment popup
 * 4. Handles success/failure callbacks
 */
function initiateRazorpayPayment() {
    // Step 1: Validate form
    if (!validateForm()) {
        return;
    }
    
    // Step 2: Create Razorpay order via AJAX
    const totalAmount = <?php echo $total; ?>;
    
    fetch('<?php echo SITE_URL; ?>/razorpay-create-order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ amount: totalAmount })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            alert('Error: ' + data.message);
            return;
        }
        
        // Step 3: Open Razorpay payment popup
        const options = {
            key: data.key_id,                           // Your Razorpay Key ID
            amount: data.amount,                         // Amount in paise
            currency: data.currency,                     // INR
            name: '<?php echo SITE_NAME; ?>',            // Your business name
            description: 'Order Payment',                // Payment description
            order_id: data.order_id,                     // Razorpay Order ID
            
            // Handler: called after successful payment
            handler: function (response) {
                // Step 4: Verify payment on server
                verifyPayment(response);
            },
            
            // Prefill: auto-fill user details in payment popup
            prefill: {
                name: document.getElementById('name').value,
                email: document.getElementById('email').value,
                contact: document.getElementById('mobile').value
            },
            
            // Theme customization
            theme: {
                color: '#0d6efd'     // Bootstrap primary color
            },
            
            // Modal behavior
            modal: {
                ondismiss: function() {
                    alert('Payment was cancelled. You can try again.');
                }
            },
            
            // Enable all payment methods
            config: {
                display: {
                    blocks: {
                        utib: {                             // Name for Net Banking block
                            name: 'Pay using',
                            instruments: [
                                { method: 'upi' },
                                { method: 'card' },
                                { method: 'netbanking' },
                                { method: 'wallet' }
                            ]
                        }
                    },
                    sequence: ['block.utib'],
                    preferences: {
                        show_default_blocks: true
                    }
                }
            }
        };
        
        // Create Razorpay instance and open popup
        const rzp = new Razorpay(options);
        
        // Handle payment failure
        rzp.on('payment.failed', function (response) {
            alert('Payment failed: ' + response.error.description);
        });
        
        rzp.open();
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Network error. Please try again.');
    });
}

/**
 * Verify payment on server
 * 
 * After Razorpay confirms payment, we send the payment details to our server.
 * Server verifies the signature and saves the order.
 */
function verifyPayment(response) {
    const orderNumber = generateOrderNumber();
    
    // Collect shipping data
    const shippingData = {
        name: document.getElementById('name').value,
        email: document.getElementById('email').value,
        mobile: document.getElementById('mobile').value,
        address: document.getElementById('address').value,
        city: document.getElementById('city').value,
        state: document.getElementById('state').value,
        pincode: document.getElementById('pincode').value
    };
    
    fetch('<?php echo SITE_URL; ?>/razorpay-verify.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            razorpay_order_id: response.razorpay_order_id,
            razorpay_payment_id: response.razorpay_payment_id,
            razorpay_signature: response.razorpay_signature,
            order_number: orderNumber,
            shipping: shippingData
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Payment verified - redirect to order page
            window.location.href = data.redirect;
        } else {
            alert('Payment verification failed: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Verification error:', error);
        alert('Payment was processed but verification failed. Please contact support.');
    });
}

// Generate order number client-side for verification
function generateOrderNumber() {
    return 'ORD' + new Date().toISOString().slice(0,10).replace(/-/g,'') + 
           Math.random().toString(36).substring(2, 8).toUpperCase();
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
