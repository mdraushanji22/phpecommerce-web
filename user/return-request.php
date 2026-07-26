<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

requireUserLogin();

$pageTitle = 'Return Product';
require_once __DIR__ . '/../includes/header.php';

$db = getDB();
$userId = $_SESSION['user_id'];
$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$orderIdItem = isset($_GET['item_id']) ? (int)$_GET['item_id'] : 0;

// Validate order belongs to user
$stmt = $db->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$orderId, $userId]);
$order = $stmt->fetch();

if (!$order) {
    setFlashMessage('danger', 'Order not found.');
    header('Location: ' . USER_URL . '/orders.php');
    exit;
}

// Validate order item
$stmt = $db->prepare("SELECT * FROM order_items WHERE id = ? AND order_id = ?");
$stmt->execute([$orderIdItem, $orderId]);
$orderItem = $stmt->fetch();

if (!$orderItem) {
    setFlashMessage('danger', 'Order item not found.');
    header('Location: ' . USER_URL . '/order-details.php?id=' . $orderId);
    exit;
}

// Check return eligibility
$eligibility = isReturnEligible($order);
if (!$eligibility['eligible']) {
    setFlashMessage('danger', $eligibility['reason']);
    header('Location: ' . USER_URL . '/order-details.php?id=' . $orderId);
    exit;
}

// Check if already has active return
if (hasActiveReturn($orderIdItem, $userId)) {
    setFlashMessage('warning', 'A return request already exists for this product.');
    header('Location: ' . USER_URL . '/order-details.php?id=' . $orderId);
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reason = sanitize($_POST['return_reason'] ?? '');
    $description = trim($_POST['return_description'] ?? '');

    $validReasons = ['wrong_product', 'damaged', 'not_as_described', 'size_issue', 'quality_issue', 'other'];
    $errors = [];

    if (empty($reason) || !in_array($reason, $validReasons)) {
        $errors[] = 'Please select a valid return reason.';
    }
    if (empty($description)) {
        $errors[] = 'Please provide a description for the return.';
    }
    if (strlen($description) > 2000) {
        $errors[] = 'Description must be less than 2000 characters.';
    }

    // Validate uploaded images
    $validImageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxFileSize = 5 * 1024 * 1024; // 5MB
    $maxImages = 5;
    if (!empty($_FILES['images']['name'][0])) {
        $imageCount = count($_FILES['images']['name']);
        if ($imageCount > $maxImages) {
            $errors[] = "You can upload a maximum of {$maxImages} images.";
        }
        for ($i = 0; $i < $imageCount; $i++) {
            if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                if (!in_array($_FILES['images']['type'][$i], $validImageTypes)) {
                    $errors[] = 'Invalid image type. Allowed: JPG, PNG, GIF, WebP.';
                    break;
                }
                if ($_FILES['images']['size'][$i] > $maxFileSize) {
                    $errors[] = 'Each image must be less than 5MB.';
                    break;
                }
            }
        }
    }

    if (empty($errors)) {
        // Insert return request
        $stmt = $db->prepare("
            INSERT INTO returns (order_id, order_item_id, user_id, product_id, product_title, return_reason, return_description, return_status, refund_status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'requested', 'pending', NOW(), NOW())
        ");
        $stmt->execute([
            $orderId,
            $orderIdItem,
            $userId,
            $orderItem['product_id'],
            $orderItem['product_title'],
            $reason,
            $description
        ]);

        $returnId = $db->lastInsertId();

        // Log initial status
        logReturnStatus($returnId, null, 'requested', 'Return request submitted by customer.', $userId, 'user');

        // Upload images
        if (!empty($_FILES['images']['name'][0])) {
            uploadReturnImages($_FILES, $returnId);
        }

        setFlashMessage('success', 'Return request submitted successfully! We will review it shortly.');
        header('Location: ' . USER_URL . '/returns.php');
        exit;
    }
}

