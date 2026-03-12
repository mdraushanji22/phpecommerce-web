<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/../includes/header.php';

requireUserLogin();

$db = getDB();
$userId = $_SESSION['user_id'];

// Get user stats
$stmt = $db->prepare("SELECT COUNT(*) as total_orders FROM orders WHERE user_id = ?");
$stmt->execute([$userId]);
$totalOrders = $stmt->fetch()['total_orders'];

$stmt = $db->prepare("SELECT COUNT(*) as pending FROM orders WHERE user_id = ? AND order_status = 'pending'");
$stmt->execute([$userId]);
$pendingOrders = $stmt->fetch()['pending'];

$stmt = $db->prepare("SELECT COUNT(*) as processing FROM orders WHERE user_id = ? AND order_status = 'processing'");
$stmt->execute([$userId]);
$processingOrders = $stmt->fetch()['processing'];

$stmt = $db->prepare("SELECT COUNT(*) as completed FROM orders WHERE user_id = ? AND order_status = 'completed'");
$stmt->execute([$userId]);
$completedOrders = $stmt->fetch()['completed'];

// Get recent orders
$stmt = $db->prepare("
    SELECT * FROM orders 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
");
$stmt->execute([$userId]);
$recentOrders = $stmt->fetchAll();
?>

<div class="container my-5">
    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="text-center mb-3">
                        <i class="bi bi-person-circle fs-1 text-primary"></i>
                        <h5 class="mt-2"><?php echo htmlspecialchars($_SESSION['user_name']); ?></h5>
                        <p class="text-muted"><?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
                    </div>
                    <div class="list-group">
                        <a href="<?php echo USER_URL; ?>/dashboard.php" class="list-group-item list-group-item-action active">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                        <a href="<?php echo USER_URL; ?>/orders.php" class="list-group-item list-group-item-action">
                            <i class="bi bi-bag-check"></i> My Orders
                        </a>
                        <a href="<?php echo USER_URL; ?>/profile.php" class="list-group-item list-group-item-action">
                            <i class="bi bi-person"></i> Profile
                        </a>
                        <a href="<?php echo SITE_URL; ?>/logout.php" class="list-group-item list-group-item-action text-danger">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <h2 class="mb-4">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h2>

            <!-- Statistics Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body text-center">
                            <i class="bi bi-bag fs-1"></i>
                            <h3><?php echo $totalOrders; ?></h3>
                            <p class="mb-0">Total Orders</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body text-center">
                            <i class="bi bi-clock fs-1"></i>
                            <h3><?php echo $pendingOrders; ?></h3>
                            <p class="mb-0">Pending</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body text-center">
                            <i class="bi bi-arrow-repeat fs-1"></i>
                            <h3><?php echo $processingOrders; ?></h3>
                            <p class="mb-0">Processing</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center">
                            <i class="bi bi-check-circle fs-1"></i>
                            <h3><?php echo $completedOrders; ?></h3>
                            <p class="mb-0">Completed</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Orders -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Recent Orders</h5>
                </div>
                <div class="card-body">
                    <?php if (count($recentOrders) > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Order Number</th>
                                    <th>Date</th>
                                    <th>Total Amount</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentOrders as $order): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                    <td><?php echo formatPrice($order['total_amount']); ?></td>
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
                                           class="btn btn-sm btn-primary">View</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <a href="<?php echo USER_URL; ?>/orders.php" class="btn btn-primary">View All Orders</a>
                    <?php else: ?>
                    <p class="text-muted text-center my-4">No orders yet. Start shopping!</p>
                    <div class="text-center">
                        <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-primary">Browse Products</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
