<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdminLogin();

$pageTitle = 'Manage Returns';
require_once __DIR__ . '/../includes/admin_header.php';

$db = getDB();

// Handle status update POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_return'])) {
    $returnId = (int)$_POST['return_id'];
    $newStatus = sanitize($_POST['new_status']);
    $adminRemarks = trim($_POST['admin_remarks'] ?? '');
    $refundAmount = $_POST['refund_amount'] ?? null;
    $pickupDate = $_POST['pickup_date'] ?? null;

    $validStatuses = ['requested', 'under_review', 'approved', 'rejected', 'pickup_scheduled', 'returned', 'refund_completed'];
    if (!in_array($newStatus, $validStatuses)) {
        setFlashMessage('danger', 'Invalid status.');
        header('Location: ' . ADMIN_URL . '/returns.php');
        exit;
    }

    // Get current return
    $stmt = $db->prepare("SELECT * FROM returns WHERE id = ?");
    $stmt->execute([$returnId]);
    $currentReturn = $stmt->fetch();

    if (!$currentReturn) {
        setFlashMessage('danger', 'Return not found.');
        header('Location: ' . ADMIN_URL . '/returns.php');
        exit;
    }

    $oldStatus = $currentReturn['return_status'];

    // Build update query dynamically
    $updates = ['return_status = ?', 'updated_at = NOW()'];
    $params = [$newStatus];

    if (!empty($adminRemarks)) {
        $updates[] = 'admin_remarks = ?';
        $params[] = $adminRemarks;
    }

    if ($newStatus === 'refund_completed' && $refundAmount !== null && $refundAmount !== '') {
        $updates[] = 'refund_status = ?';
        $updates[] = 'refund_amount = ?';
        $params[] = 'completed';
        $params[] = (float)$refundAmount;
    } elseif ($newStatus === 'approved') {
        $updates[] = 'refund_status = ?';
        $params[] = 'pending';
    } elseif ($newStatus === 'pickup_scheduled' && !empty($pickupDate)) {
        $updates[] = 'pickup_date = ?';
        $params[] = $pickupDate;
    }

    $params[] = $returnId;
    $sql = "UPDATE returns SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    // Log status change
    $remark = !empty($adminRemarks) ? $adminRemarks : null;
    logReturnStatus($returnId, $oldStatus, $newStatus, $remark, $_SESSION['admin_id'], 'admin');

    setFlashMessage('success', 'Return status updated successfully.');
    header('Location: ' . ADMIN_URL . '/returns.php');
    exit;
}

// Filter
$filterStatus = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Build query
$where = [];
$params = [];

if (!empty($filterStatus)) {
    $where[] = "r.return_status = ?";
    $params[] = $filterStatus;
}

