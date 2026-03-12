<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'phpecommerce');

// Site Configuration
define('SITE_NAME', 'PHP E-Commerce');
define('SITE_URL', 'http://localhost/phpecommerce');
define('ADMIN_URL', SITE_URL . '/admin');
define('USER_URL', SITE_URL . '/user');

// Upload Directories
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('PRODUCT_UPLOAD_DIR', UPLOAD_DIR . 'products/');
define('CATEGORY_UPLOAD_DIR', UPLOAD_DIR . 'categories/');

// URL paths for uploads
define('PRODUCT_UPLOAD_URL', SITE_URL . '/uploads/products/');
define('CATEGORY_UPLOAD_URL', SITE_URL . '/uploads/categories/');

// Pagination
define('PRODUCTS_PER_PAGE', 12);

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Timezone
date_default_timezone_set('Asia/Kolkata');
?>
