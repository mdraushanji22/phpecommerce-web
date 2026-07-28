<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($productId <= 0) {
    header('Location: ' . SITE_URL . '/products.php');
    exit;
}

require_once __DIR__ . '/includes/header.php';

$db = getDB();

$stmt = $db->prepare("
    SELECT p.*, c.name as category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.id = ? AND p.status = 'active'
");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: ' . SITE_URL . '/products.php');
    exit;
}

$pageTitle = $product['title'];

// Get rating info
$ratingInfo = getAverageRating($productId);
$ratingDist = getRatingDistribution($productId);
$reviews = getProductReviews($productId, 20);
$inWishlist = isUserLoggedIn() ? isInWishlist($productId) : false;
$userReview = isUserLoggedIn() ? hasUserReviewedProduct($_SESSION['user_id'], $productId) : false;
$canReview = isUserLoggedIn() ? hasUserPurchasedProduct($_SESSION['user_id'], $productId) : false;

// Get related products
$stmt = $db->prepare("
    SELECT * FROM products 
    WHERE category_id = ? AND id != ? AND status = 'active'
    ORDER BY RAND()
    LIMIT 4
");
$stmt->execute([$product['category_id'], $productId]);
$relatedProducts = $stmt->fetchAll();
?>

<div class="container my-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/">Home</a></li>
            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/products.php">Products</a></li>
            <li class="breadcrumb-item"><?php echo htmlspecialchars($product['category_name']); ?></li>
            <li class="breadcrumb-item active"><?php echo htmlspecialchars($product['title']); ?></li>
        </ol>
    </nav>

    <div class="row">
        <!-- Product Images -->
        <div class="col-md-6 mb-4">
            <div class="main-product-image">
                <img src="<?php echo getProductImage($product); ?>" 
                     class="img-fluid rounded shadow-sm w-100" alt="<?php echo htmlspecialchars($product['title']); ?>">
            </div>
        </div>

        <!-- Product Details -->
        <div class="col-md-6">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="badge bg-info"><?php echo htmlspecialchars($product['category_name']); ?></span>
                <button class="btn btn-outline-danger btn-sm wishlist-toggle" data-product-id="<?php echo $product['id']; ?>" title="<?php echo $inWishlist ? t('remove_from_wishlist') : t('add_to_wishlist'); ?>">
                    <i class="bi <?php echo $inWishlist ? 'bi-heart-fill' : 'bi-heart'; ?>" id="wishlistIcon"></i>
                </button>
            </div>
            <h1 class="mb-2"><?php echo htmlspecialchars($product['title']); ?></h1>
            
            <!-- Rating Summary -->
            <?php if ($ratingInfo['total'] > 0): ?>
            <div class="mb-3 d-flex align-items-center gap-2">
                <?php echo renderStars($ratingInfo['average']); ?>
                <span class="text-muted">(<?php echo $ratingInfo['average']; ?> / 5 - <?php echo $ratingInfo['total']; ?> <?php echo $ratingInfo['total'] === 1 ? 'review' : 'reviews'; ?>)</span>
            </div>
            <?php endif; ?>
            
            <h2 class="text-primary mb-4">
                <?php
                $dPrice = $product['price'];
                if ($product['discount_percent'] > 0) {
                    $dPrice = $product['price'] * (1 - $product['discount_percent'] / 100);
                    echo '<span class="text-decoration-line-through text-muted fs-5">' . formatPrice($product['price']) . '</span> ';
                }
                echo formatPrice($dPrice);
                ?>
            </h2>

            <div class="mb-4">
                <?php if ($product['stock'] > 0): ?>
                <span class="badge bg-success">In Stock (<?php echo $product['stock']; ?> units)</span>
                <?php else: ?>
                <span class="badge bg-danger">Out of Stock</span>
                <?php endif; ?>
            </div>

            <!-- Estimated Delivery -->
            <div class="delivery-card mb-4">
                <div class="delivery-icon"><i class="bi bi-truck"></i></div>
                <div class="delivery-text">
                    <div class="small text-muted">Estimated Delivery</div>
                    <strong><?php echo getEstimatedDeliveryDisplay(); ?> (Within <?php echo DELIVERY_DAYS; ?> Days)</strong>
                </div>
            </div>

            <p class="mb-4"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>

            <?php if ($product['stock'] > 0): ?>
            <form id="addToCartForm" method="POST" action="<?php echo SITE_URL; ?>/cart-action.php">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                
                <div class="row mb-4">
                    <div class="col-md-4">
                        <label for="quantity" class="form-label">Quantity:</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-box-seam"></i></span>
                            <input type="number" class="form-control" id="quantity" name="quantity" 
                                   placeholder="Qty" value="1" min="1" max="<?php echo $product['stock']; ?>" required>
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-cart-plus"></i> Add to Cart
                    </button>
                </div>
            </form>
            <?php else: ?>
            <button class="btn btn-secondary btn-lg" disabled>Out of Stock</button>
            <?php endif; ?>

            <hr class="my-4">

            <div class="product-features">
                <h5 class="mb-3">Product Features:</h5>
                <ul class="list-unstyled">
                    <li><i class="bi bi-check-circle text-success"></i> High Quality Product</li>
                    <li><i class="bi bi-check-circle text-success"></i> Fast Shipping Available</li>
                    <li><i class="bi bi-check-circle text-success"></i> 7 Days Return Policy</li>
                    <li><i class="bi bi-check-circle text-success"></i> Secure Payment</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Reviews Section -->
    <div class="mt-5" id="reviewsSection">
        <div class="row">
            <div class="col-md-8">
                <h3 class="mb-4"><i class="bi bi-chat-left-text"></i> <?php echo t('customer_reviews', 'Customer Reviews'); ?> (<?php echo $ratingInfo['total']; ?>)</h3>

                <!-- Write Review Form -->
                <?php if (isUserLoggedIn()): ?>
                <div class="card mb-4" id="reviewFormCard">
                    <div class="card-body">
                        <?php if ($userReview): ?>
                            <h6 class="mb-3"><?php echo t('edit_review', 'Edit Review'); ?></h6>
                        <?php else: ?>
                            <h6 class="mb-3"><?php echo t('write_review', 'Write a Review'); ?></h6>
                        <?php endif; ?>

                        <?php if (!$canReview && !$userReview): ?>
                            <div class="alert alert-info mb-0">
                                <i class="bi bi-info-circle"></i> <?php echo t('only_purchased', 'You can only review products you have purchased'); ?>
                            </div>
                        <?php else: ?>
                        <form id="reviewForm">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                            <input type="hidden" name="product_id" value="<?php echo $productId; ?>">
                            <?php if ($userReview): ?>
                            <input type="hidden" name="review_id" value="<?php echo (int)$userReview['id']; ?>">
                            <?php endif; ?>

                            <div class="mb-3">
                                <label class="form-label fw-bold"><?php echo t('your_rating', 'Your Rating'); ?> *</label>
                                <div class="star-rating-input" id="starRating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="bi bi-star fs-4 star-btn" data-value="<?php echo $i; ?>" style="cursor:pointer;color:#ffc107;"></i>
                                    <?php endfor; ?>
                                    <input type="hidden" name="rating" id="ratingValue" value="<?php echo $userReview ? (int)$userReview['rating'] : ''; ?>" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold"><?php echo t('your_review', 'Your Review'); ?> *</label>
                                <textarea class="form-control" name="comment" id="reviewComment" rows="4" 
                                          placeholder="<?php echo t('review_placeholder', 'Write your thoughts about this product...'); ?>" required><?php echo $userReview ? htmlspecialchars($userReview['comment']) : ''; ?></textarea>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary" id="submitReviewBtn">
                                    <i class="bi bi-send"></i> <?php echo $userReview ? t('edit_review', 'Update Review') : t('submit_review', 'Submit Review'); ?>
                                </button>
                                <?php if ($userReview): ?>
                                <button type="button" class="btn btn-outline-danger btn-sm" id="deleteReviewBtn">
                                    <i class="bi bi-trash"></i> <?php echo t('delete_review', 'Delete'); ?>
                                </button>
                                <?php endif; ?>
                            </div>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Reviews List -->
                <div id="reviewsList">
                    <?php if (empty($reviews)): ?>
                    <div class="text-center py-4">
                        <i class="bi bi-chat-dots fs-1 text-muted"></i>
                        <p class="mt-2 text-muted"><?php echo t('no_reviews', 'No reviews yet'); ?></p>
                    </div>
                    <?php else: ?>
                        <?php foreach ($reviews as $review): ?>
                        <div class="card mb-3 review-card" data-review-id="<?php echo $review['id']; ?>">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1"><i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($review['user_name']); ?></h6>
                                        <div class="mb-1"><?php echo renderStars($review['rating']); ?></div>
                                    </div>
                                    <small class="text-muted"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></small>
                                </div>
                                <p class="mb-0 mt-2"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Rating Distribution Sidebar -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <h2 class="text-primary mb-1"><?php echo $ratingInfo['average']; ?></h2>
                        <?php echo renderStars($ratingInfo['average']); ?>
                        <p class="text-muted mb-3"><?php echo $ratingInfo['total']; ?> <?php echo $ratingInfo['total'] === 1 ? 'review' : 'reviews'; ?></p>
                        
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                        <div class="d-flex align-items-center mb-1">
                            <small class="me-2" style="width:20px;"><?php echo $i; ?>★</small>
                            <div class="progress flex-grow-1" style="height: 8px;">
                                <?php $pct = $ratingInfo['total'] > 0 ? ($ratingDist[$i] / $ratingInfo['total']) * 100 : 0; ?>
                                <div class="progress-bar bg-warning" style="width: <?php echo $pct; ?>%"></div>
                            </div>
                            <small class="ms-2 text-muted" style="width:30px;"><?php echo $ratingDist[$i]; ?></small>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Related Products -->
    <?php if (count($relatedProducts) > 0): ?>
    <div class="mt-5">
        <h3 class="mb-4">Related Products</h3>
        <div class="row g-4">
            <?php foreach ($relatedProducts as $relatedProduct): ?>
            <div class="col-md-3">
                <div class="card product-card h-100">
                    <div class="product-image">
                        <img src="<?php echo getProductImage($relatedProduct); ?>" 
                             class="card-img-top" alt="<?php echo htmlspecialchars($relatedProduct['title']); ?>">
                    </div>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($relatedProduct['title']); ?></h5>
                        <p class="text-primary fw-bold"><?php echo formatPrice($relatedProduct['price']); ?></p>
                    </div>
                    <div class="card-footer bg-white border-top-0">
                        <a href="<?php echo SITE_URL; ?>/product-details.php?id=<?php echo $relatedProduct['id']; ?>" 
                           class="btn btn-outline-primary btn-sm w-100">View Details</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = csrfMeta ? csrfMeta.content : '';
    var productId = <?php echo $productId; ?>;
    var submitting = false;

    // Star Rating Input
    var stars = document.querySelectorAll('#starRating .star-btn');
    var ratingInput = document.getElementById('ratingValue');
    
    if (ratingInput && ratingInput.value) {
        highlightStars(parseInt(ratingInput.value));
    }

    stars.forEach(function(star) {
        star.addEventListener('click', function() {
            var val = parseInt(this.dataset.value);
            ratingInput.value = val;
            highlightStars(val);
        });
        star.addEventListener('mouseenter', function() {
            highlightStars(parseInt(this.dataset.value));
        });
    });

    var starContainer = document.getElementById('starRating');
    if (starContainer) {
        starContainer.addEventListener('mouseleave', function() {
            highlightStars(parseInt(ratingInput.value || 0));
        });
    }

    function highlightStars(val) {
        stars.forEach(function(s) {
            s.className = 'bi fs-4 star-btn ' + (parseInt(s.dataset.value) <= val ? 'bi-star-fill' : 'bi-star');
        });
    }

    // Submit Review (AJAX) with try/catch + submit protection
    var reviewForm = document.getElementById('reviewForm');
    if (reviewForm) {
        reviewForm.addEventListener('submit', function(e) {
            e.preventDefault();
            if (submitting) return;
            
            var rating = ratingInput.value;
            var commentEl = document.getElementById('reviewComment');
            var comment = commentEl ? commentEl.value.trim() : '';
            var reviewIdEl = this.querySelector('[name="review_id"]');
            var action = reviewIdEl ? 'update' : 'submit';

            if (!rating || parseInt(rating) < 1) { alert('Please select a rating'); return; }
            if (comment.length < 10) { alert('Review must be at least 10 characters'); return; }

            submitting = true;
            var btn = document.getElementById('submitReviewBtn');
            if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Submitting...'; }

            var fd = new FormData(this);
            fd.append('action', action);

            fetch('<?php echo SITE_URL; ?>/api/reviews.php', { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message);
                    submitting = false;
                    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-send"></i> Submit'; }
                }
            })
            .catch(function(err) {
                console.error('Review error:', err);
                alert('Network error. Please try again.');
                submitting = false;
                if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-send"></i> Submit'; }
            });
        });
    }

    // Delete Review with try/catch
    var deleteBtn = document.getElementById('deleteReviewBtn');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', function() {
            if (!confirm('Delete your review?')) return;
            var fd = new FormData();
            fd.append('action', 'delete');
            var reviewIdEl2 = reviewForm ? reviewForm.querySelector('[name="review_id"]') : null;
            fd.append('review_id', reviewIdEl2 ? reviewIdEl2.value : '0');
            fd.append('csrf_token', csrfToken);

            fetch('<?php echo SITE_URL; ?>/api/reviews.php', { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(data) { if (data.success) location.reload(); else alert(data.message); })
            .catch(function(err) { console.error('Delete error:', err); alert('Network error. Please try again.'); });
        });
    }

    // Wishlist Toggle with try/catch
    var wishBtn = document.querySelector('.wishlist-toggle');
    if (wishBtn) {
        wishBtn.addEventListener('click', function() {
            var fd = new FormData();
            fd.append('action', 'toggle');
            fd.append('product_id', productId);
            fd.append('csrf_token', csrfToken);

            fetch('<?php echo SITE_URL; ?>/api/wishlist.php', { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    var icon = document.getElementById('wishlistIcon');
                    if (icon) {
                        icon.className = data.in_wishlist ? 'bi bi-heart-fill' : 'bi bi-heart';
                    }
                    var wishBadge = document.getElementById('wishlistCount');
                    if (wishBadge) { wishBadge.textContent = data.count; wishBadge.style.display = data.count > 0 ? '' : 'none'; }
                } else if (data.requires_login) {
                    window.location.href = '<?php echo SITE_URL; ?>/login.php';
                } else {
                    alert(data.message);
                }
            })
            .catch(function(err) { console.error('Wishlist error:', err); alert('Network error. Please try again.'); });
        });
    }
});
</script>
