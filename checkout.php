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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process checkout
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $mobile = sanitize($_POST['mobile'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $city = sanitize($_POST['city'] ?? '');
    $state = sanitize($_POST['state'] ?? '');
    $pincode = sanitize($_POST['pincode'] ?? '');
    $paymentMethod = 'COD';

    // Validation
    if (empty($name) || empty($email) || empty($mobile) || empty($address) || empty($city) || empty($state) || empty($pincode)) {
        $error = 'Please fill all required fields';
    } else {
        try {
            $db->beginTransaction();

            // Generate order number
            $orderNumber = generateOrderNumber();

            // Insert order
            $stmt = $db->prepare("
                INSERT INTO orders (
                    user_id, order_number, total_amount, payment_method, 
                    shipping_name, shipping_email, shipping_mobile, shipping_address, 
                    shipping_city, shipping_state, shipping_pincode
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $userId, $orderNumber, $total, $paymentMethod,
                $name, $email, $mobile, $address, $city, $state, $pincode
            ]);

            $orderId = $db->lastInsertId();

            // Insert order items and update stock
            foreach ($cartItems as $item) {
                // Insert order item
                $stmt = $db->prepare("
                    INSERT INTO order_items (order_id, product_id, product_title, product_price, quantity, subtotal)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $itemSubtotal = $item['price'] * $item['quantity'];
                $stmt->execute([
                    $orderId, $item['product_id'], $item['title'], 
                    $item['price'], $item['quantity'], $itemSubtotal
                ]);

                // Update product stock
                $stmt = $db->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                $stmt->execute([$item['quantity'], $item['product_id']]);
            }

            // Clear cart
            $stmt = $db->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$userId]);

            $db->commit();

            setFlashMessage('success', 'Order placed successfully! Order Number: ' . $orderNumber);
            header('Location: ' . USER_URL . '/orders.php');
            exit;

        } catch (Exception $e) {
            $db->rollBack();
            $error = 'Failed to place order. Please try again.';
        }
    }
}

// Now include header after all processing is complete
$pageTitle = 'Checkout';
require_once __DIR__ . '/includes/header.php';
?>

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

                    <form method="POST" action="">
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

                        <div class="card bg-light mb-3">
                            <div class="card-body">
                                <h6>Payment Method</h6>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" id="cod" value="COD" checked>
                                    <label class="form-check-label" for="cod">
                                        Cash on Delivery (COD)
                                    </label>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success w-100">
                            <i class="bi bi-check-circle"></i> Place Order
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
