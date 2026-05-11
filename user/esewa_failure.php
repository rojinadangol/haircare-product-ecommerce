<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit(); }

$user_id = (int)$_SESSION['user_id'];
$status           = strtoupper($_GET['status'] ?? 'FAILED');
$transaction_uuid = $_GET['transaction_uuid'] ?? '';

$messages = [
    'CANCELED'  => 'You cancelled the payment.',
    'PENDING'   => 'Payment is still pending. Please check your eSewa history.',
    'AMBIGUOUS' => 'Payment status is uncertain. Contact support if charged.',
    'DEFAULT'   => 'Your payment was not completed.'
];
$friendly_message = $messages[$status] ?? $messages['DEFAULT'];

// Mark order as failed & restore stock if UUID exists
if ($transaction_uuid) {
    $stmt = $pdo->prepare("SELECT id, status FROM orders WHERE esewa_transaction_uuid = ? AND user_id = ? LIMIT 1");
    $stmt->execute([$transaction_uuid, $user_id]);
    $order = $stmt->fetch();

    if ($order && $order['status'] !== 'cancelled') {
        $pdo->beginTransaction();
        try {
            $pdo->prepare("UPDATE orders SET status = 'cancelled', payment_status = 'failed' WHERE id = ?")->execute([$order['id']]);
            $items = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
            $items->execute([$order['id']]);
            foreach ($items->fetchAll() as $item) {
                $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?")
                    ->execute([$item['quantity'], $item['product_id']]);
            }
            $pdo->commit();
        } catch (Exception $e) { $pdo->rollBack(); }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payment Failed — Lumière</title>
<style>
:root { --bg:#E0D4C3; --card:#F4ECE1; --danger:#C62828; --txt:#3A3532; --mut:#7A726C; --bdr:#CDBBA6; }
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: system-ui, -apple-system, sans-serif; background: var(--bg); display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 24px; }
.box { background: var(--card); padding: 2.5rem; border-radius: 12px; border: 1px solid var(--bdr); max-width: 500px; text-align: center; box-shadow: 0 4px 20px rgba(0,0,0,.05); }
h1 { color: var(--danger); margin-bottom: 0.5rem; }
p { color: var(--mut); margin-bottom: 1rem; }
.actions { display: flex; gap: 1rem; justify-content: center; margin-top: 1.5rem; flex-wrap: wrap; }
.btn { padding: 0.8rem 1.5rem; border-radius: 50px; text-decoration: none; font-weight: 600; transition: .2s; }
.btn-primary { background: var(--danger); color: #fff; }
.btn-secondary { background: transparent; border: 1px solid var(--bdr); color: var(--txt); }
.btn-primary:hover, .btn-secondary:hover { opacity: 0.85; }
</style>
</head>
<body>
<div class="box">
    <h1>❌ Payment Failed</h1>
    <p><?= htmlspecialchars($friendly_message) ?></p>
    <p style="font-size:0.85rem;">If you were charged, contact support with your eSewa transaction ID.</p>
    <div class="actions">
        <a href="cart.php" class="btn btn-secondary">Back to Cart</a>
        <a href="index.php" class="btn btn-primary">Continue Shopping</a>
    </div>
</div>
</body>
</html>
