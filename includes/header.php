<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';
$cartCount = getCartCount();
$flash = getFlashMessage();
$csrfToken = generateCSRFToken();
$wishCount = isUserLoggedIn() ? getWishlistCount() : 0;
$currentLang = getCurrentLang();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $csrfToken; ?>">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' . SITE_NAME : SITE_NAME; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
</head>
<body>
    <!-- Sticky Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top" id="mainNavbar">
        <div class="container">
            <a class="navbar-brand fw-bold" href="<?php echo SITE_URL; ?>/">
                <i class="bi bi-shop"></i> <?php echo SITE_NAME; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/"><?php echo t('home', 'Home'); ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/products.php"><?php echo t('products', 'Products'); ?></a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="categoriesDropdown" role="button" data-bs-toggle="dropdown">
                            <?php echo t('categories', 'Categories'); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <?php
                            $db = getDB();
                            $stmt = $db->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name");
                            $categories = $stmt->fetchAll();
                            foreach ($categories as $cat):
                            ?>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/products.php?category=<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="<?php echo SITE_URL; ?>/cart.php">
                            <i class="bi bi-cart3"></i> <?php echo t('cart', 'Cart'); ?>
                            <?php if ($cartCount > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php echo $cartCount; ?>
                            </span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <?php if (isUserLoggedIn()): ?>
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="<?php echo SITE_URL; ?>/user/wishlist.php">
                            <i class="bi bi-heart"></i>
                            <?php if ($wishCount > 0): ?>
                            <span id="wishlistCount" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php echo $wishCount; ?>
                            </span>
                            <?php else: ?>
                            <span id="wishlistCount" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display:none;">0</span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo t('account', 'Account'); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?php echo USER_URL; ?>/dashboard.php"><i class="bi bi-speedometer2"></i> <?php echo t('dashboard', 'Dashboard'); ?></a></li>
                            <li><a class="dropdown-item" href="<?php echo USER_URL; ?>/orders.php"><i class="bi bi-bag-check"></i> <?php echo t('my_orders', 'My Orders'); ?></a></li>
                            <li><a class="dropdown-item" href="<?php echo USER_URL; ?>/returns.php"><i class="bi bi-arrow-return-left"></i> <?php echo t('my_returns', 'My Returns'); ?></a></li>
                            <li><a class="dropdown-item" href="<?php echo USER_URL; ?>/profile.php"><i class="bi bi-person"></i> <?php echo t('profile', 'Profile'); ?></a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?php echo SITE_URL; ?>/logout.php"><i class="bi bi-box-arrow-right"></i> <?php echo t('logout', 'Logout'); ?></a></li>
                        </ul>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/login.php"><i class="bi bi-box-arrow-in-right"></i> <?php echo t('login', 'Login'); ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/signup.php"><i class="bi bi-person-plus"></i> <?php echo t('sign_up', 'Sign Up'); ?></a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-globe"></i> <?php echo strtoupper($currentLang); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item change-lang <?php echo $currentLang === 'en' ? 'active' : ''; ?>" href="#" data-lang="en">English</a></li>
                            <li><a class="dropdown-item change-lang <?php echo $currentLang === 'hi' ? 'active' : ''; ?>" href="#" data-lang="hi">Hindi</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <?php if ($flash): ?>
    <div class="container mt-3">
        <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($flash['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
    <?php endif; ?>
