<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../login.php"); exit;
}

$userId  = $_SESSION['user_id'];
$orderId = intval($_POST['order_id'] ?? 0);

try {
    $pdo->beginTransaction();

    // 1️⃣ Verify ownership & fetch current status
    $stmt = $pdo->prepare("SELECT status FROM orders WHERE id = ? AND user_id = ? LIMIT 1");
    $stmt->execute([$orderId, $userId]);
    $order = $stmt->fetch();

    if (!$order) throw new Exception("Order not found.");

    // 🚫 STRICT CANCELLATION RULE
    if ($order['status'] === 'shipped' || $order['status'] === 'delivered') {
        throw new Exception("❌ This order has already been shipped and cannot be cancelled.");
    }
    if ($order['status'] === 'cancelled') {
        throw new Exception("❌ This order is already cancelled.");
    }
    if (!in_array($order['status'], ['confirmed', 'processing'])) {
        throw new Exception("❌ This order cannot be cancelled at this stage.");
    }

    // 2️⃣ Cancel order
    $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?")->execute([$orderId]);

    // 3️⃣ Restore stock
    $items = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
    $items->execute([$orderId]);
    foreach ($items->fetchAll() as $item) {
        $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?")
            ->execute([$item['quantity'], $item['product_id']]);
    }

    // 4️⃣ Notify Admin
    $pdo->prepare("INSERT INTO notifications (type, title, message, related_id) VALUES (?, ?, ?, ?)")
        ->execute(['order_cancelled', 'Order Cancelled', "User ID {$userId} cancelled order #{$orderId}.", $orderId]);

    $pdo->commit();
    $_SESSION['cancel_msg'] = "✅ Order cancelled successfully. Stock has been restored.";
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['cancel_msg'] = $e->getMessage();
}

header("Location: order_details.php?id=$orderId");
exit;
?>