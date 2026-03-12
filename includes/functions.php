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
?>
