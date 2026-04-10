<?php
ob_start(); // Prevents "headers already sent" crashes
session_start();
require_once '../db.php';
require_once '../config/esewa.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php'); exit;
}

$user_id = (int)$_SESSION['user_id'];
$error_message = '';
$order_id = null;
$esewa_data = [];

/* 1. DECODE & VERIFY */
if (empty($_GET['data'])) {
    $error_message = "No payment response received.";
} else {
    $decoded = base64_decode($_GET['data'], true);
    if ($decoded === false || !($esewa_data = json_decode($decoded, true))) {
        $error_message = "Invalid payment response.";
    }
}

/* 2. SIGNATURE CHECK */
if (empty($error_message)) {
    $signed = explode(',', $esewa_data['signed_field_names'] ?? '');
    $parts = [];
    foreach ($signed as $f) {
        $f = trim($f);
        if (!isset($esewa_data[$f])) { $error_message = "Missing field: $f"; break; }
        $parts[] = "{$f}={$esewa_data[$f]}";
    }
    if (empty($error_message)) {
        $msg = implode(',', $parts);
        $expected = base64_encode(hash_hmac('sha256', $msg, ESEEWA_SECRET_KEY, true));
        if (!hash_equals($expected, $esewa_data['signature'] ?? '')) {
            $error_message = "Signature verification failed.";
        }
    }
}

/* 3. STATUS & ORDER MATCH */
if (empty($error_message) && strtoupper($esewa_data['status'] ?? '') !== 'COMPLETE') {
    $error_message = "Payment not complete.";
}

if (empty($error_message)) {
    $uuid = $esewa_data['transaction_uuid'] ?? '';
    $stmt = $pdo->prepare("SELECT id, total, payment_status FROM orders WHERE esewa_transaction_uuid = ? AND user_id = ? LIMIT 1");
    $stmt->execute([$uuid, $user_id]);
    $order = $stmt->fetch();

    if (!$order) $error_message = "Order not found.";
    elseif ($order['payment_status'] === 'paid') {
        // Already processed → safe redirect
        header("Location: orders_detailed.php?id=" . (int)$order['id']);
        exit;
    }
    elseif (abs($order['total'] - (float)($esewa_data['total_amount'] ?? 0)) > 0.01) {
        $error_message = "Amount mismatch.";
    }
}

/* 4. FINALIZE ORDER */
if (empty($error_message) && $order) {
    try {
        $pdo->beginTransaction();
        $pdo->prepare("UPDATE orders SET payment_status = 'paid', status = 'confirmed', esewa_ref_id = ? WHERE id = ?")
            ->execute([$esewa_data['transaction_code'] ?? '', $order['id']]);
        
        // Clear cart
        $c = $pdo->prepare("SELECT id FROM carts WHERE user_id = ? AND status = 'active' LIMIT 1");
        $c->execute([$user_id]);
        if ($cart = $c->fetch()) {
            $pdo->prepare("DELETE FROM cart_items WHERE cart_id = ?")->execute([$cart['id']]);
        }
        $pdo->commit();
        $order_id = $order['id'];
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = "Failed to finalize order.";
    }
}

/* 5. SAFE REDIRECT OR SHOW ERROR */
if (empty($error_message) && $order_id > 0) {
    // 🔍 VERIFY FILE EXISTS BEFORE REDIRECTING
    $target = "orders_detailed.php?id=" . (int)$order_id;
    if (file_exists(__DIR__ . '/orders_detailed.php')) {
        header("Location: $target");
        exit;
    } else {
        $error_message = "Redirect file not found: orders_detailed.php";
    }
}

// Show error page if something failed
ob_end_clean();
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payment Issue</title>
<style>:root{--bg:#F5F1EB;--card:#FFF;--danger:#C62828;--bdr:#E6DFD6;}
body{background:var(--bg);display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px;font-family:system-ui,sans-serif;}
.box{background:var(--card);padding:2rem;border-radius:12px;border:1px solid var(--bdr);max-width:450px;text-align:center;}
h1{color:var(--danger);margin-bottom:.5rem;}
p{color:#7A726C;margin-bottom:1.5rem;}
.btn{background:var(--danger);color:#fff;padding:.8rem 1.5rem;border-radius:50px;text-decoration:none;font-weight:600;}</style>
</head><body><div class="box"><h1>⚠️ Payment Verification Failed</h1>
<p><?= htmlspecialchars($error_message) ?></p>
<a href="index.php" class="btn">Back to Home</a></div></body></html>