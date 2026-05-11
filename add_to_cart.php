<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    // Store redirect target safely
    $target = $_GET['redirect'] ?? 'index.php';
    $safeTarget = basename($target); // Prevent open redirect attacks
    header("Location: login.php?redirect=" . urlencode($safeTarget));
    exit;
}

$userId = $_SESSION['user_id'];
$productId = intval($_GET['id'] ?? 0);
if ($productId <= 0) { header("Location: index.php"); exit; }

try {
    // Check product stock
    $stmt = $pdo->prepare("SELECT stock_quantity FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();

    if (!$product || $product['stock_quantity'] <= 0) {
        // 🚫 Out of stock → Add to wishlist
        $pdo->prepare("INSERT IGNORE INTO wishlist (user_id, product_id) VALUES (?, ?)")
            ->execute([$userId, $productId]);
        $redirect = "user/wishlist.php?msg=out_of_stock";
    } else {
        // ✅ In stock → Add to cart
        $stmt = $pdo->prepare("SELECT id FROM carts WHERE user_id = ? AND status = 'active' LIMIT 1");
        $stmt->execute([$userId]);
        $cart = $stmt->fetch();

        if (!$cart) {
            $pdo->prepare("INSERT INTO carts (user_id) VALUES (?)")->execute([$userId]);
            $cartId = $pdo->lastInsertId();
        } else {
            $cartId = $cart['id'];
        }

        $pdo->prepare("
            INSERT INTO cart_items (cart_id, product_id, quantity) 
            VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE quantity = quantity + 1
        ")->execute([$cartId, $productId]);
        
        $redirect = $_SERVER['HTTP_REFERER'] ?? "index.php";
    }
} catch (Exception $e) {
    error_log("Cart/Wishlist Error: " . $e->getMessage());
    $redirect = "index.php";
}

header("Location: " . $redirect);
exit;
?>