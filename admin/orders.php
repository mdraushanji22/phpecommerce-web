<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdminLogin();

$pageTitle = 'Manage Orders';
require_once __DIR__ . '/../includes/admin_header.php';

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

// Search & filter
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$filterStatus = isset($_GET['status']) ? sanitize($_GET['status']) : '';

$where = [];
$params = [];

if (!empty($filterStatus)) {
    $where[] = "o.order_status = ?";
    $params[] = $filterStatus;
}

if (!empty($search)) {
    $where[] = "(o.order_number LIKE ? OR u.name LIKE ? OR u.email LIKE ? OR o.id LIKE ?)";
    $searchTerm = "%{$search}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$whereClause = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $db->prepare("
    SELECT o.*, u.name as user_name, u.email as user_email
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    {$whereClause}
    ORDER BY o.created_at DESC
");
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Status counts for filter cards
$stats = $db->query("SELECT order_status, COUNT(*) as cnt FROM orders GROUP BY order_status")->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<div class="container-fluid my-4">
    <h2 class="mb-4"><i class="bi bi-cart-check"></i> Manage Orders</h2>

    <!-- Status Filter Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-2 col-4">
            <a href="<?php echo ADMIN_URL; ?>/orders.php" class="text-decoration-none">
                <div class="card text-center border-primary <?php echo empty($filterStatus) ? 'border-3' : ''; ?>">
                    <div class="card-body py-3">
                        <h4 class="text-primary mb-0"><?php echo array_sum($stats); ?></h4>
                        <small class="text-muted">All</small>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-2 col-4">
            <a href="<?php echo ADMIN_URL; ?>/orders.php?status=pending" class="text-decoration-none">
                <div class="card text-center border-warning <?php echo $filterStatus === 'pending' ? 'border-3' : ''; ?>">
                    <div class="card-body py-3">
                        <h4 class="text-warning mb-0"><?php echo $stats['pending'] ?? 0; ?></h4>
                        <small class="text-muted">Pending</small>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-2 col-4">
            <a href="<?php echo ADMIN_URL; ?>/orders.php?status=processing" class="text-decoration-none">
                <div class="card text-center border-info <?php echo $filterStatus === 'processing' ? 'border-3' : ''; ?>">
                    <div class="card-body py-3">
                        <h4 class="text-info mb-0"><?php echo $stats['processing'] ?? 0; ?></h4>
                        <small class="text-muted">Processing</small>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-2 col-4">
            <a href="<?php echo ADMIN_URL; ?>/orders.php?status=completed" class="text-decoration-none">
                <div class="card text-center border-success <?php echo $filterStatus === 'completed' ? 'border-3' : ''; ?>">
                    <div class="card-body py-3">
                        <h4 class="text-success mb-0"><?php echo $stats['completed'] ?? 0; ?></h4>
                        <small class="text-muted">Completed</small>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-2 col-4">
            <a href="<?php echo ADMIN_URL; ?>/orders.php?status=cancelled" class="text-decoration-none">
                <div class="card text-center border-danger <?php echo $filterStatus === 'cancelled' ? 'border-3' : ''; ?>">
                    <div class="card-body py-3">
                        <h4 class="text-danger mb-0"><?php echo $stats['cancelled'] ?? 0; ?></h4>
                        <small class="text-muted">Cancelled</small>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- Search Bar -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-2">
                <?php if (!empty($filterStatus)): ?>
                <input type="hidden" name="status" value="<?php echo htmlspecialchars($filterStatus); ?>">
                <?php endif; ?>
                <div class="col-md-10">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" name="search" 
                               placeholder="Search by order number, customer name or email..."
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Search</button>
                </div>
            </form>
            <?php if (!empty($search) || !empty($filterStatus)): ?>
            <a href="<?php echo ADMIN_URL; ?>/orders.php" class="btn btn-outline-secondary btn-sm mt-2">
                <i class="bi bi-x-circle"></i> Clear Filters
            </a>
            <?php endif; ?>
        </div>
    </div>

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
