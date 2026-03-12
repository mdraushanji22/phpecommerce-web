<?php
require_once __DIR__ . '/includes/header.php';

$db = getDB();

// Get product ID
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($productId <= 0) {
    header('Location: ' . SITE_URL . '/products.php');
    exit;
}

// Get product details
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
            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/products.php?category=<?php echo $product['category_id']; ?>"><?php echo htmlspecialchars($product['category_name']); ?></a></li>
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
            <span class="badge bg-info mb-2"><?php echo htmlspecialchars($product['category_name']); ?></span>
            <h1 class="mb-3"><?php echo htmlspecialchars($product['title']); ?></h1>
            <h2 class="text-primary mb-4"><?php echo formatPrice($product['price']); ?></h2>

            <div class="mb-4">
                <?php if ($product['stock'] > 0): ?>
                <span class="badge bg-success">In Stock (<?php echo $product['stock']; ?> units)</span>
                <?php else: ?>
                <span class="badge bg-danger">Out of Stock</span>
                <?php endif; ?>
            </div>

            <p class="mb-4"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>

            <?php if ($product['stock'] > 0): ?>
            <form id="addToCartForm" method="POST" action="<?php echo SITE_URL; ?>/cart-action.php">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                
                <div class="row mb-4">
                    <div class="col-md-4">
                        <label for="quantity" class="form-label">Quantity:</label>
                        <input type="number" class="form-control" id="quantity" name="quantity" 
                               value="1" min="1" max="<?php echo $product['stock']; ?>" required>
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
