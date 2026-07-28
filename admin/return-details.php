<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdminLogin();

$db = getDB();
$returnId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get return details
$stmt = $db->prepare("
    SELECT r.*, o.order_number, u.name as user_name, u.email as user_email, u.mobile as user_mobile
    FROM returns r
    JOIN orders o ON r.order_id = o.id
    JOIN users u ON r.user_id = u.id
    WHERE r.id = ?
");
$stmt->execute([$returnId]);
$return = $stmt->fetch();

if (!$return) {
    setFlashMessage('danger', 'Return not found.');
    header('Location: ' . ADMIN_URL . '/returns.php');
    exit;
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_return'])) {
    $newStatus = sanitize($_POST['new_status']);
    $adminRemarks = trim($_POST['admin_remarks'] ?? '');
    $refundAmount = $_POST['refund_amount'] ?? null;
    $pickupDate = $_POST['pickup_date'] ?? null;

    $validStatuses = ['requested', 'under_review', 'approved', 'rejected', 'pickup_scheduled', 'returned', 'refund_completed'];
    if (!in_array($newStatus, $validStatuses)) {
        setFlashMessage('danger', 'Invalid status.');
        header('Location: ' . ADMIN_URL . '/return-details.php?id=' . $returnId);
        exit;
    }

    $oldStatus = $return['return_status'];

    // Build update
    $updates = ['return_status = ?', 'updated_at = NOW()'];
    $params = [$newStatus];

    if (!empty($adminRemarks)) {
        $updates[] = 'admin_remarks = ?';
        $params[] = $adminRemarks;
    } else {
        $updates[] = 'admin_remarks = ?';
        $params[] = null;
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

    // Reload data
    setFlashMessage('success', 'Return status updated successfully.');
    header('Location: ' . ADMIN_URL . '/return-details.php?id=' . $returnId);
    exit;
}

// Get images
$returnImages = getReturnImages($returnId);

// Get status history
$statusHistory = getReturnStatusHistory($returnId);

// Get order item details
$stmt = $db->prepare("SELECT * FROM order_items WHERE id = ?");
$stmt->execute([$return['order_item_id']]);
$orderItem = $stmt->fetch();

// Get order
$stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$return['order_id']]);
$order = $stmt->fetch();