$remainingDays = getRemainingReturnDays($order);
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-arrow-return-left"></i> Return Product</h5>
                </div>
                <div class="card-body">

                    <!-- Remaining Days Alert -->
                    <div class="alert alert-info d-flex align-items-center mb-4">
                        <i class="bi bi-clock-history fs-4 me-3"></i>
                        <div>
                            <strong><?php echo $remainingDays; ?> day<?php echo $remainingDays !== 1 ? 's' : ''; ?> remaining</strong> to return this product.
                            <div class="progress mt-2" style="height: 6px;">
                                <div class="progress-bar bg-<?php echo $remainingDays > 3 ? 'success' : ($remainingDays > 1 ? 'warning' : 'danger'); ?>" 
                                     style="width: <?php echo ($remainingDays / 7) * 100; ?>%"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Product Info -->
                    <div class="card mb-4 border-light">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <?php
                                $productImage = getProductImage($orderItem['product_id'] ?? $orderItem['product_title']);
                                // Try to get actual product image
                                $prodStmt = $db->prepare("SELECT image FROM products WHERE id = ?");
                                $prodStmt->execute([$orderItem['product_id']]);
                                $prodImg = $prodStmt->fetch();
                                if ($prodImg) $productImage = getProductImage($prodImg['image'] ?? '');
                                ?>
                                <img src="<?php echo $productImage; ?>" alt="" class="rounded me-3" style="width: 80px; height: 80px; object-fit: cover;">
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($orderItem['product_title']); ?></h6>
                                    <p class="mb-0 text-muted">
                                        Qty: <?php echo $orderItem['quantity']; ?> &middot; 
                                        Price: <?php echo formatPrice($orderItem['product_price']); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $err): ?>
                            <li><?php echo htmlspecialchars($err); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <!-- Return Form -->
                    <form method="POST" enctype="multipart/form-data" id="returnForm">
                        <div class="mb-3">
                            <label for="return_reason" class="form-label fw-bold">Return Reason *</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-tag-fill"></i></span>
                                <select class="form-select" id="return_reason" name="return_reason" required>
                                    <option value="">-- Select Reason --</option>
                                    <option value="wrong_product" <?php echo (isset($reason) && $reason === 'wrong_product') ? 'selected' : ''; ?>>Wrong Product Received</option>
                                    <option value="damaged" <?php echo (isset($reason) && $reason === 'damaged') ? 'selected' : ''; ?>>Damaged Product</option>
                                    <option value="not_as_described" <?php echo (isset($reason) && $reason === 'not_as_described') ? 'selected' : ''; ?>>Product Not as Described</option>
                                    <option value="size_issue" <?php echo (isset($reason) && $reason === 'size_issue') ? 'selected' : ''; ?>>Size Issue</option>
                                    <option value="quality_issue" <?php echo (isset($reason) && $reason === 'quality_issue') ? 'selected' : ''; ?>>Quality Issue</option>
                                    <option value="other" <?php echo (isset($reason) && $reason === 'other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="return_description" class="form-label fw-bold">Description *</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-textarea-resize"></i></span>
                                <textarea class="form-control" id="return_description" name="return_description" rows="5" 
                                          required maxlength="2000"
                                          placeholder="Please describe the issue with the product in detail..."><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
                            </div>
                            <small class="text-muted"><span id="descCount">0</span>/2000 characters</small>
                        </div>

                        <div class="mb-4">
                            <label for="images" class="form-label fw-bold">Upload Product Images (Optional)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-camera-fill"></i></span>
                                <input type="file" class="form-control" id="images" name="images[]" multiple 
                                       accept="image/jpeg,image/png,image/gif,image/webp">
                            </div>
                            <small class="text-muted">Max 5 images, each under 5MB. Allowed: JPG, PNG, GIF, WebP</small>
                            <div id="imagePreview" class="mt-2 d-flex flex-wrap gap-2"></div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-send-fill"></i> Submit Return Request
                            </button>
                            <a href="<?php echo USER_URL; ?>/order-details.php?id=<?php echo $orderId; ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Character counter for description
document.getElementById('return_description').addEventListener('input', function() {
    document.getElementById('descCount').textContent = this.value.length;
});
document.getElementById('descCount').textContent = document.getElementById('return_description').value.length;

// Image preview
document.getElementById('images').addEventListener('change', function(e) {
    var preview = document.getElementById('imagePreview');
    preview.innerHTML = '';
    var files = e.target.files;
    for (var i = 0; i < files.length && i < 5; i++) {
        if (files[i].type.startsWith('image/')) {
            var reader = new FileReader();
            reader.onload = function(ev) {
                var img = document.createElement('img');
                img.src = ev.target.result;
                img.style.cssText = 'width:100px;height:100px;object-fit:cover;border-radius:8px;border:2px solid #dee2e6;';
                preview.appendChild(img);
            };
            reader.readAsDataURL(files[i]);
        }
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
