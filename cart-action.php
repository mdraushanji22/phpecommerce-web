<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

// Require user login for cart actions
requireUserLogin();

$db = getDB();
$action = $_POST['action'] ?? '';

if ($action === 'add') {
    // Add to cart
    $productId = (int)($_POST['product_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 1);
    $userId = $_SESSION['user_id'];

    // Validate product
    $stmt = $db->prepare("SELECT id, stock FROM products WHERE id = ? AND status = 'active'");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();

    if (!$product) {
        setFlashMessage('danger', 'Product not found');
        header('Location: ' . SITE_URL . '/products.php');
        exit;
    }

    if ($product['stock'] < $quantity) {
        setFlashMessage('danger', 'Insufficient stock');
        header('Location: ' . SITE_URL . '/product-details.php?id=' . $productId);
        exit;
    }

    // Check if product already in cart
    $stmt = $db->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$userId, $productId]);
    $cartItem = $stmt->fetch();

    if ($cartItem) {
        // Update quantity
        $newQuantity = $cartItem['quantity'] + $quantity;
        if ($newQuantity > $product['stock']) {
            $newQuantity = $product['stock'];
        }
        $stmt = $db->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
        $stmt->execute([$newQuantity, $cartItem['id']]);
    } else {
        // Insert new cart item
        $stmt = $db->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $productId, $quantity]);
    }

    setFlashMessage('success', 'Product added to cart');
    header('Location: ' . SITE_URL . '/cart.php');
    exit;

} elseif ($action === 'update') {
    // Update cart quantity
    $cartId = (int)($_POST['cart_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 1);
    $userId = $_SESSION['user_id'];

    if ($quantity < 1) {
        setFlashMessage('danger', 'Invalid quantity');
        header('Location: ' . SITE_URL . '/cart.php');
        exit;
    }

    // Verify cart item belongs to user and check stock
    $stmt = $db->prepare("
        SELECT c.id, p.stock 
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.id = ? AND c.user_id = ?
    ");
    $stmt->execute([$cartId, $userId]);
    $cartItem = $stmt->fetch();

    if (!$cartItem) {
        setFlashMessage('danger', 'Cart item not found');
        header('Location: ' . SITE_URL . '/cart.php');
        exit;
    }

    if ($quantity > $cartItem['stock']) {
        $quantity = $cartItem['stock'];
        setFlashMessage('warning', 'Quantity adjusted to available stock');
    }

    $stmt = $db->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
    $stmt->execute([$quantity, $cartId]);

    setFlashMessage('success', 'Cart updated');
    header('Location: ' . SITE_URL . '/cart.php');
    exit;

} elseif ($action === 'delete') {
    // Delete from cart
    $cartId = (int)($_POST['cart_id'] ?? 0);
    $userId = $_SESSION['user_id'];

    $stmt = $db->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    $stmt->execute([$cartId, $userId]);

    setFlashMessage('success', 'Item removed from cart');
    header('Location: ' . SITE_URL . '/cart.php');
    exit;

} else {
    header('Location: ' . SITE_URL . '/cart.php');
    exit;
}
?>
