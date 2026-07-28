<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && !isUserLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

try {
    $db = getDB();
    $action = $_GET['action'] ?? $_POST['action'] ?? '';

    switch ($action) {
        case 'get_reviews':
            $productId = (int)($_GET['product_id'] ?? 0);
            if (!$productId) {
                echo json_encode(['success' => false, 'message' => 'Invalid product']);
                exit;
            }
            $reviews = getProductReviews($productId);
            $ratingInfo = getAverageRating($productId);
            $distribution = getRatingDistribution($productId);
            
            echo json_encode([
                'success' => true,
                'reviews' => $reviews,
                'avg_rating' => $ratingInfo['average'],
                'total_reviews' => $ratingInfo['total'],
                'distribution' => $distribution
            ]);
            break;

        case 'submit':
            if (!isUserLoggedIn()) {
                echo json_encode(['success' => false, 'message' => 'Please login first']);
                exit;
            }
            
            if (!verifyCSRFToken()) {
                echo json_encode(['success' => false, 'message' => 'Invalid security token']);
                exit;
            }

            $productId = (int)($_POST['product_id'] ?? 0);
            $rating = (int)($_POST['rating'] ?? 0);
            $comment = trim($_POST['comment'] ?? '');
            $userId = $_SESSION['user_id'];

            if (!$productId || $rating < 1 || $rating > 5) {
                echo json_encode(['success' => false, 'message' => 'Invalid rating']);
                exit;
            }

            if (empty($comment) || strlen($comment) < 10) {
                echo json_encode(['success' => false, 'message' => 'Review must be at least 10 characters']);
                exit;
            }

            if (strlen($comment) > 2000) {
                echo json_encode(['success' => false, 'message' => 'Review must be less than 2000 characters']);
                exit;
            }

            if (!hasUserPurchasedProduct($userId, $productId)) {
                echo json_encode(['success' => false, 'message' => 'You can only review products you have purchased']);
                exit;
            }

            $existing = hasUserReviewedProduct($userId, $productId);
            if ($existing) {
                echo json_encode(['success' => false, 'message' => 'You have already reviewed this product. You can edit your review.']);
                exit;
            }

            $db->beginTransaction();

            $stmt = $db->prepare("INSERT INTO reviews (product_id, user_id, rating, comment, status, created_at) VALUES (?, ?, ?, ?, 'active', NOW())");
            $stmt->execute([$productId, $userId, $rating, $comment]);

            // Update product average rating
            $stmt = $db->prepare("UPDATE products SET avg_rating = (SELECT COALESCE(AVG(rating), 0) FROM reviews WHERE product_id = ? AND status = 'active') WHERE id = ?");
            $stmt->execute([$productId, $productId]);

            $db->commit();

            echo json_encode(['success' => true, 'message' => 'Review submitted successfully']);
            break;

        case 'update':
            if (!isUserLoggedIn()) {
                echo json_encode(['success' => false, 'message' => 'Please login first']);
                exit;
            }

            if (!verifyCSRFToken()) {
                echo json_encode(['success' => false, 'message' => 'Invalid security token']);
                exit;
            }

            $reviewId = (int)($_POST['review_id'] ?? 0);
            $rating = (int)($_POST['rating'] ?? 0);
            $comment = trim($_POST['comment'] ?? '');
            $userId = $_SESSION['user_id'];

            if (!$reviewId || $rating < 1 || $rating > 5) {
                echo json_encode(['success' => false, 'message' => 'Invalid data']);
                exit;
            }

            if (empty($comment) || strlen($comment) < 10) {
                echo json_encode(['success' => false, 'message' => 'Review must be at least 10 characters']);
                exit;
            }

            $stmt = $db->prepare("SELECT id, product_id FROM reviews WHERE id = ? AND user_id = ?");
            $stmt->execute([$reviewId, $userId]);
            $review = $stmt->fetch();
            if (!$review) {
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                exit;
            }

            $db->beginTransaction();

            $stmt = $db->prepare("UPDATE reviews SET rating = ?, comment = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$rating, $comment, $reviewId]);

            // Update product average rating
            $stmt = $db->prepare("UPDATE products SET avg_rating = (SELECT COALESCE(AVG(rating), 0) FROM reviews WHERE product_id = ? AND status = 'active') WHERE id = ?");
            $stmt->execute([$review['product_id'], $review['product_id']]);

            $db->commit();

            echo json_encode(['success' => true, 'message' => 'Review updated successfully']);
            break;

        case 'delete':
            if (!isUserLoggedIn()) {
                echo json_encode(['success' => false, 'message' => 'Please login first']);
                exit;
            }

            if (!verifyCSRFToken()) {
                echo json_encode(['success' => false, 'message' => 'Invalid security token']);
                exit;
            }

            $reviewId = (int)($_POST['review_id'] ?? 0);
            $userId = $_SESSION['user_id'];

            $stmt = $db->prepare("SELECT id, product_id FROM reviews WHERE id = ? AND user_id = ?");
            $stmt->execute([$reviewId, $userId]);
            $review = $stmt->fetch();
            if (!$review) {
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                exit;
            }

            $db->beginTransaction();

            $stmt = $db->prepare("DELETE FROM reviews WHERE id = ?");
            $stmt->execute([$reviewId]);

            // Update product average rating
            $stmt = $db->prepare("UPDATE products SET avg_rating = (SELECT COALESCE(AVG(rating), 0) FROM reviews WHERE product_id = ? AND status = 'active') WHERE id = ?");
            $stmt->execute([$review['product_id'], $review['product_id']]);

            $db->commit();

            echo json_encode(['success' => true, 'message' => 'Review deleted successfully', 'product_id' => $review['product_id']]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Review API error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error. Please try again.']);
}
