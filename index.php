<?php
$pageTitle = 'Home';
require_once __DIR__ . '/includes/header.php';

$db = getDB();

// Get featured products
$stmt = $db->prepare("
    SELECT * FROM products 
    WHERE status = 'active' AND stock > 0
    ORDER BY created_at DESC
    LIMIT 8
");
$stmt->execute();
$featuredProducts = $stmt->fetchAll();

// Get categories
$stmt = $db->query("SELECT * FROM categories WHERE status = 'active' ORDER BY name LIMIT 6");
$categories = $stmt->fetchAll();
?>

<!-- Hero Slider -->
<div id="heroCarousel" class="carousel slide" data-bs-ride="carousel">
    <div class="carousel-indicators">
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active"></button>
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1"></button>
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2"></button>
    </div>
    <div class="carousel-inner">
        <!-- Slide 1: Online Shopping -->
        <div class="carousel-item active">
            <div class="hero-slide" style="background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?w=1920&q=80') center/cover no-repeat; min-height: 500px;">
                <div class="container h-100">
                    <div class="row align-items-center h-100">
                        <div class="col-md-6 text-white">
                            <h1 class="display-4 fw-bold mb-3">Welcome to <?php echo SITE_NAME; ?></h1>
                            <p class="lead mb-4">Discover amazing products at unbeatable prices</p>
                            <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-light btn-lg">Shop Now</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Slide 2: Electronics -->
        <div class="carousel-item">
            <div class="hero-slide" style="background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('https://images.unsplash.com/photo-1519389950473-47ba0277781c?w=1920&q=80') center/cover no-repeat; min-height: 500px;">
                <div class="container h-100">
                    <div class="row align-items-center h-100">
                        <div class="col-md-6 text-white">
                            <h1 class="display-4 fw-bold mb-3">Latest Electronics</h1>
                            <p class="lead mb-4">Get the newest tech gadgets and devices</p>
                            <a href="<?php echo SITE_URL; ?>/products.php?category=1" class="btn btn-light btn-lg">Explore Electronics</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Slide 3: Fashion -->
        <div class="carousel-item">
            <div class="hero-slide" style="background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=1920&q=80') center/cover no-repeat; min-height: 500px;">
                <div class="container h-100">
                    <div class="row align-items-center h-100">
                        <div class="col-md-6 text-white">
                            <h1 class="display-4 fw-bold mb-3">Fashion Trends</h1>
                            <p class="lead mb-4">Stay stylish with our latest fashion collection</p>
                            <a href="<?php echo SITE_URL; ?>/products.php?category=2" class="btn btn-light btn-lg">Shop Fashion</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon"></span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon"></span>
    </button>
</div>

<!-- Categories Section -->
<div class="container my-5">
    <h2 class="text-center mb-4">Shop by Category</h2>
    <div class="row g-4">
        <?php foreach ($categories as $category): ?>
        <div class="col-md-4 col-lg-2">
            <a href="<?php echo SITE_URL; ?>/products.php?category=<?php echo $category['id']; ?>" class="text-decoration-none">
                <div class="card category-card text-center h-100">
                    <div class="card-body">
                        <i class="bi bi-tag-fill text-primary fs-1"></i>
                        <h5 class="card-title mt-3"><?php echo htmlspecialchars($category['name']); ?></h5>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Featured Products Section -->
<div class="container my-5">
    <h2 class="text-center mb-4">Featured Products</h2>
    <div class="row g-4">
        <?php foreach ($featuredProducts as $product): ?>
        <div class="col-md-6 col-lg-3">
            <div class="card product-card h-100">
                <div class="product-image">
                    <img src="<?php echo getProductImage($product); ?>" 
                         class="card-img-top" alt="<?php echo htmlspecialchars($product['title']); ?>">
                </div>
                <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($product['title']); ?></h5>
                    <p class="card-text text-muted"><?php echo substr(htmlspecialchars($product['description']), 0, 60); ?>...</p>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="h5 mb-0 text-primary"><?php echo formatPrice($product['price']); ?></span>
                        <span class="badge bg-secondary">Stock: <?php echo $product['stock']; ?></span>
                    </div>
                </div>
                <div class="card-footer bg-white border-top-0">
                    <a href="<?php echo SITE_URL; ?>/product-details.php?id=<?php echo $product['id']; ?>" 
                       class="btn btn-outline-primary btn-sm w-100">View Details</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="text-center mt-4">
        <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-primary btn-lg">View All Products</a>
    </div>
</div>

<!-- Features Section -->
<div class="bg-light py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-3 text-center">
                <i class="bi bi-truck fs-1 text-primary mb-3"></i>
                <h5>Free Shipping</h5>
                <p class="text-muted">On orders over ₹500</p>
            </div>
            <div class="col-md-3 text-center">
                <i class="bi bi-shield-check fs-1 text-primary mb-3"></i>
                <h5>Secure Payment</h5>
                <p class="text-muted">100% secure transactions</p>
            </div>
            <div class="col-md-3 text-center">
                <i class="bi bi-arrow-clockwise fs-1 text-primary mb-3"></i>
                <h5>Easy Returns</h5>
                <p class="text-muted">7 days return policy</p>
            </div>
            <div class="col-md-3 text-center">
                <i class="bi bi-headset fs-1 text-primary mb-3"></i>
                <h5>24/7 Support</h5>
                <p class="text-muted">Dedicated customer support</p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
