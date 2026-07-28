<?php
require_once __DIR__ . '/../config/database.php';

// Sanitize input data
function sanitize($data) {
    if ($data === null) return '';
    return htmlspecialchars(strip_tags(trim((string)$data)));
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

// ==================== LANGUAGE / TRANSLATION SYSTEM ====================

// Load translations for a given language code
function loadTranslations($langCode = 'en') {
    $db = getDB();
    $cacheKey = 'translations_' . $langCode;
    
    // Use session cache to avoid repeated DB queries
    if (isset($_SESSION[$cacheKey])) {
        return $_SESSION[$cacheKey];
    }
    
    $stmt = $db->prepare("SELECT translation_key, translation_value FROM site_translations WHERE lang_code = ?");
    $stmt->execute([$langCode]);
    $translations = [];
    while ($row = $stmt->fetch()) {
        $translations[$row['translation_key']] = $row['translation_value'];
    }
    
    // If translations not found for this language, fall back to English
    if (empty($translations) && $langCode !== 'en') {
        return loadTranslations('en');
    }
    
    $_SESSION[$cacheKey] = $translations;
    return $translations;
}

// Get current language code
function getCurrentLang() {
    return $_SESSION['site_lang'] ?? 'en';
}

// Translate a key
function t($key, $default = null) {
    $translations = loadTranslations(getCurrentLang());
    return $translations[$key] ?? $default ?? ucfirst(str_replace('_', ' ', $key));
}

// Set language
function setLanguage($langCode) {
    $validLangs = ['en', 'hi'];
    if (in_array($langCode, $validLangs)) {
        $_SESSION['site_lang'] = $langCode;
        // Clear translation cache
        unset($_SESSION['translations_en'], $_SESSION['translations_hi']);
    }
}

// ==================== CSRF PROTECTION ====================

// Generate CSRF token
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Get hidden input for CSRF
function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">';
}

// Verify CSRF token
function verifyCSRFToken() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return true;
    }
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

// ==================== REVIEW SYSTEM FUNCTIONS ====================

// Update product rating stats (avg_rating + review_count)
function syncProductRating($productId) {
    $db = getDB();
    $stmt = $db->prepare("
        UPDATE products 
        SET avg_rating = (SELECT COALESCE(AVG(rating), 0) FROM reviews WHERE product_id = ? AND status = 'active'),
            review_count = (SELECT COUNT(*) FROM reviews WHERE product_id = ? AND status = 'active')
        WHERE id = ?
    ");
    $stmt->execute([$productId, $productId, $productId]);
}

// Get average rating for a product
function getAverageRating($productId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM reviews WHERE product_id = ? AND status = 'active'");
    $stmt->execute([$productId]);
    $result = $stmt->fetch();
    return [
        'average' => $result['avg_rating'] ? round($result['avg_rating'], 1) : 0,
        'total' => (int)$result['total_reviews']
    ];
}

// Get reviews for a product
function getProductReviews($productId, $limit = 50, $offset = 0) {
    $db = getDB();
    $limit = (int)$limit;
    $offset = (int)$offset;
    $stmt = $db->prepare("
        SELECT r.*, u.name as user_name 
        FROM reviews r 
        JOIN users u ON r.user_id = u.id 
        WHERE r.product_id = ? AND r.status = 'active' 
        ORDER BY r.created_at DESC 
        LIMIT {$limit} OFFSET {$offset}
    ");
    $stmt->execute([$productId]);
    return $stmt->fetchAll();
}

// Check if user has purchased this product
function hasUserPurchasedProduct($userId, $productId) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT COUNT(*) as cnt 
        FROM order_items oi 
        JOIN orders o ON oi.order_id = o.id 
        WHERE o.user_id = ? AND oi.product_id = ? AND o.order_status = 'completed'
    ");
    $stmt->execute([$userId, $productId]);
    return $stmt->fetch()['cnt'] > 0;
}

// Check if user already reviewed this product
function hasUserReviewedProduct($userId, $productId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT id, rating, comment FROM reviews WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$userId, $productId]);
    return $stmt->fetch();
}

// Get rating distribution
function getRatingDistribution($productId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT rating, COUNT(*) as cnt FROM reviews WHERE product_id = ? AND status = 'active' GROUP BY rating ORDER BY rating DESC");
    $stmt->execute([$productId]);
    $dist = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
    while ($row = $stmt->fetch()) {
        $dist[(int)$row['rating']] = (int)$row['cnt'];
    }
    return $dist;
}

// Generate star HTML
function renderStars($rating, $size = '') {
    $sizeClass = $size ? ' fs-' . $size : '';
    $html = '<span class="text-warning' . $sizeClass . '">';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= floor($rating)) {
            $html .= '<i class="bi bi-star-fill"></i>';
        } elseif ($i - $rating < 1 && $i - $rating > 0) {
            $html .= '<i class="bi bi-star-half"></i>';
        } else {
            $html .= '<i class="bi bi-star"></i>';
        }
    }
    $html .= '</span>';
    return $html;
}

// ==================== WISHLIST SYSTEM FUNCTIONS ====================

// Get wishlist count for a user
function getWishlistCount() {
    if (!isUserLoggedIn()) {
        return 0;
    }
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM wishlist WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch()['cnt'] ?? 0;
}

// Check if product is in wishlist
function isInWishlist($productId) {
    if (!isUserLoggedIn()) {
        return false;
    }
    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$_SESSION['user_id'], $productId]);
    return $stmt->fetch() !== false;
}