if (!empty($search)) {
    $where[] = "(r.product_title LIKE ? OR r.id LIKE ? OR o.order_number LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
    $searchTerm = "%{$search}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$whereClause = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

// Get returns
$stmt = $db->prepare("
    SELECT r.*, o.order_number, u.name as user_name, u.email as user_email
    FROM returns r
    JOIN orders o ON r.order_id = o.id
    JOIN users u ON r.user_id = u.id
    {$whereClause}
    ORDER BY r.created_at DESC
");
$stmt->execute($params);
$returns = $stmt->fetchAll();

// Get stats
$stats = $db->query("SELECT return_status, COUNT(*) as cnt FROM returns GROUP BY return_status")->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<div class="container-fluid my-4">
    <h2 class="mb-4"><i class="bi bi-arrow-return-left"></i> Manage Returns</h2>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-2 col-4">
            <a href="<?php echo ADMIN_URL; ?>/returns.php" class="text-decoration-none">
                <div class="card text-center border-primary <?php echo empty($filterStatus) ? 'border-3' : ''; ?>">
                    <div class="card-body py-3">
                        <h4 class="text-primary mb-0"><?php echo array_sum($stats); ?></h4>
                        <small class="text-muted">Total</small>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-2 col-4">
            <a href="<?php echo ADMIN_URL; ?>/returns.php?status=requested" class="text-decoration-none">
                <div class="card text-center border-warning <?php echo $filterStatus === 'requested' ? 'border-3' : ''; ?>">
                    <div class="card-body py-3">
                        <h4 class="text-warning mb-0"><?php echo $stats['requested'] ?? 0; ?></h4>
                        <small class="text-muted">Requested</small>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-2 col-4">
            <a href="<?php echo ADMIN_URL; ?>/returns.php?status=under_review" class="text-decoration-none">
                <div class="card text-center border-info <?php echo $filterStatus === 'under_review' ? 'border-3' : ''; ?>">
                    <div class="card-body py-3">
                        <h4 class="text-info mb-0"><?php echo $stats['under_review'] ?? 0; ?></h4>
                        <small class="text-muted">Under Review</small>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-2 col-4">
            <a href="<?php echo ADMIN_URL; ?>/returns.php?status=approved" class="text-decoration-none">
                <div class="card text-center border-success <?php echo $filterStatus === 'approved' ? 'border-3' : ''; ?>">
                    <div class="card-body py-3">
                        <h4 class="text-success mb-0"><?php echo $stats['approved'] ?? 0; ?></h4>
                        <small class="text-muted">Approved</small>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-2 col-4">
            <a href="<?php echo ADMIN_URL; ?>/returns.php?status=rejected" class="text-decoration-none">
                <div class="card text-center border-danger <?php echo $filterStatus === 'rejected' ? 'border-3' : ''; ?>">
                    <div class="card-body py-3">
                        <h4 class="text-danger mb-0"><?php echo $stats['rejected'] ?? 0; ?></h4>
                        <small class="text-muted">Rejected</small>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-2 col-4">
            <a href="<?php echo ADMIN_URL; ?>/returns.php?status=refund_completed" class="text-decoration-none">
                <div class="card text-center border-success <?php echo $filterStatus === 'refund_completed' ? 'border-3' : ''; ?>">
                    <div class="card-body py-3">
                        <h4 class="text-success mb-0"><?php echo $stats['refund_completed'] ?? 0; ?></h4>
                        <small class="text-muted">Refunded</small>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- Search -->
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
                               placeholder="Search by product, return ID, order number, customer name or email..."
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Search</button>
                </div>
            </form>
            <?php if (!empty($search) || !empty($filterStatus)): ?>
            <a href="<?php echo ADMIN_URL; ?>/returns.php" class="btn btn-outline-secondary btn-sm mt-2">
                <i class="bi bi-x-circle"></i> Clear Filters
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Returns Table -->
    <div class="card">
        <div class="card-body">
            <?php if (count($returns) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Return ID</th>
                            <th>Customer</th>
                            <th>Order #</th>
                            <th>Product</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Refund</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($returns as $return): ?>
                        <tr>
                            <td><strong>#RTN<?php echo str_pad($return['id'], 4, '0', STR_PAD_LEFT); ?></strong></td>
                            <td>
                                <?php echo htmlspecialchars($return['user_name']); ?><br>
                                <small class="text-muted"><?php echo htmlspecialchars($return['user_email']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($return['order_number']); ?></td>
                            <td><small><?php echo htmlspecialchars($return['product_title']); ?></small></td>
                            <td><small><?php echo getReturnReasonLabel($return['return_reason']); ?></small></td>
                            <td>
                                <span class="badge bg-<?php echo getReturnStatusBadge($return['return_status']); ?>">
                                    <?php echo getReturnStatusLabel($return['return_status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if (in_array($return['refund_status'], ['pending', 'processing', 'completed'])): ?>
                                <span class="badge bg-<?php echo getRefundStatusBadge($return['refund_status']); ?>">
                                    <?php echo ucfirst($return['refund_status']); ?>
                                </span>
                                <?php else: ?>
                                --
                                <?php endif; ?>
                            </td>
                            <td><small><?php echo date('M d, Y', strtotime($return['created_at'])); ?></small></td>
                            <td>
                                <a href="<?php echo ADMIN_URL; ?>/return-details.php?id=<?php echo $return['id']; ?>" 
                                   class="btn btn-sm btn-primary">
                                    <i class="bi bi-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox fs-1 text-muted"></i>
                <p class="mt-3 text-muted">No return requests found.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
