<?php
require_once __DIR__ . '/../config/database.php';

// Sanitize input data
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Check if user is logged in
function isUserLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if admin is logged in
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

// Redirect to login if user not logged in
function requireUserLogin() {
    if (!isUserLoggedIn()) {
        header('Location: ' . SITE_URL . '/login.php');
        exit;
    }
}

// Redirect to login if admin not logged in
function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header('Location: ' . ADMIN_URL . '/login.php');
        exit;
    }
}

// Generate unique order number
function generateOrderNumber() {
    return 'ORD' . date('Ymd') . strtoupper(substr(uniqid(), -6));
}

// Upload file helper
function uploadFile($file, $targetDir, $allowedTypes = ['jpg', 'jpeg', 'png', 'gif']) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'No file uploaded or upload error'];
    }

    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileError = $file['error'];

    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if (!in_array($fileExt, $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }

    if ($fileSize > 5000000) { // 5MB
        return ['success' => false, 'message' => 'File size too large'];
    }

    $newFileName = uniqid('', true) . '.' . $fileExt;
    $targetPath = $targetDir . $newFileName;

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    if (move_uploaded_file($fileTmpName, $targetPath)) {
        return ['success' => true, 'filename' => $newFileName];
    }

    return ['success' => false, 'message' => 'Failed to upload file'];
}

// Delete file helper
function deleteFile($filePath) {
    if (file_exists($filePath)) {
        return unlink($filePath);
    }
    return false;
}

// Format price
function formatPrice($price) {
    return '₹' . number_format($price, 2);
}

/**
 * Helper to display product image safely everywhere
 * @param mixed $product_data Can be the image name string or the whole product array
 * @return string Correct URL to the image
 */
function getProductImage($product_data) {
    $placeholder = 'assets/images/no-image.jpg';
    $upload_path = 'uploads/products/';
    
    // Handle both string and array inputs
    $image_name = is_array($product_data) ? ($product_data['image'] ?? '') : $product_data;

    // If image exists in database and file exists on server
    if (!empty($image_name) && file_exists(__DIR__ . '/../' . $upload_path . $image_name)) {
        return SITE_URL . '/' . $upload_path . $image_name;
    } else {
        return SITE_URL . '/' . $placeholder;
    }
}

// Get cart count
function getCartCount() {
    if (!isUserLoggedIn()) {
        return 0;
    }
    
    $db = getDB();
    $stmt = $db->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch();
    return $result['total'] ?? 0;
}

// Flash message functions
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $flash;
    }
    return null;
}

// Pagination helper
function getPaginationData($totalItems, $currentPage, $itemsPerPage) {
    $totalPages = ceil($totalItems / $itemsPerPage);
    $offset = ($currentPage - 1) * $itemsPerPage;
    
    return [
        'total_pages' => $totalPages,
        'current_page' => $currentPage,
        'offset' => $offset,
        'items_per_page' => $itemsPerPage
    ];
}

// Time ago function
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) return $diff . ' seconds ago';
    if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    
    return date('M d, Y', $timestamp);
}

// ==================== RETURN SYSTEM FUNCTIONS ====================

// Check if an order item is eligible for return (7-day window from delivery)
function isReturnEligible($order, $orderItem = null) {
    if ($order['order_status'] !== 'completed') {
        return ['eligible' => false, 'reason' => 'Order has not been delivered yet.'];
    }
    // Use updated_at as approximate delivery date (when status changed to completed)
    $deliveryDate = $order['updated_at'] ?? $order['created_at'];
    $daysSinceDelivery = (time() - strtotime($deliveryDate)) / 86400;
    if ($daysSinceDelivery > 7) {
        return ['eligible' => false, 'reason' => 'The 7-day return period has expired.'];
    }
    return ['eligible' => true, 'days_left' => ceil(7 - $daysSinceDelivery)];
}

// Check if a return request already exists for an order item by a user
function hasActiveReturn($orderItemId, $userId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM returns WHERE order_item_id = ? AND user_id = ? AND return_status NOT IN ('rejected', 'refund_completed')");
    $stmt->execute([$orderItemId, $userId]);
    return $stmt->fetch() !== false;
}

// Calculate remaining days for return
function getRemainingReturnDays($order) {
    $deliveryDate = $order['updated_at'] ?? $order['created_at'];
    $daysSinceDelivery = (time() - strtotime($deliveryDate)) / 86400;
    $remaining = 7 - $daysSinceDelivery;
    return max(0, ceil($remaining));
}

// Get return reason label
function getReturnReasonLabel($reason) {
    $labels = [
        'wrong_product' => 'Wrong Product Received',
        'damaged' => 'Damaged Product',
        'not_as_described' => 'Product Not as Described',
        'size_issue' => 'Size Issue',
        'quality_issue' => 'Quality Issue',
        'other' => 'Other'
    ];
    return $labels[$reason] ?? ucfirst(str_replace('_', ' ', $reason));
}

