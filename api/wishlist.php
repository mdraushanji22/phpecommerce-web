<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!isUserLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please login first', 'requires_login' => true]);
    exit;
}

try {
    $db = getDB();
    $action = $_GET['action'] ?? $_POST['action'] ?? '';

    switch ($action) {
        case 'toggle':
            if (!verifyCSRFToken()) {
                echo json_encode(['success' => false, 'message' => 'Invalid security token']);
                exit;
            }

            $productId = (int)($_POST['product_id'] ?? 0);
            if (!$productId) {
                echo json_encode(['success' => false, 'message' => 'Invalid product']);
                exit;
            }

            $userId = $_SESSION['user_id'];

            $stmt = $db->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$userId, $productId]);
            $existing = $stmt->fetch();

            if ($existing) {
                $stmt = $db->prepare("DELETE FROM wishlist WHERE id = ?");
                $stmt->execute([$existing['id']]);
                $count = getWishlistCount();
                echo json_encode(['success' => true, 'action' => 'removed', 'message' => 'Removed from wishlist', 'count' => $count, 'in_wishlist' => false]);
            } else {
                $stmt = $db->prepare("INSERT INTO wishlist (user_id, product_id, created_at) VALUES (?, ?, NOW())");
                $stmt->execute([$userId, $productId]);
                $count = getWishlistCount();
                echo json_encode(['success' => true, 'action' => 'added', 'message' => 'Added to wishlist', 'count' => $count, 'in_wishlist' => true]);
            }
            break;

        case 'move_to_cart':
            if (!verifyCSRFToken()) {
                echo json_encode(['success' => false, 'message' => 'Invalid security token']);
                exit;
            }

            $productId = (int)($_POST['product_id'] ?? 0);
            $userId = $_SESSION['user_id'];

            $stmt = $db->prepare("SELECT stock, status FROM products WHERE id = ? AND status = 'active'");
            $stmt->execute([$productId]);
            $product = $stmt->fetch();
            if (!$product || $product['stock'] <= 0) {
                echo json_encode(['success' => false, 'message' => 'Product is out of stock']);
                exit;
            }

            $db->beginTransaction();

            $stmt = $db->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$userId, $productId]);
            $cartItem = $stmt->fetch();

            if ($cartItem) {
                $stmt = $db->prepare("UPDATE cart SET quantity = quantity + 1 WHERE id = ?");
                $stmt->execute([$cartItem['id']]);
            } else {
                $stmt = $db->prepare("INSERT INTO cart (user_id, product_id, quantity, created_at) VALUES (?, ?, 1, NOW())");
                $stmt->execute([$userId, $productId]);
            }

            $stmt = $db->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$userId, $productId]);

            $db->commit();

            $count = getWishlistCount();
            echo json_encode(['success' => true, 'message' => 'Moved to cart', 'wishlist_count' => $count, 'cart_count' => getCartCount()]);
            break;

        case 'remove':
            if (!verifyCSRFToken()) {
                echo json_encode(['success' => false, 'message' => 'Invalid security token']);
                exit;
            }

            $productId = (int)($_POST['product_id'] ?? 0);
            $userId = $_SESSION['user_id'];

            $stmt = $db->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$userId, $productId]);

            $count = getWishlistCount();
            echo json_encode(['success' => true, 'message' => 'Removed from wishlist', 'count' => $count]);
            break;

        case 'check':
            $productId = (int)($_GET['product_id'] ?? 0);
            $inWishlist = isInWishlist($productId);
            echo json_encode(['success' => true, 'in_wishlist' => $inWishlist]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Wishlist API error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error. Please try again.']);
}
