<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

try {
    $db = getDB();

    // Handle subcategory loading
    $action = $_GET['action'] ?? '';
    if ($action === 'subcategories') {
        $catId = (int)($_GET['category_id'] ?? 0);
        $subs = getAllSubcategories($catId);
        echo json_encode(['success' => true, 'subcategories' => $subs]);
        exit;
    }

    $filters = [
        'q' => sanitize($_GET['q'] ?? ''),
        'category_id' => $_GET['category_id'] ?? '',
        'subcategory_id' => $_GET['subcategory_id'] ?? '',
        'brand_id' => $_GET['brand_id'] ?? '',
        'min_price' => $_GET['min_price'] ?? '',
        'max_price' => $_GET['max_price'] ?? '',
        'min_rating' => $_GET['min_rating'] ?? '',
        'in_stock' => $_GET['in_stock'] ?? '',
        'featured' => $_GET['featured'] ?? '',
        'discounted' => $_GET['discounted'] ?? '',
        'best_selling' => $_GET['best_selling'] ?? '',
        'sort' => $_GET['sort'] ?? 'newest',
    ];

    $page = max(1, (int)($_GET['page'] ?? 1));

    $filters = array_filter($filters, function($v) { return $v !== '' && $v !== null; });

    $result = searchProducts($filters, $page, 12);

    $html = '';
    if (empty($result['products'])) {
        $html = '<div class="col-12 text-center py-5"><i class="bi bi-search fs-1 text-muted"></i><p class="mt-3 text-muted">' . t('no_products_found', 'No products found') . '</p></div>';
    } else {
        foreach ($result['products'] as $product) {
            $imgSrc = getProductImage($product);
            $discount = $product['discount_percent'] > 0 ? round($product['discount_percent']) : 0;
            $discountedPrice = $discount > 0 ? $product['price'] * (1 - $discount / 100) : $product['price'];
            $inWishlist = isUserLoggedIn() && isInWishlist($product['id']);
            $heartIcon = $inWishlist ? 'bi-heart-fill text-danger' : 'bi-heart';
            
            $html .= '<div class="col-md-3 col-sm-6 mb-4">
                <div class="card product-card h-100 position-relative">
                    <button class="btn wishlist-btn position-absolute top-0 end-0 m-2 p-1 border-0 bg-transparent z-1" data-product-id="' . $product['id'] . '" title="' . t('add_to_wishlist', 'Add to Wishlist') . '">
                        <i class="bi ' . $heartIcon . ' fs-5"></i>
                    </button>
                    ' . ($discount > 0 ? '<span class="badge bg-danger position-absolute top-0 start-0 m-2 z-1">-' . $discount . '%</span>' : '') . '
                    <div class="product-image">
                        <img src="' . $imgSrc . '" class="card-img-top" alt="' . htmlspecialchars($product['title']) . '">
                    </div>
                    <div class="card-body d-flex flex-column">
                        <h6 class="card-title text-truncate">' . htmlspecialchars($product['title']) . '</h6>
                        <div class="mb-2">' . renderStars($product['avg_rating']) . ' <small class="text-muted">(' . $product['review_count'] . ')</small></div>
                        <div class="mb-2">
                            ' . ($discount > 0 ? '<span class="text-decoration-line-through text-muted">' . formatPrice($product['price']) . '</span> ' : '') . '
                            <span class="text-primary fw-bold">' . formatPrice($discountedPrice) . '</span>
                        </div>
                        <div class="mt-auto">
                            <a href="' . SITE_URL . '/product-details.php?id=' . $product['id'] . '" class="btn btn-outline-primary btn-sm w-100">' . t('view_details', 'View Details') . '</a>
                        </div>
                    </div>
                </div>
            </div>';
        }
    }

    $paginationHtml = '';
    if ($result['total_pages'] > 1) {
        $paginationHtml = '<nav><ul class="pagination justify-content-center mt-4">';
        for ($i = 1; $i <= $result['total_pages']; $i++) {
            $active = $i == $result['current_page'] ? ' active' : '';
            $paginationHtml .= '<li class="page-item' . $active . '"><a class="page-link search-page-link" href="#" data-page="' . $i . '">' . $i . '</a></li>';
        }
        $paginationHtml .= '</ul></nav>';
    }

    echo json_encode([
        'success' => true,
        'html' => $html,
        'pagination' => $paginationHtml,
        'total' => $result['total'],
        'total_pages' => $result['total_pages'],
        'current_page' => $result['current_page']
    ]);

} catch (Exception $e) {
    error_log('Search API error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error. Please try again.', 'html' => '', 'pagination' => '', 'total' => 0, 'total_pages' => 0, 'current_page' => 1]);
}
