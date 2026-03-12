<?php
$pageTitle = 'Shopping Cart';
require_once __DIR__ . '/includes/header.php';

requireUserLogin();

$db = getDB();
$userId = $_SESSION['user_id'];

// Get cart items
$stmt = $db->prepare("
    SELECT c.id as cart_id, c.quantity, p.id as product_id, p.title, p.price, p.stock, p.image
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ? AND p.status = 'active'
    ORDER BY c.created_at DESC
");
$stmt->execute([$userId]);
$cartItems = $stmt->fetchAll();

// Calculate totals
$subtotal = 0;
foreach ($cartItems as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$shipping = $subtotal > 500 ? 0 : 50;
$total = $subtotal + $shipping;
?>

<div class="container my-5">
    <h2 class="mb-4"><i class="bi bi-cart3"></i> Shopping Cart</h2>

    <?php if (count($cartItems) > 0): ?>
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Subtotal</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cartItems as $item): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo getProductImage($item); ?>" 
                                                 alt="<?php echo htmlspecialchars($item['title']); ?>" 
                                                 class="me-3" style="width: 60px; height: 60px; object-fit: cover;">
                                            <div>
                                                <a href="<?php echo SITE_URL; ?>/product-details.php?id=<?php echo $item['product_id']; ?>" 
                                                   class="text-decoration-none">
                                                    <?php echo htmlspecialchars($item['title']); ?>
                                                </a>
                                                <br>
                                                <small class="text-muted">Stock: <?php echo $item['stock']; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo formatPrice($item['price']); ?></td>
                                    <td>
                                        <form method="POST" action="<?php echo SITE_URL; ?>/cart-action.php" class="d-flex align-items-center">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                                            <input type="number" name="quantity" class="form-control form-control-sm" 
                                                   style="width: 70px;" value="<?php echo $item['quantity']; ?>" 
                                                   min="1" max="<?php echo $item['stock']; ?>" required>
                                            <button type="submit" class="btn btn-sm btn-primary ms-2">Update</button>
                                        </form>
                                    </td>
                                    <td><?php echo formatPrice($item['price'] * $item['quantity']); ?></td>
                                    <td>
                                        <form method="POST" action="<?php echo SITE_URL; ?>/cart-action.php" 
                                              onsubmit="return confirm('Remove this item from cart?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="mt-3">
                <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-outline-primary">
                    <i class="bi bi-arrow-left"></i> Continue Shopping
                </a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Order Summary</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal:</span>
                        <span><?php echo formatPrice($subtotal); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Shipping:</span>
                        <span><?php echo $shipping > 0 ? formatPrice($shipping) : 'FREE'; ?></span>
                    </div>
                    <?php if ($shipping > 0): ?>
                    <small class="text-muted">Free shipping on orders over ₹500</small>
                    <?php endif; ?>
                    <hr>
                    <div class="d-flex justify-content-between mb-3">
                        <strong>Total:</strong>
                        <strong class="text-primary"><?php echo formatPrice($total); ?></strong>
                    </div>
                    <a href="<?php echo SITE_URL; ?>/checkout.php" class="btn btn-success w-100">
                        <i class="bi bi-credit-card"></i> Proceed to Checkout
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="alert alert-info text-center">
        <i class="bi bi-cart-x fs-1"></i>
        <p class="mt-3">Your cart is empty</p>
        <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-primary">Start Shopping</a>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
