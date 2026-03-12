<?php
$pageTitle = 'Products';
require_once __DIR__ . '/includes/header.php';

$db = getDB();

// Get filter parameters
$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

// Build query
$where = ["p.status = 'active'"];
$params = [];

if ($categoryId > 0) {
    $where[] = "p.category_id = ?";
    $params[] = $categoryId;
}

if (!empty($search)) {
    $where[] = "(p.title LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereClause = implode(' AND ', $where);

// Get total products count
$countSql = "SELECT COUNT(*) as total FROM products p WHERE $whereClause";
$stmt = $db->prepare($countSql);
$stmt->execute($params);
$totalProducts = $stmt->fetch()['total'];

// Calculate pagination
$pagination = getPaginationData($totalProducts, $currentPage, PRODUCTS_PER_PAGE);

// Get products
$sql = "
    SELECT p.*, c.name as category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE $whereClause
    ORDER BY p.created_at DESC
    LIMIT {$pagination['items_per_page']} OFFSET {$pagination['offset']}
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get categories for filter
$categories = $db->query("SELECT * FROM categories WHERE status = 'active' ORDER BY name")->fetchAll();
?>

<div class="container my-5">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-funnel"></i> Filters</h5>
                </div>
                <div class="card-body">
                    <!-- Search -->
                    <form method="GET" action="" class="mb-4">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Search products..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                            <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
                        </div>
                    </form>

                    <!-- Categories -->
                    <h6 class="mb-3">Categories</h6>
                    <div class="list-group mb-3">
                        <a href="<?php echo SITE_URL; ?>/products.php" 
                           class="list-group-item list-group-item-action <?php echo $categoryId == 0 ? 'active' : ''; ?>">
                            All Products
                        </a>
                        <?php foreach ($categories as $cat): ?>
                        <a href="<?php echo SITE_URL; ?>/products.php?category=<?php echo $cat['id']; ?>" 
                           class="list-group-item list-group-item-action <?php echo $categoryId == $cat['id'] ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Products Grid -->
        <div class="col-md-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>
                    <?php 
                    if ($categoryId > 0) {
                        $catStmt = $db->prepare("SELECT name FROM categories WHERE id = ?");
                        $catStmt->execute([$categoryId]);
                        $catName = $catStmt->fetch();
                        echo htmlspecialchars($catName['name']);
                    } elseif (!empty($search)) {
                        echo 'Search Results for "' . htmlspecialchars($search) . '"';
                    } else {
                        echo 'All Products';
                    }
                    ?>
                </h2>
                <span class="text-muted"><?php echo $totalProducts; ?> products found</span>
            </div>

            <?php if (count($products) > 0): ?>
            <div class="row g-4">
                <?php foreach ($products as $product): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card product-card h-100">
                        <div class="product-image">
                            <img src="<?php echo getProductImage($product); ?>" 
                                 class="card-img-top" alt="<?php echo htmlspecialchars($product['title']); ?>">
                        </div>
                        <div class="card-body">
                            <span class="badge bg-info mb-2"><?php echo htmlspecialchars($product['category_name']); ?></span>
                            <h5 class="card-title"><?php echo htmlspecialchars($product['title']); ?></h5>
                            <p class="card-text text-muted"><?php echo substr(htmlspecialchars($product['description']), 0, 80); ?>...</p>
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

            <!-- Pagination -->
            <?php if ($pagination['total_pages'] > 1): ?>
            <nav class="mt-5">
                <ul class="pagination justify-content-center">
                    <?php if ($currentPage > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $currentPage - 1; ?><?php echo $categoryId ? '&category=' . $categoryId : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">Previous</a>
                    </li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                    <li class="page-item <?php echo $i == $currentPage ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo $categoryId ? '&category=' . $categoryId : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>

                    <?php if ($currentPage < $pagination['total_pages']): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $currentPage + 1; ?><?php echo $categoryId ? '&category=' . $categoryId : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">Next</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>

            <?php else: ?>
            <div class="alert alert-info text-center">
                <i class="bi bi-info-circle fs-1"></i>
                <p class="mt-3 mb-0">No products found. Try adjusting your filters.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
