<?php
$pageTitle = 'Payment Failed';
require_once __DIR__ . '/includes/header.php';

requireUserLogin();
?>

<div class="container my-5 text-center">
    <div class="card shadow-sm">
        <div class="card-body p-5">
            <i class="bi bi-x-circle-fill text-danger" style="font-size: 80px;"></i>
            <h2 class="mt-4 text-danger">Payment Failed</h2>
            <p class="lead mt-3">Unfortunately, your payment could not be processed.</p>
            <p class="text-muted">Please try again or choose a different payment method.</p>
            
            <div class="mt-4">
                <a href="<?php echo SITE_URL; ?>/checkout.php" class="btn btn-primary me-2">
                    <i class="bi bi-arrow-repeat"></i> Try Again
                </a>
                <a href="<?php echo SITE_URL; ?>/cart.php" class="btn btn-outline-primary">
                    <i class="bi bi-cart3"></i> View Cart
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
