<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = t('products', 'Products');
require_once __DIR__ . '/includes/header.php';

$db = getDB();

// Get filter options
$categories = $db->query("SELECT * FROM categories WHERE status = 'active' ORDER BY name")->fetchAll();
$brands = getAllBrands();
$priceRange = getPriceRange();

// Initial search
$filters = [
    'category_id' => $_GET['category'] ?? '',
    'q' => $_GET['search'] ?? '',
];
$filters = array_filter($filters, function($v) { return $v !== '' && $v !== null; });
$result = searchProducts($filters, 1, PRODUCTS_PER_PAGE);
?>

<style>
.filter-sidebar { position: sticky; top: 70px; max-height: calc(100vh - 80px); overflow-y: auto; }
.filter-sidebar::-webkit-scrollbar { width: 4px; }
.filter-sidebar::-webkit-scrollbar-thumb { background: #ccc; border-radius: 4px; }
.star-filter label { cursor: pointer; }
.price-slider { -webkit-appearance: none; width: 100%; height: 6px; border-radius: 3px; background: #dee2e6; outline: none; }
.price-slider::-webkit-slider-thumb { -webkit-appearance: none; width: 18px; height: 18px; border-radius: 50%; background: #0d6efd; cursor: pointer; }
</style>

<div class="container my-5">
    <div class="row">
        <!-- Filter Sidebar -->
        <div class="col-md-3">
            <div class="card filter-sidebar">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-funnel"></i> <?php echo t('filter_products'); ?></h5>
                </div>
                <div class="card-body">

                    <!-- Search -->
                    <div class="mb-3">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" id="searchInput" placeholder="<?php echo t('search_products'); ?>" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                        </div>
                    </div>

                    <!-- Categories -->
                    <div class="mb-3">
                        <label class="form-label fw-bold"><?php echo t('categories'); ?></label>
                        <select class="form-select form-select-sm" id="filterCategory">
                            <option value=""><?php echo t('all_categories'); ?></option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo (isset($_GET['category']) && $_GET['category'] == $cat['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Subcategories -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Subcategory</label>
                        <select class="form-select form-select-sm" id="filterSubcategory">
                            <option value="">All Subcategories</option>
                        </select>
                    </div>

                    <!-- Brands -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Brand</label>
                        <select class="form-select form-select-sm" id="filterBrand">
                            <option value=""><?php echo t('all_brands'); ?></option>
                            <?php foreach ($brands as $brand): ?>
                            <option value="<?php echo $brand['id']; ?>"><?php echo htmlspecialchars($brand['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Price Range -->
                    <div class="mb-3">
                        <label class="form-label fw-bold"><?php echo t('price_range'); ?></label>
                        <div class="d-flex gap-2 mb-2">
                            <input type="number" class="form-control form-control-sm" id="minPrice" placeholder="Min" min="0" value="<?php echo $priceRange['min']; ?>">
                            <input type="number" class="form-control form-control-sm" id="maxPrice" placeholder="Max" min="0" value="<?php echo $priceRange['max']; ?>">
                        </div>
                        <input type="range" class="price-slider" id="priceSlider" min="<?php echo $priceRange['min']; ?>" max="<?php echo $priceRange['max']; ?>" value="<?php echo $priceRange['max']; ?>">
                    </div>

                    <!-- Rating Filter -->
                    <div class="mb-3">
                        <label class="form-label fw-bold"><?php echo t('min_rating'); ?></label>
                        <div class="star-filter">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="filterRating" id="rating<?php echo $i; ?>" value="<?php echo $i; ?>">
                                <label class="form-check-label" for="rating<?php echo $i; ?>">
                                    <span class="text-warning"><?php echo str_repeat('&#9733;', $i); ?></span> &amp; Up
                                </label>
                            </div>
                            <?php endfor; ?>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="filterRating" id="rating0" value="" checked>
                                <label class="form-check-label" for="rating0">All Ratings</label>
                            </div>
                        </div>
                    </div>

                    <!-- Availability -->
                    <div class="mb-3">
                        <label class="form-label fw-bold"><?php echo t('availability'); ?></label>
                        <select class="form-select form-select-sm" id="filterStock">
                            <option value="">All</option>
                            <option value="1"><?php echo t('in_stock'); ?></option>
                            <option value="0"><?php echo t('out_of_stock'); ?></option>
                        </select>
                    </div>

                    <!-- Special Filters -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Special</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="filterFeatured" value="1">
                            <label class="form-check-label" for="filterFeatured"><i class="bi bi-star"></i> <?php echo t('featured'); ?></label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="filterDiscounted" value="1">
                            <label class="form-check-label" for="filterDiscounted"><i class="bi bi-tags"></i> <?php echo t('discounted'); ?></label>
                        </div>
                    </div>

                    <button class="btn btn-primary btn-sm w-100" id="applyFilters">
                        <i class="bi bi-funnel"></i> Apply Filters
                    </button>
                    <button class="btn btn-outline-secondary btn-sm w-100 mt-2" id="clearFilters">
                        <i class="bi bi-x-circle"></i> Clear All
                    </button>
                </div>
            </div>
        </div>

        <!-- Products Grid -->
        <div class="col-md-9">
            <!-- Sort Bar -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0"><i class="bi bi-grid"></i> <span id="productCount"><?php echo $result['total']; ?></span> <?php echo t('results_found'); ?></h4>
                <div class="d-flex align-items-center gap-2">
                    <label class="form-label mb-0 small fw-bold"><?php echo t('sort_by'); ?>:</label>
                    <select class="form-select form-select-sm" id="sortSelect" style="width: auto;">
                        <option value="newest"><?php echo t('latest'); ?></option>
                        <option value="price_low"><?php echo t('price_low_high'); ?></option>
                        <option value="price_high"><?php echo t('price_high_low'); ?></option>
                        <option value="rating"><?php echo t('highest_rated'); ?></option>
                        <option value="best_selling"><?php echo t('best_selling'); ?></option>
                        <option value="popular"><?php echo t('most_popular'); ?></option>
                        <option value="az"><?php echo t('az'); ?></option>
                        <option value="za"><?php echo t('za'); ?></option>
                        <option value="oldest">Oldest</option>
                    </select>
                </div>
            </div>

            <!-- Products Container (AJAX loaded) -->
            <div class="row g-4" id="productsContainer">
                <?php foreach ($result['products'] as $product):
                    $imgSrc = getProductImage($product);
                    $discount = $product['discount_percent'] > 0 ? round($product['discount_percent']) : 0;
                    $dPrice = $discount > 0 ? $product['price'] * (1 - $discount / 100) : $product['price'];
                    $inWish = isUserLoggedIn() && isInWishlist($product['id']);
                ?>
                <div class="col-md-4 col-sm-6 mb-4">
                    <div class="card product-card h-100 position-relative">
                        <button class="btn wishlist-btn position-absolute top-0 end-0 m-2 p-1 border-0 bg-transparent z-1" data-product-id="<?php echo $product['id']; ?>" title="<?php echo t('add_to_wishlist'); ?>">
                            <i class="bi <?php echo $inWish ? 'bi-heart-fill text-danger' : 'bi-heart'; ?>"></i>
                        </button>
                        <?php if ($discount > 0): ?>
                        <span class="badge bg-danger position-absolute top-0 start-0 m-2 z-1">-<?php echo $discount; ?>%</span>
                        <?php endif; ?>
                        <div class="product-image">
                            <img src="<?php echo $imgSrc; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['title']); ?>">
                        </div>
                        <div class="card-body d-flex flex-column">
                            <h6 class="card-title text-truncate"><?php echo htmlspecialchars($product['title']); ?></h6>
                            <div class="mb-2"><?php echo renderStars($product['avg_rating'] ?? 0); ?> <small class="text-muted">(<?php echo $product['review_count'] ?? 0; ?>)</small></div>
                            <div class="mb-2">
                                <?php if ($discount > 0): ?>
                                <span class="text-decoration-line-through text-muted small"><?php echo formatPrice($product['price']); ?></span>
                                <?php endif; ?>
                                <span class="text-primary fw-bold"><?php echo formatPrice($dPrice); ?></span>
                            </div>
                            <div class="mt-auto">
                                <a href="<?php echo SITE_URL; ?>/product-details.php?id=<?php echo $product['id']; ?>" class="btn btn-outline-primary btn-sm w-100"><?php echo t('view_details'); ?></a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- AJAX Pagination -->
            <div id="paginationContainer">
                <?php if ($result['total_pages'] > 1): ?>
                <nav><ul class="pagination justify-content-center mt-4">
                    <?php for ($i = 1; $i <= $result['total_pages']; $i++): ?>
                    <li class="page-item <?php echo $i == 1 ? 'active' : ''; ?>"><a class="page-link search-page-link" href="#" data-page="<?php echo $i; ?>"><?php echo $i; ?></a></li>
                    <?php endfor; ?>
                </ul></nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    function getFilters() {
        return {
            q: document.getElementById('searchInput').value,
            category_id: document.getElementById('filterCategory').value,
            subcategory_id: document.getElementById('filterSubcategory').value,
            brand_id: document.getElementById('filterBrand').value,
            min_price: document.getElementById('minPrice').value,
            max_price: document.getElementById('maxPrice').value,
            min_rating: document.querySelector('input[name="filterRating"]:checked').value,
            in_stock: document.getElementById('filterStock').value,
            featured: document.getElementById('filterFeatured').checked ? '1' : '',
            discounted: document.getElementById('filterDiscounted').checked ? '1' : '',
            sort: document.getElementById('sortSelect').value,
            page: 1
        };
    }

    function loadProducts(page) {
        var filters = getFilters();
        filters.page = page;
        var params = new URLSearchParams(filters);
        // Remove empty params
        for (var key of Array.from(params.keys())) {
            if (params.get(key) === '') params.delete(key);
        }

        fetch('<?php echo SITE_URL; ?>/api/search.php?' + params.toString())
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                document.getElementById('productsContainer').innerHTML = data.html;
                document.getElementById('paginationContainer').innerHTML = data.pagination;
                document.getElementById('productCount').textContent = data.total;
                bindWishlistButtons();
                bindPageLinks();
            }
        });
    }

    // Debounce search
    var searchTimer;
    document.getElementById('searchInput').addEventListener('input', function() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function() { loadProducts(1); }, 400);
    });

    // Filter changes trigger reload
    ['filterCategory', 'filterSubcategory', 'filterBrand', 'filterStock', 'sortSelect'].forEach(function(id) {
        document.getElementById(id).addEventListener('change', function() { loadProducts(1); });
    });
    ['filterFeatured', 'filterDiscounted'].forEach(function(id) {
        document.getElementById(id).addEventListener('change', function() { loadProducts(1); });
    });
    document.querySelectorAll('input[name="filterRating"]').forEach(function(el) {
        el.addEventListener('change', function() { loadProducts(1); });
    });
    document.getElementById('priceSlider').addEventListener('input', function() {
        document.getElementById('maxPrice').value = this.value;
        loadProducts(1);
    });
    document.getElementById('minPrice').addEventListener('change', function() { loadProducts(1); });
    document.getElementById('maxPrice').addEventListener('change', function() {
        document.getElementById('priceSlider').value = this.value;
        loadProducts(1);
    });

    document.getElementById('applyFilters').addEventListener('click', function() { loadProducts(1); });
    document.getElementById('clearFilters').addEventListener('click', function() {
        document.getElementById('searchInput').value = '';
        document.getElementById('filterCategory').value = '';
        document.getElementById('filterSubcategory').value = '';
        document.getElementById('filterSubcategory').innerHTML = '<option value="">All Subcategories</option>';
        document.getElementById('filterBrand').value = '';
        document.getElementById('minPrice').value = '<?php echo $priceRange['min']; ?>';
        document.getElementById('maxPrice').value = '<?php echo $priceRange['max']; ?>';
        document.getElementById('priceSlider').value = '<?php echo $priceRange['max']; ?>';
        document.getElementById('filterStock').value = '';
        document.getElementById('filterFeatured').checked = false;
        document.getElementById('filterDiscounted').checked = false;
        document.getElementById('rating0').checked = true;
        document.getElementById('sortSelect').value = 'newest';
        loadProducts(1);
    });

    // Category change -> load subcategories
    document.getElementById('filterCategory').addEventListener('change', function() {
        var catId = this.value;
        var subSelect = document.getElementById('filterSubcategory');
        subSelect.innerHTML = '<option value="">All Subcategories</option>';
        if (catId) {
            fetch('<?php echo SITE_URL; ?>/api/search.php?action=subcategories&category_id=' + catId)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    data.subcategories.forEach(function(s) {
                        subSelect.innerHTML += '<option value="' + s.id + '">' + s.name + '</option>';
                    });
                }
            });
        }
        loadProducts(1);
    });

    // Pagination links
    function bindPageLinks() {
        document.querySelectorAll('.search-page-link').forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                loadProducts(parseInt(this.dataset.page));
            });
        });
    }
    bindPageLinks();

    // Wishlist buttons in AJAX results
    function bindWishlistButtons() {
        document.querySelectorAll('.wishlist-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var pid = this.dataset.productId;
                var icon = this.querySelector('i');
                var fd = new FormData();
                fd.append('action', 'toggle');
                fd.append('product_id', pid);
                fd.append('csrf_token', csrfToken);

                fetch('<?php echo SITE_URL; ?>/api/wishlist.php', { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success) {
                        icon.className = data.in_wishlist ? 'bi bi-heart-fill text-danger' : 'bi bi-heart';
                        var wb = document.getElementById('wishlistCount');
                        if (wb) { wb.textContent = data.count; wb.style.display = data.count > 0 ? '' : 'none'; }
                    } else if (data.requires_login) {
                        window.location.href = '<?php echo SITE_URL; ?>/login.php';
                    }
                });
            });
        });
    }
    bindWishlistButtons();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
