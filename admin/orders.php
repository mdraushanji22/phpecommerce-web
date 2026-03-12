<?php
$pageTitle = 'Manage Orders';
require_once __DIR__ . '/../includes/admin_header.php';

requireAdminLogin();

$db = getDB();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $orderId = (int)$_POST['order_id'];
    $status = sanitize($_POST['order_status']);
    
    $stmt = $db->prepare("UPDATE orders SET order_status = ? WHERE id = ?");
    $stmt->execute([$status, $orderId]);
    setFlashMessage('success', 'Order status updated');
    header('Location: ' . ADMIN_URL . '/orders.php');
    exit;
}

// Get all orders
$orders = $db->query("
    SELECT o.*, u.name as user_name, u.email as user_email
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
")->fetchAll();
?>

<div class="container-fluid my-4">
    <h2 class="mb-4">Manage Orders</h2>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                            <td>
                                <?php echo htmlspecialchars($order['user_name']); ?><br>
                                <small class="text-muted"><?php echo htmlspecialchars($order['user_email']); ?></small>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                            <td><?php echo formatPrice($order['total_amount']); ?></td>
                            <td><?php echo htmlspecialchars($order['payment_method']); ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                    <select name="order_status" class="form-select form-select-sm" style="width: auto; display: inline-block;" 
                                            onchange="this.form.submit();">
                                        <option value="pending" <?php echo $order['order_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="processing" <?php echo $order['order_status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                        <option value="completed" <?php echo $order['order_status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        <option value="cancelled" <?php echo $order['order_status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                    <input type="hidden" name="update_status" value="1">
                                </form>
                            </td>
                            <td>
                                <a href="<?php echo ADMIN_URL; ?>/order-details.php?id=<?php echo $order['id']; ?>" 
                                   class="btn btn-sm btn-primary">View</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
