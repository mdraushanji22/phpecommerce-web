<?php
$pageTitle = 'Manage Products';
require_once __DIR__ . '/../includes/admin_header.php';

requireAdminLogin();

$db = getDB();

// Get all products
$products = $db->query("
    SELECT p.*, c.name as category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    ORDER BY p.created_at DESC
")->fetchAll();

// Get categories for dropdown
$categories = $db->query("SELECT * FROM categories WHERE status = 'active' ORDER BY name")->fetchAll();
?>

<div class="container-fluid my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Manage Products</h2>
        <a href="<?php echo ADMIN_URL; ?>/product-add.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add Product
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                        <tr>
                            <td>
                                <img src="<?php echo getProductImage($product); ?>" 
                                     style="width: 50px; height: 50px; object-fit: cover;" class="img-thumbnail">
                            </td>
                            <td><?php echo htmlspecialchars($product['title']); ?></td>
                            <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                            <td><?php echo formatPrice($product['price']); ?></td>
                            <td><?php echo $product['stock']; ?></td>
                            <td>
                                <span class="badge bg-<?php echo $product['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                    <?php echo ucfirst($product['status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?php echo ADMIN_URL; ?>/product-edit.php?id=<?php echo $product['id']; ?>" 
                                   class="btn btn-sm btn-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