// Get return status label
function getReturnStatusLabel($status) {
    $labels = [
        'requested' => 'Return Requested',
        'under_review' => 'Under Review',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'pickup_scheduled' => 'Pickup Scheduled',
        'returned' => 'Returned',
        'refund_completed' => 'Refund Completed'
    ];
    return $labels[$status] ?? ucfirst(str_replace('_', ' ', $status));
}

// Get return status badge class
function getReturnStatusBadge($status) {
    $badges = [
        'requested' => 'warning',
        'under_review' => 'info',
        'approved' => 'success',
        'rejected' => 'danger',
        'pickup_scheduled' => 'primary',
        'returned' => 'secondary',
        'refund_completed' => 'success'
    ];
    return $badges[$status] ?? 'secondary';
}

// Get refund status badge class
function getRefundStatusBadge($status) {
    $badges = [
        'pending' => 'warning',
        'processing' => 'info',
        'completed' => 'success'
    ];
    return $badges[$status] ?? 'secondary';
}

// ==================== ESTIMATED DELIVERY FUNCTIONS ====================

// Delivery time constant (in days)
define('DELIVERY_DAYS', 4);

// Get estimated delivery date from a given date
function getEstimatedDeliveryDate($fromDate = null) {
    $date = $fromDate ? new DateTime($fromDate) : new DateTime();
    $date->modify('+' . DELIVERY_DAYS . ' days');
    return $date;
}

// Check if an order has been delivered
function isOrderDelivered($order) {
    return $order['order_status'] === 'completed';
}

// Get estimated delivery date string for display (e.g. "31 July 2026")
function getEstimatedDeliveryDisplay($fromDate = null) {
    $deliveryDate = getEstimatedDeliveryDate($fromDate);
    return $deliveryDate->format('d M Y');
}

// Get days remaining until estimated delivery
function getDaysUntilDelivery($fromDate = null) {
    $deliveryDate = getEstimatedDeliveryDate($fromDate);
    $now = new DateTime();
    $diff = $now->diff($deliveryDate);
    return max(0, (int)$diff->format('%r%a'));
}

// Get return images for a return request
function getReturnImages($returnId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM return_images WHERE return_id = ? ORDER BY created_at ASC");
    $stmt->execute([$returnId]);
    return $stmt->fetchAll();
}

// Get return status history for a return request
function getReturnStatusHistory($returnId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM return_status_history WHERE return_id = ? ORDER BY created_at ASC");
    $stmt->execute([$returnId]);
    return $stmt->fetchAll();
}

// Log a return status change
function logReturnStatus($returnId, $oldStatus, $newStatus, $remark = null, $changedBy = null, $changedByType = 'admin') {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO return_status_history (return_id, old_status, new_status, remark, changed_by, changed_by_type, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$returnId, $oldStatus, $newStatus, $remark, $changedBy, $changedByType]);
}

// Upload return images
function uploadReturnImages($files, $returnId) {
    $db = getDB();
    $targetDir = UPLOAD_DIR . 'returns/';
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $uploaded = [];

    if (!isset($files['images']) || empty($files['images']['name'][0])) {
        return $uploaded;
    }

    $fileCount = count($files['images']['name']);
    for ($i = 0; $i < $fileCount; $i++) {
        $file = [
            'name' => $files['images']['name'][$i],
            'type' => $files['images']['type'][$i],
            'tmp_name' => $files['images']['tmp_name'][$i],
            'error' => $files['images']['error'][$i],
            'size' => $files['images']['size'][$i]
        ];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            continue;
        }

        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExt, $allowedTypes)) {
            continue;
        }

        if ($file['size'] > 5000000) { // 5MB
            continue;
        }

        $newFileName = 'return_' . $returnId . '_' . uniqid('', true) . '.' . $fileExt;
        $targetPath = $targetDir . $newFileName;

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $stmt = $db->prepare("INSERT INTO return_images (return_id, image_name, created_at) VALUES (?, ?, NOW())");
            $stmt->execute([$returnId, $newFileName]);
            $uploaded[] = $newFileName;
        }
    }

    return $uploaded;
}

// Delete return images from disk
function deleteReturnImages($returnId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT image_name FROM return_images WHERE return_id = ?");
    $stmt->execute([$returnId]);
    $images = $stmt->fetchAll();

    $targetDir = UPLOAD_DIR . 'returns/';
    foreach ($images as $img) {
        $path = $targetDir . $img['image_name'];
        if (file_exists($path)) {
            unlink($path);
        }
    }

    $stmt = $db->prepare("DELETE FROM return_images WHERE return_id = ?");
    $stmt->execute([$returnId]);
}

// Get return request count for admin badge
function getReturnRequestCount() {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM returns WHERE return_status IN ('requested', 'under_review')");
    $stmt->execute();
    return $stmt->fetch()['cnt'] ?? 0;
}
?>
