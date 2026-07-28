<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

requireUserLogin();

$db = getDB();
$userId = $_SESSION['user_id'];
$returnId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get return request (verify ownership)
$stmt = $db->prepare("
    SELECT r.*, o.order_number
    FROM returns r
    JOIN orders o ON r.order_id = o.id
    WHERE r.id = ? AND r.user_id = ?
");
$stmt->execute([$returnId, $userId]);
$return = $stmt->fetch();

if (!$return) {
    setFlashMessage('danger', 'Return request not found.');
    header('Location: ' . USER_URL . '/returns.php');
    exit;
}

// Get images
$returnImages = getReturnImages($returnId);

// Get status history
$statusHistory = getReturnStatusHistory($returnId);

$remainingDays = 0;
if ($return['return_status'] === 'requested' || $return['return_status'] === 'under_review') {
    $stmt2 = $db->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt2->execute([$return['order_id']]);
    $ord = $stmt2->fetch();
    if ($ord) $remainingDays = getRemainingReturnDays($ord);
}

$pageTitle = 'Return Details';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">

            <!-- Header Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-file-earmark-text"></i> 
                        Return #RTN<?php echo str_pad($return['id'], 4, '0', STR_PAD_LEFT); ?>
                    </h5>
                    <span class="badge bg-<?php echo getReturnStatusBadge($return['return_status']); ?> fs-6">
                        <?php echo getReturnStatusLabel($return['return_status']); ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Order Number:</strong> 
                                <a href="<?php echo USER_URL; ?>/order-details.php?id=<?php echo $return['order_id']; ?>">
                                    <?php echo htmlspecialchars($return['order_number']); ?>
                                </a>
                            </p>
                            <p><strong>Product:</strong> <?php echo htmlspecialchars($return['product_title']); ?></p>
                            <p><strong>Return Reason:</strong> <?php echo getReturnReasonLabel($return['return_reason']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Requested On:</strong> <?php echo date('M d, Y h:i A', strtotime($return['created_at'])); ?></p>
                            <p><strong>Last Updated:</strong> <?php echo date('M d, Y h:i A', strtotime($return['updated_at'])); ?></p>
                            <?php if ($remainingDays > 0 && in_array($return['return_status'], ['requested', 'under_review'])): ?>
                            <p class="text-<?php echo $remainingDays > 3 ? 'success' : ($remainingDays > 1 ? 'warning' : 'danger'); ?>">
                                <i class="bi bi-clock"></i> <strong><?php echo $remainingDays; ?> day<?php echo $remainingDays !== 1 ? 's' : ''; ?> remaining</strong> for return period
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (!empty($return['return_description'])): ?>
                    <hr>
                    <h6>Description:</h6>
                    <p class="text-muted"><?php echo nl2br(htmlspecialchars($return['return_description'])); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Refund Info -->
            <?php if (in_array($return['return_status'], ['approved', 'pickup_scheduled', 'returned', 'refund_completed'])): ?>
            <div class="card shadow-sm mb-4 border-success">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0"><i class="bi bi-wallet2"></i> Refund Information</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Refund Status:</strong> 
                                <span class="badge bg-<?php echo getRefundStatusBadge($return['refund_status']); ?>">
                                    <?php echo ucfirst($return['refund_status']); ?>
                                </span>
                            </p>
                        </div>
                        <?php if ($return['refund_amount']): ?>
                        <div class="col-md-6">
                            <p><strong>Refund Amount:</strong> 
                                <span class="text-success fw-bold"><?php echo formatPrice($return['refund_amount']); ?></span>
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Admin Remarks -->
            <?php if (!empty($return['admin_remarks'])): ?>
            <div class="card shadow-sm mb-4 border-info">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="bi bi-chat-dots"></i> Admin Remarks</h6>
                </div>
                <div class="card-body">
                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($return['admin_remarks'])); ?></p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Pickup Date -->
            <?php if ($return['pickup_date']): ?>
            <div class="card shadow-sm mb-4 border-warning">
                <div class="card-body">
                    <p class="mb-0">
                        <i class="bi bi-calendar-event text-warning fs-4 me-2"></i>
                        <strong>Pickup Scheduled:</strong> 
                        <?php echo date('l, M d, Y', strtotime($return['pickup_date'])); ?>
                    </p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Uploaded Images -->
            <?php if (count($returnImages) > 0): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-images"></i> Uploaded Images</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-3">
                        <?php foreach ($returnImages as $img): ?>
                        <a href="<?php echo SITE_URL; ?>/uploads/returns/<?php echo htmlspecialchars($img['image_name']); ?>" target="_blank">
                            <img src="<?php echo SITE_URL; ?>/uploads/returns/<?php echo htmlspecialchars($img['image_name']); ?>" 
                                 alt="Return Image" class="rounded border" 
                                 style="width: 120px; height: 120px; object-fit: cover;">
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Status Timeline -->
            <?php if (count($statusHistory) > 0): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-clock-history"></i> Status History</h6>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <?php foreach (array_reverse($statusHistory) as $history): ?>
                        <div class="d-flex mb-3">
                            <div class="me-3">
                                <div class="timeline-dot bg-<?php echo getReturnStatusBadge($history['new_status']); ?>"></div>
                            </div>
                            <div>
                                <div class="fw-bold"><?php echo getReturnStatusLabel($history['new_status']); ?></div>
                                <small class="text-muted"><?php echo date('M d, Y h:i A', strtotime($history['created_at'])); ?></small>
                                <?php if (!empty($history['remark'])): ?>
                                <div class="text-muted small mt-1"><?php echo nl2br(htmlspecialchars($history['remark'])); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="d-flex gap-2">
                <a href="<?php echo USER_URL; ?>/returns.php" class="btn btn-primary">
                    <i class="bi bi-arrow-left"></i> Back to My Returns
                </a>
                <a href="<?php echo USER_URL; ?>/order-details.php?id=<?php echo $return['order_id']; ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-bag"></i> View Order
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
