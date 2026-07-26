<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

requireUserLogin();

$pageTitle = 'Order Details';
require_once __DIR__ . '/../includes/header.php';

$db = getDB();
$userId = $_SESSION['user_id'];
$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get order details
$stmt = $db->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$orderId, $userId]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: ' . USER_URL . '/orders.php');
    exit;
}

// Get order items (order ownership already verified above)
$stmt = $db->prepare("SELECT * FROM order_items WHERE order_id = ? AND order_id IN (SELECT id FROM orders WHERE id = ? AND user_id = ?)");
$stmt->execute([$orderId, $orderId, $userId]);
$orderItems = $stmt->fetchAll();
?>

<div class="container my-5">
    <h2 class="mb-4">Order Details</h2>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Order #<?php echo htmlspecialchars($order['order_number']); ?></h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong>Order Date:</strong> <?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></p>
                            <p><strong>Payment Method:</strong> 
                                <?php if ($order['payment_method'] === 'Razorpay'): ?>
                                    <span class="badge bg-info"><i class="bi bi-credit-card"></i> Razorpay (Online)</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><i class="bi bi-cash"></i> Cash on Delivery</span>
                                <?php endif; ?>
                            </p>
                            <?php if (!empty($order['razorpay_payment_id'])): ?>
                            <p><strong>Payment ID:</strong> <code><?php echo htmlspecialchars($order['razorpay_payment_id']); ?></code></p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Order Status:</strong> 
                                <?php
                                $badgeClass = '';
                                switch ($order['order_status']) {
                                    case 'pending': $badgeClass = 'warning'; break;
                                    case 'processing': $badgeClass = 'info'; break;
                                    case 'completed': $badgeClass = 'success'; break;
                                    case 'cancelled': $badgeClass = 'danger'; break;
                                }
                                ?>
                                <span class="badge bg-<?php echo $badgeClass; ?>">
                                    <?php echo ucfirst($order['order_status']); ?>
                                </span>
                            </p>
                            <p><strong>Payment Status:</strong> 
                                <span class="badge bg-<?php echo $order['payment_status'] == 'paid' ? 'success' : 'warning'; ?>">
                                    <?php echo ucfirst($order['payment_status']); ?>
                                </span>
                            </p>
                        </div>
                    </div>

                    <!-- Estimated Delivery -->
                    <?php if (isOrderDelivered($order)): ?>
                    <div class="delivery-card delivered mb-3">
                        <div class="delivery-icon"><i class="bi bi-check-circle-fill"></i></div>
                        <div class="delivery-text">
                            <div class="small text-muted">Delivery Status</div>
                            <strong>Delivered</strong>
                        </div>
                    </div>
                    <?php else: ?>
                    <?php
                    $estDate = getEstimatedDeliveryDisplay($order['created_at']);
                    $daysLeft = getDaysUntilDelivery($order['created_at']);
                    ?>
                    <div class="delivery-card mb-3">
                        <div class="delivery-icon"><i class="bi bi-truck"></i></div>
                        <div class="delivery-text">
                            <div class="small text-muted">Estimated Delivery</div>
                            <strong><?php echo $estDate; ?> (<?php echo $daysLeft > 0 ? 'Within ' . $daysLeft . ' Day' . ($daysLeft !== 1 ? 's' : '') : 'Delivery Expected Today'; ?>)</strong>
                        </div>
                    </div>
                    <?php endif; ?>

                    <h6>Order Items:</h6>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Subtotal</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orderItems as $item): ?>
                                <?php
                                $returnElig = isReturnEligible($order);
                                $hasReturn = hasActiveReturn($item['id'], $userId);
                                $canReturn = $returnElig['eligible'] && !$hasReturn;
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['product_title']); ?></td>
                                    <td><?php echo formatPrice($item['product_price']); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td><?php echo formatPrice($item['subtotal']); ?></td>
                                    <td>
                                        <?php if ($hasReturn): ?>
                                            <a href="<?php echo USER_URL; ?>/returns.php" class="btn btn-sm btn-outline-info">
                                                <i class="bi bi-arrow-return-left"></i> Return Status
                                            </a>
                                        <?php elseif ($canReturn): ?>
                                            <a href="<?php echo USER_URL; ?>/return-request.php?order_id=<?php echo $orderId; ?>&item_id=<?php echo $item['id']; ?>" 
                                               class="btn btn-sm btn-outline-warning">
                                                <i class="bi bi-arrow-return-left"></i> Return
                                            </a>
                                        <?php else: ?>
                                            <small class="text-muted">
                                                <i class="bi bi-clock-history"></i> <?php echo $returnElig['reason']; ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="4" class="text-end">Total Amount:</th>
                                    <th><?php echo formatPrice($order['total_amount']); ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <?php
                    $returnEligGlobal = isReturnEligible($order);
                    if ($returnEligGlobal['eligible'] && $order['order_status'] === 'completed'):
                    ?>
                    <div class="alert alert-info d-flex align-items-center mt-3">
                        <i class="bi bi-info-circle fs-5 me-2"></i>
                        <div>
                            <strong><?php echo $returnEligGlobal['days_left']; ?> day<?php echo $returnEligGlobal['days_left'] !== 1 ? 's' : ''; ?> remaining</strong> to return products from this order.
                            <div class="progress mt-2" style="height: 4px; max-width: 200px;">
                                <div class="progress-bar bg-<?php echo $returnEligGlobal['days_left'] > 3 ? 'success' : 'warning'; ?>" 
                                     style="width: <?php echo ($returnEligGlobal['days_left'] / 7) * 100; ?>%"></div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Shipping Address</h5>
                </div>
                <div class="card-body">
                    <p><strong><?php echo htmlspecialchars($order['shipping_name']); ?></strong></p>
                    <p><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                    <p><?php echo htmlspecialchars($order['shipping_city']); ?>, <?php echo htmlspecialchars($order['shipping_state']); ?></p>
                    <p>PIN: <?php echo htmlspecialchars($order['shipping_pincode']); ?></p>
                    <hr>
                    <p><i class="bi bi-envelope"></i> <?php echo htmlspecialchars($order['shipping_email']); ?></p>
                    <p><i class="bi bi-telephone"></i> <?php echo htmlspecialchars($order['shipping_mobile']); ?></p>
                </div>
            </div>
        </div>
    </div>

    <a href="<?php echo USER_URL; ?>/orders.php" class="btn btn-primary mt-3">
        <i class="bi bi-arrow-left"></i> Back to Orders
    </a>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
