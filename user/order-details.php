<?php
$pageTitle = 'Order Details';
require_once __DIR__ . '/../includes/header.php';

requireUserLogin();

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

// Get order items
$stmt = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
$stmt->execute([$orderId]);
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
                            <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></p>
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

                    <h6>Order Items:</h6>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orderItems as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['product_title']); ?></td>
                                    <td><?php echo formatPrice($item['product_price']); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td><?php echo formatPrice($item['subtotal']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3" class="text-end">Total Amount:</th>
                                    <th><?php echo formatPrice($order['total_amount']); ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
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