// Get wishlist items for a user
function getWishlistItems() {
    if (!isUserLoggedIn()) {
        return [];
    }
    $db = getDB();
    $stmt = $db->prepare("
        SELECT w.*, p.title, p.price, p.image, p.stock, p.status as product_status, c.name as category_name
        FROM wishlist w
        JOIN products p ON w.product_id = p.id
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE w.user_id = ?
        ORDER BY w.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetchAll();
}

// ==================== ADVANCED SEARCH / FILTER FUNCTIONS ====================

// Get all active brands
function getAllBrands() {
    $db = getDB();
    return $db->query("SELECT id, name, slug FROM brands WHERE status = 'active' ORDER BY name")->fetchAll();
}

// Get all active subcategories
function getAllSubcategories($categoryId = null) {
    $db = getDB();
    if ($categoryId) {
        $stmt = $db->prepare("SELECT id, name, slug, category_id FROM subcategories WHERE category_id = ? AND status = 'active' ORDER BY name");
        $stmt->execute([$categoryId]);
        return $stmt->fetchAll();
    }
    return $db->query("SELECT s.id, s.name, s.slug, s.category_id, c.name as category_name FROM subcategories s JOIN categories c ON s.category_id = c.id WHERE s.status = 'active' ORDER BY c.name, s.name")->fetchAll();
}

// Get min and max product prices
function getPriceRange() {
    $db = getDB();
    $result = $db->query("SELECT MIN(price) as min_price, MAX(price) as max_price FROM products WHERE status = 'active'")->fetch();
    return [
        'min' => (int)floor($result['min_price'] ?? 0),
        'max' => (int)ceil($result['max_price'] ?? 10000)
    ];
}

// Advanced product search with filters
function searchProducts($filters = [], $page = 1, $perPage = 12) {
    $db = getDB();
    $where = ["p.status = 'active'"];
    $params = [];

    // Category filter
    if (!empty($filters['category_id'])) {
        $where[] = "p.category_id = ?";
        $params[] = (int)$filters['category_id'];
    }

    // Subcategory filter
    if (!empty($filters['subcategory_id'])) {
        $where[] = "p.subcategory_id = ?";
        $params[] = (int)$filters['subcategory_id'];
    }

    // Brand filter
    if (!empty($filters['brand_id'])) {
        $where[] = "p.brand_id = ?";
        $params[] = (int)$filters['brand_id'];
    }

    // Price range
    if (isset($filters['min_price']) && $filters['min_price'] !== '') {
        $where[] = "p.price >= ?";
        $params[] = (float)$filters['min_price'];
    }
    if (isset($filters['max_price']) && $filters['max_price'] !== '') {
        $where[] = "p.price <= ?";
        $params[] = (float)$filters['max_price'];
    }

    // Minimum rating
    if (!empty($filters['min_rating'])) {
        $having = "HAVING avg_rating >= ?";
        $params[] = (float)$filters['min_rating'];
    } else {
        $having = "";
    }

    // Availability
    if (isset($filters['in_stock']) && $filters['in_stock'] === '1') {
        $where[] = "p.stock > 0";
    }
    if (isset($filters['in_stock']) && $filters['in_stock'] === '0') {
        $where[] = "p.stock = 0";
    }

    // Featured
    if (!empty($filters['featured'])) {
        $where[] = "p.is_featured = 1";
    }

    // Discounted
    if (!empty($filters['discounted'])) {
        $where[] = "p.discount_percent > 0";
    }

    // Search query
    if (!empty($filters['q'])) {
        $where[] = "(p.title LIKE ? OR p.description LIKE ?)";
        $searchTerm = "%" . $filters['q'] . "%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    $whereClause = implode(' AND ', $where);

    // Sorting
    $orderBy = "p.created_at DESC";
    if (!empty($filters['sort'])) {
        switch ($filters['sort']) {
            case 'price_low': $orderBy = "p.price ASC"; break;
            case 'price_high': $orderBy = "p.price DESC"; break;
            case 'newest': $orderBy = "p.created_at DESC"; break;
            case 'oldest': $orderBy = "p.created_at ASC"; break;
            case 'rating': $orderBy = "avg_rating DESC"; break;
            case 'best_selling': $orderBy = "p.sales_count DESC"; break;
            case 'popular': $orderBy = "review_count DESC"; break;
            case 'az': $orderBy = "p.title ASC"; break;
            case 'za': $orderBy = "p.title DESC"; break;
        }
    }

    // Base query with subquery for ratings
    $baseQuery = "
        FROM products p 
        LEFT JOIN (SELECT product_id, AVG(rating) as avg_rating, COUNT(*) as review_count FROM reviews WHERE status = 'active' GROUP BY product_id) rev ON rev.product_id = p.id
        LEFT JOIN brands b ON p.brand_id = b.id
        LEFT JOIN subcategories s ON p.subcategory_id = s.id
        WHERE {$whereClause}
    ";

    // Count total
    $countParams = $params;
    if ($having) {
        $countSql = "SELECT COUNT(*) as total FROM (SELECT p.id {$baseQuery} GROUP BY p.id {$having}) as filtered";
    } else {
        $countSql = "SELECT COUNT(*) as total {$baseQuery}";
    }
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($countParams);
    $total = $countStmt->fetch()['total'];

    // Get products
    $offset = ($page - 1) * $perPage;

    $selectCols = "SELECT p.*, COALESCE(rev.avg_rating, 0) as avg_rating, COALESCE(rev.review_count, 0) as review_count, b.name as brand_name, s.name as subcategory_name";
    $productQuery = "{$selectCols} {$baseQuery} GROUP BY p.id {$having} ORDER BY {$orderBy} LIMIT {$perPage} OFFSET {$offset}";
    $stmt = $db->prepare($productQuery);
    $stmt->execute($params);
    $products = $stmt->fetchAll();

    return [
        'products' => $products,
        'total' => $total,
        'total_pages' => ceil($total / $perPage),
        'current_page' => $page,
        'per_page' => $perPage
    ];
}
?>
