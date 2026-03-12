<?php
$pageTitle = 'Order Details';
require_once __DIR__ . '/../includes/admin_header.php';

requireAdminLogin();

$db = getDB();
$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get order
$stmt = $db->prepare("
    SELECT o.*, u.name as user_name, u.email as user_email
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    WHERE o.id = ?
");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: ' . ADMIN_URL . '/orders.php');
    exit;
}

// Get order items
$stmt = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
$stmt->execute([$orderId]);
$items = $stmt->fetchAll();
?>

<div class="container-fluid my-4">
    <h2 class="mb-4">Order Details - <?php echo htmlspecialchars($order['order_number']); ?></h2>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-3">
                <div class="card-header">
                    <h5>Order Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Order Date:</strong> <?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></p>
                            <p><strong>Customer:</strong> <?php echo htmlspecialchars($order['user_name']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($order['user_email']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></p>
                            <p><strong>Payment Status:</strong> 
                                <span class="badge bg-<?php echo $order['payment_status'] === 'paid' ? 'success' : 'warning'; ?>">
                                    <?php echo ucfirst($order['payment_status']); ?>
                                </span>
                            </p>
                            <p><strong>Order Status:</strong> 
                                <span class="badge bg-<?php
                                    $badges = ['pending' => 'warning', 'processing' => 'info', 'completed' => 'success', 'cancelled' => 'danger'];
                                    echo $badges[$order['order_status']];
                                ?>">
                                    <?php echo ucfirst($order['order_status']); ?>
                                </span>
                            </p>
                        </div>
                    </div>

                    <h6 class="mt-4">Order Items</h6>
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
                            <?php foreach ($items as $item): ?>
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
                                <th colspan="3" class="text-end">Total:</th>
                                <th><?php echo formatPrice($order['total_amount']); ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5>Shipping Address</h5>
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

    <a href="<?php echo ADMIN_URL; ?>/orders.php" class="btn btn-secondary mt-3">Back to Orders</a>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
