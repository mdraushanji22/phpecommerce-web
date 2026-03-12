<?php
$pageTitle = 'Edit Product';
require_once __DIR__ . '/../includes/admin_header.php';

requireAdminLogin();

$db = getDB();
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get product
$stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: ' . ADMIN_URL . '/products.php');
    exit;
}

$categories = $db->query("SELECT * FROM categories WHERE status = 'active' ORDER BY name")->fetchAll();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoryId = (int)$_POST['category_id'];
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $price = (float)$_POST['price'];
    $stock = (int)$_POST['stock'];
    $status = sanitize($_POST['status']);

    if (empty($title) || $categoryId <= 0 || $price <= 0) {
        $error = 'Please fill all required fields';
    } else {
        $imageName = $product['image'] ?? ''; // Default to current image

        // Handle image upload
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
            $result = uploadFile($_FILES['product_image'], PRODUCT_UPLOAD_DIR);
            if ($result['success']) {
                $imageName = $result['filename'];

                // Delete old image file if it exists
                if (!empty($product['image'] ?? '')) {
                    deleteFile(PRODUCT_UPLOAD_DIR . $product['image']);
                }
            } else {
                $error = $result['message'];
            }
        }

        if (empty($error)) {
            // Update product
            $stmt = $db->prepare("
                UPDATE products 
                SET category_id = ?, title = ?, description = ?, price = ?, stock = ?, status = ?, image = ?
                WHERE id = ?
            ");
            $stmt->execute([$categoryId, $title, $description, $price, $stock, $status, $imageName, $productId]);

            setFlashMessage('success', 'Product updated successfully');
            header('Location: ' . ADMIN_URL . '/products.php');
            exit;
        }
    }
}
?>

<div class="container-fluid my-4">
    <h2 class="mb-4">Edit Product</h2>

    <?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Product Title *</label>
                        <input type="text" class="form-control" name="title" value="<?php echo htmlspecialchars($product['title']); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Category *</label>
                        <select class="form-select" name="category_id" required>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $cat['id'] == $product['category_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="description" rows="4"><?php echo htmlspecialchars($product['description']); ?></textarea>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Price *</label>
                        <input type="number" step="0.01" class="form-control" name="price" value="<?php echo $product['price']; ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Stock *</label>
                        <input type="number" class="form-control" name="stock" value="<?php echo $product['stock']; ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="active" <?php echo $product['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $product['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Product Image</label>
                    <div class="mb-2">
                        <img src="<?php echo getProductImage($product); ?>" 
                             style="width: 150px; height: 150px; object-fit: cover;" class="img-thumbnail">
                    </div>
                    <input type="file" class="form-control" name="product_image" accept="image/*">
                    <small class="text-muted">Upload a new image to replace the current one.</small>
                </div>

                <button type="submit" class="btn btn-primary">Update Product</button>
                <a href="<?php echo ADMIN_URL; ?>/products.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