$pageTitle = 'Return Details';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="container-fluid my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">
            <i class="bi bi-arrow-return-left"></i> 
            Return #RTN<?php echo str_pad($return['id'], 4, '0', STR_PAD_LEFT); ?>
        </h2>
        <a href="<?php echo ADMIN_URL; ?>/returns.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Returns
        </a>
    </div>

    <div class="row">
        <!-- Left Column: Return Info -->
        <div class="col-lg-8">
            <!-- Return Details Card -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Return Details</h5>
                    <span class="badge bg-<?php echo getReturnStatusBadge($return['return_status']); ?> fs-6">
                        <?php echo getReturnStatusLabel($return['return_status']); ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Return ID:</strong> #RTN<?php echo str_pad($return['id'], 4, '0', STR_PAD_LEFT); ?></p>
                            <p><strong>Product:</strong> <?php echo htmlspecialchars($return['product_title']); ?></p>
                            <p><strong>Return Reason:</strong> <?php echo getReturnReasonLabel($return['return_reason']); ?></p>
                            <?php if ($orderItem): ?>
                            <p><strong>Quantity:</strong> <?php echo $orderItem['quantity']; ?></p>
                            <p><strong>Price:</strong> <?php echo formatPrice($orderItem['product_price']); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Order Number:</strong> 
                                <a href="<?php echo ADMIN_URL; ?>/order-details.php?id=<?php echo $return['order_id']; ?>">
                                    <?php echo htmlspecialchars($return['order_number']); ?>
                                </a>
                            </p>
                            <p><strong>Requested On:</strong> <?php echo date('M d, Y h:i A', strtotime($return['created_at'])); ?></p>
                            <p><strong>Last Updated:</strong> <?php echo date('M d, Y h:i A', strtotime($return['updated_at'])); ?></p>
                            <?php if ($return['pickup_date']): ?>
                            <p><strong>Pickup Date:</strong> <?php echo date('M d, Y', strtotime($return['pickup_date'])); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <hr>
                    <h6>Description:</h6>
                    <p class="text-muted"><?php echo nl2br(htmlspecialchars($return['return_description'])); ?></p>
                </div>
            </div>

            <!-- Uploaded Images -->
            <?php if (count($returnImages) > 0): ?>
            <div class="card mb-4 shadow-sm">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-images"></i> Uploaded Images</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-3">
                        <?php foreach ($returnImages as $img): ?>
                        <a href="<?php echo SITE_URL; ?>/uploads/returns/<?php echo htmlspecialchars($img['image_name']); ?>" target="_blank">
                            <img src="<?php echo SITE_URL; ?>/uploads/returns/<?php echo htmlspecialchars($img['image_name']); ?>" 
                                 alt="Return Image" class="rounded border" 
                                 style="width: 150px; height: 150px; object-fit: cover;">
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Status Timeline -->
            <?php if (count($statusHistory) > 0): ?>
            <div class="card mb-4 shadow-sm">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-clock-history"></i> Status History</h6>
                </div>
                <div class="card-body">
                    <?php foreach (array_reverse($statusHistory) as $history): ?>
                    <div class="d-flex mb-3">
                        <div class="me-3">
                            <div class="timeline-dot bg-<?php echo getReturnStatusBadge($history['new_status']); ?>"></div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-bold"><?php echo getReturnStatusLabel($history['new_status']); ?></div>
                            <small class="text-muted">
                                <?php echo date('M d, Y h:i A', strtotime($history['created_at'])); ?>
                                &middot; by <?php echo $history['changed_by_type'] === 'admin' ? 'Admin' : 'Customer'; ?>
                            </small>
                            <?php if (!empty($history['remark'])): ?>
                            <div class="text-muted small mt-1"><?php echo nl2br(htmlspecialchars($history['remark'])); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Right Column: Customer Info + Update Form -->
        <div class="col-lg-4">
            <!-- Customer Info -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-person"></i> Customer Info</h6>
                </div>
                <div class="card-body">
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($return['user_name']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($return['user_email']); ?></p>
                    <?php if ($return['user_mobile']): ?>
                    <p><strong>Mobile:</strong> <?php echo htmlspecialchars($return['user_mobile']); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Order Info -->
            <?php if ($order): ?>
            <div class="card mb-4 shadow-sm">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-bag"></i> Order Info</h6>
                </div>
                <div class="card-body">
                    <p><strong>Order #:</strong> <?php echo htmlspecialchars($order['order_number']); ?></p>
                    <p><strong>Date:</strong> <?php echo date('M d, Y', strtotime($order['created_at'])); ?></p>
                    <p><strong>Status:</strong> 
                        <span class="badge bg-<?php echo $order['order_status'] === 'completed' ? 'success' : 'warning'; ?>">
                            <?php echo ucfirst($order['order_status']); ?>
                        </span>
                    </p>
                    <p><strong>Amount:</strong> <?php echo formatPrice($order['total_amount']); ?></p>
                    <p><strong>Payment:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Update Status Form -->
            <div class="card mb-4 shadow-sm border-primary">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="bi bi-pencil-square"></i> Update Return Status</h6>
                </div>
                <div class="card-body">
                    <form method="POST" id="updateReturnForm">
                        <input type="hidden" name="update_return" value="1">

                        <div class="mb-3">
                            <label class="form-label fw-bold">New Status *</label>
                            <select class="form-select" name="new_status" id="newStatus" required>
                                <option value="requested" <?php echo $return['return_status'] === 'requested' ? 'selected' : ''; ?>>Return Requested</option>
                                <option value="under_review" <?php echo $return['return_status'] === 'under_review' ? 'selected' : ''; ?>>Under Review</option>
                                <option value="approved" <?php echo $return['return_status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                <option value="rejected" <?php echo $return['return_status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                <option value="pickup_scheduled" <?php echo $return['return_status'] === 'pickup_scheduled' ? 'selected' : ''; ?>>Pickup Scheduled</option>
                                <option value="returned" <?php echo $return['return_status'] === 'returned' ? 'selected' : ''; ?>>Returned</option>
                                <option value="refund_completed" <?php echo $return['return_status'] === 'refund_completed' ? 'selected' : ''; ?>>Refund Completed</option>
                            </select>
                        </div>

                        <div class="mb-3" id="pickupDateGroup" style="display: <?php echo $return['return_status'] === 'pickup_scheduled' ? 'block' : 'none'; ?>;">
                            <label class="form-label fw-bold">Pickup Date</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-calendar"></i></span>
                                <input type="date" class="form-control" name="pickup_date" 
                                       value="<?php echo $return['pickup_date'] ? date('Y-m-d', strtotime($return['pickup_date'])) : ''; ?>">
                            </div>
                        </div>

                        <div class="mb-3" id="refundAmountGroup" style="display: <?php echo $return['return_status'] === 'refund_completed' ? 'block' : 'none'; ?>;">
                            <label class="form-label fw-bold">Refund Amount</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-currency-rupee"></i></span>
                                <input type="number" class="form-control" name="refund_amount" step="0.01" min="0"
                                       value="<?php echo $return['refund_amount'] ?? ($orderItem ? $orderItem['subtotal'] : ''); ?>">
                            </div>
                            <small class="text-muted">Defaults to item subtotal</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Admin Remarks</label>
                            <textarea class="form-control" name="admin_remarks" rows="4" 
                                      placeholder="Add remarks about this return..."><?php echo htmlspecialchars($return['admin_remarks'] ?? ''); ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-check-circle-fill"></i> Update Status
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('newStatus').addEventListener('change', function() {
    var status = this.value;
    document.getElementById('pickupDateGroup').style.display = (status === 'pickup_scheduled') ? 'block' : 'none';
    document.getElementById('refundAmountGroup').style.display = (status === 'refund_completed') ? 'block' : 'none';
});
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
