<?php
$pageTitle = 'My Orders';
require_once __DIR__ . '/../includes/header.php';

requireUserLogin();

$db = getDB();
$userId = $_SESSION['user_id'];

// Get all orders
$stmt = $db->prepare("
    SELECT * FROM orders 
    WHERE user_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$userId]);
$orders = $stmt->fetchAll();
?>

<div class="container my-5">
    <h2 class="mb-4"><i class="bi bi-bag-check"></i> My Orders</h2>

    <?php if (count($orders) > 0): ?>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Order Number</th>
                    <th>Date</th>
                    <th>Total Amount</th>
                    <th>Payment Method</th>
                    <th>Order Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                    <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                    <td><?php echo formatPrice($order['total_amount']); ?></td>
                    <td><?php echo htmlspecialchars($order['payment_method']); ?></td>
                    <td>
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
                    </td>
                    <td>
                        <a href="<?php echo USER_URL; ?>/order-details.php?id=<?php echo $order['id']; ?>" 
                           class="btn btn-sm btn-primary">View Details</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="alert alert-info text-center">
        <i class="bi bi-info-circle fs-1"></i>
        <p class="mt-3">No orders found.</p>
        <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-primary">Start Shopping</a>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
