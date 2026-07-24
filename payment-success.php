<?php
$pageTitle = 'Payment Successful';
require_once __DIR__ . '/includes/header.php';

requireUserLogin();
?>

<div class="container my-5 text-center">
    <div class="card shadow-sm">
        <div class="card-body p-5">
            <i class="bi bi-check-circle-fill text-success" style="font-size: 80px;"></i>
            <h2 class="mt-4 text-success">Payment Successful!</h2>
            <p class="lead mt-3">Your order has been placed and payment has been confirmed.</p>
            <p class="text-muted">You will receive an email confirmation shortly.</p>
            
            <div class="mt-4">
                <a href="<?php echo USER_URL; ?>/orders.php" class="btn btn-primary me-2">
                    <i class="bi bi-bag-check"></i> View My Orders
                </a>
                <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-outline-primary">
                    <i class="bi bi-shop"></i> Continue Shopping
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
