<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

requireUserLogin();

$pageTitle = t('my_wishlist', 'My Wishlist');
require_once __DIR__ . '/../includes/header.php';

$wishlistItems = getWishlistItems();
?>

<div class="container my-5">
    <h2 class="mb-4"><i class="bi bi-heart"></i> <?php echo t('my_wishlist', 'My Wishlist'); ?></h2>

    <?php if (count($wishlistItems) > 0): ?>
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th><?php echo t('image', 'Image'); ?></th>
                    <th><?php echo t('product', 'Product'); ?></th>
                    <th><?php echo t('price', 'Price'); ?></th>
                    <th><?php echo t('stock_status', 'Stock Status'); ?></th>
                    <th><?php echo t('actions', 'Actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($wishlistItems as $item): ?>
                <tr class="wishlist-item">
                    <td style="width: 80px;">
                        <img src="<?php echo getProductImage($item['image']); ?>" 
                             alt="<?php echo htmlspecialchars($item['title']); ?>" 
                             class="img-fluid rounded" style="max-width: 70px; max-height: 70px;">
                    </td>
                    <td>
                        <a href="<?php echo SITE_URL; ?>/product-details.php?id=<?php echo $item['product_id']; ?>" 
                           class="text-decoration-none fw-bold">
                            <?php echo htmlspecialchars($item['title']); ?>
                        </a>
                        <?php if (!empty($item['category_name'])): ?>
                        <br><small class="text-muted"><?php echo htmlspecialchars($item['category_name']); ?></small>
                        <?php endif; ?>
                    </td>
                    <td><strong><?php echo formatPrice($item['price']); ?></strong></td>
                    <td>
                        <?php if ($item['stock'] > 0 && $item['product_status'] === 'active'): ?>
                            <span class="badge bg-success"><?php echo t('in_stock', 'In Stock'); ?> (<?php echo $item['stock']; ?>)</span>
                        <?php else: ?>
                            <span class="badge bg-danger"><?php echo t('out_of_stock', 'Out of Stock'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="d-flex gap-2">
                            <?php if ($item['stock'] > 0 && $item['product_status'] === 'active'): ?>
                            <button class="btn btn-sm btn-success wishlist-move-btn" 
                                    data-action="move_to_cart" 
                                    data-product-id="<?php echo $item['product_id']; ?>">
                                <i class="bi bi-cart-plus"></i> <?php echo t('move_to_cart', 'Move to Cart'); ?>
                            </button>
                            <?php endif; ?>
                            <button class="btn btn-sm btn-danger wishlist-remove-btn" 
                                    data-action="remove" 
                                    data-product-id="<?php echo $item['product_id']; ?>">
                                <i class="bi bi-trash"></i> <?php echo t('remove', 'Remove'); ?>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="alert alert-info text-center">
        <i class="bi bi-heart fs-1"></i>
        <p class="mt-3"><?php echo t('wishlist_empty', 'Your wishlist is empty.'); ?></p>
        <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-primary"><?php echo t('start_shopping', 'Start Shopping'); ?></a>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.wishlist-move-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var productId = this.dataset.productId;
            var row = this.closest('tr') || this.closest('.card');
            var formData = new FormData();
            formData.append('action', 'move_to_cart');
            formData.append('product_id', productId);
            formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);
            
            fetch('<?php echo SITE_URL; ?>/api/wishlist.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    if (row) row.remove();
                    var cartBadge = document.getElementById('cartCount');
                    if (cartBadge && data.cart_count !== undefined) cartBadge.textContent = data.cart_count;
                    var wishBadge = document.getElementById('wishlistCount');
                    if (wishBadge) wishBadge.textContent = data.wishlist_count;
                    if (document.querySelectorAll('.wishlist-item').length <= 1) location.reload();
                    alert(data.message);
                } else {
                    alert(data.message);
                }
            });
        });
    });
    
    document.querySelectorAll('.wishlist-remove-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var productId = this.dataset.productId;
            var row = this.closest('tr') || this.closest('.card');
            var formData = new FormData();
            formData.append('action', 'remove');
            formData.append('product_id', productId);
            formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);
            
            fetch('<?php echo SITE_URL; ?>/api/wishlist.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    if (row) row.remove();
                    var wishBadge = document.getElementById('wishlistCount');
                    if (wishBadge) wishBadge.textContent = data.count;
                    if (document.querySelectorAll('.wishlist-item').length <= 1) location.reload();
                } else {
                    alert(data.message);
                }
            });
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
