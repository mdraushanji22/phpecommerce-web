<?php
$pageTitle = 'Add Product';
require_once __DIR__ . '/../includes/admin_header.php';

requireAdminLogin();

$db = getDB();
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
        $imageName = '';

        // Handle image upload
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
            $result = uploadFile($_FILES['product_image'], PRODUCT_UPLOAD_DIR);
            if ($result['success']) {
                $imageName = $result['filename'];
            } else {
                $error = $result['message'];
            }
        }

        if (empty($error)) {
            // Get next ID
            $lastId = $db->query("SELECT id FROM products ORDER BY id DESC LIMIT 1")->fetchColumn();
            $nextId = $lastId ? $lastId + 1 : 1;

            // Insert product with specific ID
            $stmt = $db->prepare("
                INSERT INTO products (id, category_id, title, description, price, stock, status, image)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$nextId, $categoryId, $title, $description, $price, $stock, $status, $imageName]);

            setFlashMessage('success', 'Product added successfully');
            header('Location: ' . ADMIN_URL . '/products.php');
            exit;
        }
    }
}
?>

<div class="container-fluid my-4">
    <h2 class="mb-4">Add New Product</h2>

    <?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Product Title *</label>
                        <input type="text" class="form-control" name="title" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Category *</label>
                        <select class="form-select" name="category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="description" rows="4"></textarea>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Price *</label>
                        <input type="number" step="0.01" class="form-control" name="price" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Stock *</label>
                        <input type="number" class="form-control" name="stock" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Product Image</label>
                    <input type="file" class="form-control" name="product_image" accept="image/*">
                </div>

                <button type="submit" class="btn btn-primary">Add Product</button>
                <a href="<?php echo ADMIN_URL; ?>/products.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
