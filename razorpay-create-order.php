<?php
/**
 * Razorpay Create Order API
 * 
 * HOW IT WORKS:
 * 1. Frontend JavaScript sends cart total amount to this file via AJAX
 * 2. This file calls Razorpay API to create an order
 * 3. Returns the Razorpay order_id to frontend
 * 4. Frontend uses this order_id to open the Razorpay payment popup
 * 
 * RAZORPAY FLOW:
 * User clicks "Pay" -> JS calls this file -> This file calls Razorpay API 
 * -> Returns order_id -> JS opens Razorpay popup -> User pays -> 
 * JS gets payment details -> JS calls verify.php -> Payment confirmed
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

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit;
}

if (!isset($input['amount']) || !is_numeric($input['amount']) || $input['amount'] <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid amount']);
    exit;
}

// Amount in paise (Razorpay expects amount in smallest currency unit)
// ₹1 = 100 paise. So ₹500 = 50000 paise
$amountInPaise = (int)($input['amount'] * 100);

// Generate a unique receipt ID for tracking
$receiptId = 'order_' . $_SESSION['user_id'] . '_' . time();

/**
 * Call Razorpay API to create an order
 * API Endpoint: https://api.razorpay.com/v1/orders
 * 
 * We use cURL to make the API call with our Key ID and Key Secret
 * for HTTP Basic Authentication
 */
$postData = json_encode([
    'amount'    => $amountInPaise,    // Amount in paise
    'currency'  => 'INR',              // Currency code
    'receipt'   => $receiptId,         // Unique receipt for tracking
    'payment_capture' => 1             // Auto-capture payment (1=yes, 0=no)
]);

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => 'https://api.razorpay.com/v1/orders',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $postData,
    CURLOPT_USERPWD        => RAZORPAY_KEY_ID . ':' . RAZORPAY_KEY_SECRET,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($postData)
    ],
    CURLOPT_SSL_VERIFYPEER => true,    // Verify SSL certificate
    CURLOPT_TIMEOUT        => 30       // 30 second timeout
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// Handle cURL errors
if ($curlError) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Network error: ' . $curlError]);
    exit;
}

// Parse Razorpay response
$responseData = json_decode($response, true);

if ($httpCode !== 200 || !isset($responseData['id'])) {
    $errorMsg = $responseData['error']['description'] ?? 'Failed to create Razorpay order';
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $errorMsg]);
    exit;
}

/**
 * Return the order details to frontend
 * 
 * The frontend will use:
 * - id: The Razorpay order_id (starts with "order_")
 * - amount: The amount in paise
 * - currency: INR
 * - key_id: Your Razorpay Key ID (needed for the popup)
 */
echo json_encode([
    'success'    => true,
    'order_id'   => $responseData['id'],          // Razorpay order ID
    'amount'     => $responseData['amount'],       // Amount in paise
    'currency'   => $responseData['currency'],     // INR
    'key_id'     => RAZORPAY_KEY_ID               // Your public Key ID
]);
