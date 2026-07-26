<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

requireUserLogin();

$pageTitle = 'My Returns';
require_once __DIR__ . '/../includes/header.php';

$db = getDB();
$userId = $_SESSION['user_id'];

// Get all return requests for this user
$stmt = $db->prepare("
    SELECT r.*, o.order_number
    FROM returns r
    JOIN orders o ON r.order_id = o.id
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$userId]);
$returns = $stmt->fetchAll();
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0"><i class="bi bi-arrow-return-left"></i> My Returns</h2>
        <a href="<?php echo USER_URL; ?>/orders.php" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-bag-check"></i> My Orders
        </a>
    </div>

    <?php if (count($returns) > 0): ?>

    <!-- Return Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="card bg-warning text-white text-center">
                <div class="card-body py-3">
                    <i class="bi bi-clock-history fs-3"></i>
                    <h4 class="mb-0 mt-1">
                        <?php
                        $count = 0;
                        foreach ($returns as $r) { if (in_array($r['return_status'], ['requested', 'under_review'])) $count++; }
                        echo $count;
                        ?>
                    </h4>
                    <small>Pending</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card bg-success text-white text-center">
                <div class="card-body py-3">
                    <i class="bi bi-check-circle fs-3"></i>
                    <h4 class="mb-0 mt-1">
                        <?php
                        $count = 0;
                        foreach ($returns as $r) { if ($r['return_status'] === 'approved') $count++; }
                        echo $count;
                        ?>
                    </h4>
                    <small>Approved</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card bg-info text-white text-center">
                <div class="card-body py-3">
                    <i class="bi bi-arrow-repeat fs-3"></i>
                    <h4 class="mb-0 mt-1">
                        <?php
                        $count = 0;
                        foreach ($returns as $r) { if (in_array($r['return_status'], ['pickup_scheduled', 'returned'])) $count++; }
                        echo $count;
                        ?>
                    </h4>
                    <small>In Progress</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card bg-primary text-white text-center">
                <div class="card-body py-3">
                    <i class="bi bi-wallet2 fs-3"></i>
                    <h4 class="mb-0 mt-1">
                        <?php
                        $count = 0;
                        foreach ($returns as $r) { if ($r['return_status'] === 'refund_completed') $count++; }
                        echo $count;
                        ?>
                    </h4>
                    <small>Refunded</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Returns Table -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Return ID</th>
                            <th>Order</th>
                            <th>Product</th>
                            <th>Reason</th>
                            <th>Return Status</th>
                            <th>Refund Status</th>
                            <th>Requested On</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($returns as $return): ?>
                        <tr>
                            <td><strong>#RTN<?php echo str_pad($return['id'], 4, '0', STR_PAD_LEFT); ?></strong></td>
                            <td>
                                <a href="<?php echo USER_URL; ?>/order-details.php?id=<?php echo $return['order_id']; ?>">
                                    <?php echo htmlspecialchars($return['order_number']); ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($return['product_title']); ?></td>
                            <td><small><?php echo getReturnReasonLabel($return['return_reason']); ?></small></td>
                            <td>
                                <span class="badge bg-<?php echo getReturnStatusBadge($return['return_status']); ?>">
                                    <?php echo getReturnStatusLabel($return['return_status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($return['return_status'] === 'refund_completed' || in_array($return['refund_status'], ['processing', 'completed'])): ?>
                                <span class="badge bg-<?php echo getRefundStatusBadge($return['refund_status']); ?>">
                                    <?php echo ucfirst($return['refund_status']); ?>
                                </span>
                                <?php else: ?>
                                <span class="text-muted">--</span>
                                <?php endif; ?>
                            </td>
                            <td><small><?php echo date('M d, Y', strtotime($return['created_at'])); ?></small></td>
                            <td>
                                <a href="<?php echo USER_URL; ?>/return-details.php?id=<?php echo $return['id']; ?>" 
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php else: ?>
    <div class="card shadow-sm">
        <div class="card-body text-center py-5">
            <i class="bi bi-arrow-return-left fs-1 text-muted"></i>
            <h5 class="mt-3">No Return Requests</h5>
            <p class="text-muted">You haven't submitted any return requests yet.</p>
            <a href="<?php echo USER_URL; ?>/orders.php" class="btn btn-primary">
                <i class="bi bi-bag-check"></i> View My Orders
            </a>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
