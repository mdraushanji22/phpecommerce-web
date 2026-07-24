<?php
/**
 * Razorpay Payment Verification
 * 
 * HOW IT WORKS:
 * 1. After user completes payment in Razorpay popup, Razorpay sends payment details
 * 2. Razorpay generates a signature using HMAC SHA256: sha256(order_id + "|" + payment_id, secret)
 * 3. Our server generates the same signature using the same algorithm
 * 4. If both signatures match, payment is authentic
 * 5. We then save the order in our database with payment details
 * 
 * WHY VERIFICATION IS IMPORTANT:
 * - Prevents fake payment confirmations
 * - Ensures the payment was actually processed by Razorpay
 * - The signature cannot be forged without the Key Secret (server-side only)
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Check if user is logged in
if (!isUserLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

// Get JSON input from Razorpay checkout
$input = json_decode(file_get_contents('php://input'), true);

$razorpayOrderId   = $input['razorpay_order_id']   ?? '';
$razorpayPaymentId = $input['razorpay_payment_id'] ?? '';
$razorpaySignature = $input['razorpay_signature']  ?? '';
$orderNumber       = $input['order_number']         ?? '';
$shippingData      = $input['shipping']             ?? [];

// Validate all required fields
if (empty($razorpayOrderId) || empty($razorpayPaymentId) || empty($razorpaySignature) || empty($orderNumber)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing payment verification data']);
    exit;
}

/**
 * Step 1: Verify the payment signature
 * 
 * Razorpay signature = HMAC_SHA256(order_id + "|" + payment_id, key_secret)
 * 
 * If this matches, the payment is genuine and hasn't been tampered with.
 */
$generatedSignature = hash_hmac('sha256', $razorpayOrderId . '|' . $razorpayPaymentId, RAZORPAY_KEY_SECRET);

if ($generatedSignature !== $razorpaySignature) {
    // Signature mismatch - payment data may be tampered
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Payment verification failed. Invalid signature.']);
    exit;
}

/**
 * Step 2: Optionally verify payment status from Razorpay API
 * This is an extra security step to confirm payment was actually captured
 */
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => "https://api.razorpay.com/v1/payments/{$razorpayPaymentId}",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_USERPWD        => RAZORPAY_KEY_ID . ':' . RAZORPAY_KEY_SECRET,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT        => 30
]);

$paymentResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$paymentData = json_decode($paymentResponse, true);

// Check if payment is captured (successful)
if ($httpCode !== 200 || ($paymentData['status'] ?? '') !== 'captured') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Payment was not captured. Status: ' . ($paymentData['status'] ?? 'unknown')]);
    exit;
}

/**
 * Step 3: Save the order in database
 * 
 * Now that payment is verified, we create the order record with:
 * - payment_method = 'Razorpay'
 * - payment_status = 'paid'
 * - razorpay_order_id = the Razorpay order ID
 * - razorpay_payment_id = the Razorpay payment ID
 */
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

if (empty($cartItems)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Cart is empty']);
    exit;
}

// Calculate totals
$subtotal = 0;
foreach ($cartItems as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$shipping = $subtotal > 500 ? 0 : 50;
$total = $subtotal + $shipping;

// Use shipping data from the request, or get from user profile
$shippingName    = $shippingData['name']    ?? '';
$shippingEmail   = $shippingData['email']   ?? '';
$shippingMobile  = $shippingData['mobile']  ?? '';
$shippingAddress = $shippingData['address'] ?? '';
$shippingCity    = $shippingData['city']    ?? '';
$shippingState   = $shippingData['state']   ?? '';
$shippingPincode = $shippingData['pincode'] ?? '';

// Fallback: get from user if shipping data is missing
if (empty($shippingName)) {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    $shippingName    = $user['name'];
    $shippingEmail   = $user['email'];
    $shippingMobile  = $user['mobile'];
    $shippingAddress = $user['address'] ?? '';
    $shippingCity    = $user['city'] ?? '';
    $shippingState   = $user['state'] ?? '';
    $shippingPincode = $user['pincode'] ?? '';
}

try {
    $db->beginTransaction();

    // Validate stock one more time
    foreach ($cartItems as $item) {
        $stockCheck = $db->prepare("SELECT stock FROM products WHERE id = ? AND status = 'active' FOR UPDATE");
        $stockCheck->execute([$item['product_id']]);
        $currentStock = $stockCheck->fetchColumn();
        
        if ($currentStock === false || $currentStock < $item['quantity']) {
            $db->rollBack();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Insufficient stock for "' . htmlspecialchars($item['title']) . '"']);
            exit;
        }
    }

    // Insert order with Razorpay details
    $stmt = $db->prepare("
        INSERT INTO orders (
            user_id, order_number, total_amount, payment_method, 
            razorpay_order_id, razorpay_payment_id, payment_status,
            shipping_name, shipping_email, shipping_mobile, shipping_address, 
            shipping_city, shipping_state, shipping_pincode
        ) VALUES (?, ?, ?, 'Razorpay', ?, ?, 'paid', ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $userId, $orderNumber, $total,
        $razorpayOrderId, $razorpayPaymentId,
        $shippingName, $shippingEmail, $shippingMobile, $shippingAddress,
        $shippingCity, $shippingState, $shippingPincode
    ]);

    $orderId = $db->lastInsertId();

    // Insert order items and update stock
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

        // Decrement stock
        $stmt = $db->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
        $stmt->execute([$item['quantity'], $item['product_id']]);
    }

    // Clear cart
    $stmt = $db->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->execute([$userId]);

    $db->commit();

    echo json_encode([
        'success'      => true,
        'message'      => 'Payment verified and order placed successfully!',
        'order_number' => $orderNumber,
        'redirect'     => USER_URL . '/order-details.php?id=' . $orderId
    ]);

} catch (Exception $e) {
    $db->rollBack();
    error_log("Razorpay order save failed: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to save order. Please contact support.']);
}
