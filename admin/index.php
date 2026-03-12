<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/../includes/admin_header.php';

requireAdminLogin();

$db = getDB();

// Get statistics
$stmt = $db->query("SELECT COUNT(*) as total FROM products");
$totalProducts = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM users");
$totalUsers = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM orders");
$totalOrders = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM categories");
$totalCategories = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE order_status = 'completed'");
$totalRevenue = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM orders WHERE order_status = 'pending'");
$pendingOrders = $stmt->fetch()['total'];

// Get recent orders
$stmt = $db->query("
    SELECT o.*, u.name as user_name
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
    LIMIT 10
");
$recentOrders = $stmt->fetchAll();
?>

<div class="container-fluid my-4">
    <h2 class="mb-4">Dashboard</h2>

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <i class="bi bi-box-seam fs-1"></i>
                    <h3><?php echo $totalProducts; ?></h3>
                    <p class="mb-0">Total Products</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <i class="bi bi-people fs-1"></i>
                    <h3><?php echo $totalUsers; ?></h3>
                    <p class="mb-0">Total Users</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <i class="bi bi-cart-check fs-1"></i>
                    <h3><?php echo $totalOrders; ?></h3>
                    <p class="mb-0">Total Orders</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <i class="bi bi-currency-rupee fs-1"></i>
                    <h3><?php echo formatPrice($totalRevenue); ?></h3>
                    <p class="mb-0">Total Revenue</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body text-center">
                    <i class="bi bi-clock-history fs-1 text-warning"></i>
                    <h3><?php echo $pendingOrders; ?></h3>
                    <p class="mb-0">Pending Orders</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-primary">
                <div class="card-body text-center">
                    <i class="bi bi-tags fs-1 text-primary"></i>
                    <h3><?php echo $totalCategories; ?></h3>
                    <p class="mb-0">Categories</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="card">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">Recent Orders</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOrders as $order): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                            <td><?php echo htmlspecialchars($order['user_name']); ?></td>
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
