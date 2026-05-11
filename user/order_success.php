<?php
// ordersuccess.php
session_start();

// Prevent caching of this page (security best practice)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Include your database connection (adjust path as needed)
// This file should define a PDO instance: $pdo
require_once '../db.php';

// Determine Order ID
$orderId = null;
if (!empty($_SESSION['last_order'])) {
    $orderId = (int)$_SESSION['last_order'];
    unset($_SESSION['last_order']); // Prevent replay on refresh
} elseif (!empty($_GET['order_id']) && ctype_digit($_GET['order_id'])) {
    $orderId = (int)$_GET['order_id'];
}

$order = null;
$items = [];
$error = null;

if ($orderId) {
    try {
        // Replace 'users' with your auth table, and adjust column names as needed
        $stmt = $pdo->prepare("
            SELECT o.id, o.total, o.status, o.address, o.payment_method, 
                   o.created_at, CONCAT(u.first_name, ' ', u.last_name) AS customer_name, u.email AS customer_email
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE o.id = :id AND o.user_id = :uid
        ");
        $stmt->execute([
            ':id' => $orderId,
            ':uid' => $_SESSION['user_id'] ?? 0
        ]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($order) {
            // Fetch order items
            $itemsStmt = $pdo->prepare("
                SELECT product_name, quantity, price 
                FROM order_items 
                WHERE order_id = :id
            ");
            $itemsStmt->execute([':id' => $orderId]);
            $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $error = "Order not found or you don't have permission to view it.";
        }
    } catch (PDOException $e) {
        // In production, log $e->getMessage() instead of displaying
        $error = "A system error occurred. Please contact support if the issue persists.";
    }
} else {
    $error = "No order ID provided. Please complete checkout first.";
}

// Clear cart if it exists
if (isset($_SESSION['cart'])) {
    unset($_SESSION['cart']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Successful</title>
    <style>
        :root { --bg:#E0D4C3; --card:#F4ECE1; --accent:#A89078; --accent-h:#8F7963; --txt:#3A3532; --mut:#7A726C; --bdr:#CDBBA6; --success:#4A7C59; --danger:#C62828; }
        * { box-sizing: border-box; }
        body { font-family: system-ui, -apple-system, sans-serif; background: var(--bg); color: var(--txt); margin: 0; padding: 2rem; line-height: 1.6; }
        .container { max-width: 700px; margin: 0 auto; background: var(--card); border: 1px solid var(--bdr); border-radius: 12px; padding: 2rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .success-icon { width: 64px; height: 64px; background: #eaf1eb; color: var(--success); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 1rem; border: 1px solid var(--success); }
        h1 { text-align: center; margin: 0 0 0.5rem; font-size: 1.75rem; color: var(--txt); }
        .order-id { text-align: center; color: var(--mut); margin-bottom: 2rem; }
        table { width: 100%; border-collapse: collapse; margin: 1.5rem 0; }
        th, td { padding: 0.75rem; border-bottom: 1px solid var(--bdr); text-align: left; }
        th { background: rgba(0,0,0,0.03); font-weight: 600; color: var(--txt); }
        .total-row td { font-weight: 700; border-top: 2px solid var(--bdr); }
        .actions { display: flex; gap: 1rem; justify-content: center; margin-top: 2rem; flex-wrap: wrap; }
        .btn { padding: 0.75rem 1.5rem; border: none; border-radius: 50px; font-weight: 600; cursor: pointer; text-decoration: none; transition: 0.2s; }
        .btn-primary { background: var(--accent); color: #fff; }
        .btn-primary:hover { background: var(--accent-h); }
        .btn-outline { background: transparent; border: 1px solid var(--bdr); color: var(--mut); }
        .btn-outline:hover { border-color: var(--accent); color: var(--txt); }
        .error { background: #FDECEA; color: var(--danger); padding: 1rem; border-radius: 8px; text-align: center; border: 1px solid var(--danger); }
        .meta { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin: 1.5rem 0; }
        .meta-box { background: rgba(0,0,0,0.02); padding: 1rem; border-radius: 8px; border: 1px solid var(--bdr); }
        .meta-box h3 { margin: 0 0 0.5rem; font-size: 0.9rem; color: var(--mut); text-transform: uppercase; letter-spacing: 0.05em; }
        .meta-box p { margin: 0; }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($error): ?>
            <div class="error">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php else: ?>
            <div class="success-icon">✓</div>
            <h1>Order Placed Successfully!</h1>
            <p class="order-id">Order #<?= htmlspecialchars($order['id']) ?> &bull; <?= date('M j, Y', strtotime($order['created_at'])) ?></p>

            <div class="meta">
                <div class="meta-box">
                    <h3>Customer</h3>
                    <p><?= htmlspecialchars($order['customer_name']) ?><br><?= htmlspecialchars($order['customer_email']) ?></p>
                </div>
                <div class="meta-box">
                    <h3>Shipping To</h3>
                    <p><?= nl2br(htmlspecialchars($order['address'] ?: 'N/A')) ?></p>
                </div>
                <div class="meta-box">
                    <h3>Payment</h3>
                    <p><?= htmlspecialchars($order['payment_method'] ?: 'N/A') ?></p>
                </div>
            </div>

            <table>
                <thead>
                    <tr><th>Product</th><th>Qty</th><th>Price</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['product_name']) ?></td>
                            <td><?= (int)$item['quantity'] ?></td>
                            <td>$<?= number_format($item['price'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td colspan="2">Total</td>
                        <td>$<?= number_format($order['total'], 2) ?></td>
                    </tr>
                </tbody>
            </table>

            <p style="text-align:center; color:#64748b; margin-bottom:0;">
                A confirmation email has been sent to <?= htmlspecialchars($order['customer_email']) ?>.<br>
                You can track your order status in your account dashboard.
            </p>

            <div class="actions">
                <a href="../index.php" class="btn btn-outline">Continue Shopping</a>
                <a href="order_details.php?id=<?= $orderId ?>" class="btn btn-primary">View Order Details</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>