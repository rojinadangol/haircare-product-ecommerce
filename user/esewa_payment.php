<?php
session_start();
require_once '../db.php';
require_once '../config/esewa.php';

/* ══════════════════════════════════════════════════════
   SECURITY — must be logged in
   ═══════════════════════════════════════════════════════ */
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php'); exit();
}

$user_id = (int)$_SESSION['user_id'];
$order_id = (int)($_GET['id'] ?? 0);

/* ══════════════════════════════════════════════════════
   1. VERIFY PENDING eSewa ORDER
   ═══════════════════════════════════════════════════════ */
$stmt = $pdo->prepare("
    SELECT id, total, order_number, status, payment_status 
    FROM orders 
    WHERE id = ? AND user_id = ? AND payment_method = 'esewa' AND payment_status = 'pending' 
    LIMIT 1
");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch();

if (!$order) {
    $_SESSION['error_msg'] = "Invalid, expired, or already processed order.";
    header('Location: cart.php'); exit();
}

/* ══════════════════════════════════════════════════════
   2. PREPARE eSewa PAYLOAD
   ═══════════════════════════════════════════════════════ */
$total_amount     = $order['total'];
$transaction_uuid = $order['order_number']; // Maps 1:1 to your DB

// Generate HMAC-SHA256 Signature (Mandatory order)
$message   = "total_amount={$total_amount},transaction_uuid={$transaction_uuid},product_code=" . ESEEWA_MERCHANT_CODE;
$signature = base64_encode(hash_hmac('sha256', $message, ESEEWA_SECRET_KEY, true));

// Save UUID to DB for verification on callback
$pdo->prepare("UPDATE orders SET esewa_transaction_uuid = ? WHERE id = ?")
    ->execute([$transaction_uuid, $order_id]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pay with eSewa — Lumière</title>
<style>
:root { --bg:#E0D4C3; --card:#F4ECE1; --accent:#A89078; --accent-h:#8F7963; --txt:#3A3532; --mut:#7A726C; --bdr:#CDBBA6; }
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: system-ui, -apple-system, sans-serif; background: var(--bg); display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 24px; }
.card { background: var(--card); border-radius: 16px; box-shadow: 0 4px 32px rgba(0,0,0,.06); padding: 40px 32px; max-width: 420px; width: 100%; text-align: center; border: 1px solid var(--bdr); }
.spinner { width: 40px; height: 40px; border: 3px solid var(--bdr); border-top-color: var(--accent); border-radius: 50%; margin: 0 auto 24px; animation: spin .8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
h1 { font-size: 1.6rem; font-weight: 700; color: var(--txt); margin-bottom: 8px; }
.sub { font-size: 0.95rem; color: var(--mut); margin-bottom: 28px; line-height: 1.5; }
.amount-box { background: #FAF8F5; border: 1.5px solid var(--bdr); border-radius: 12px; padding: 18px 24px; margin-bottom: 24px; }
.amount-label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: var(--mut); margin-bottom: 6px; }
.amount-value { font-size: 2rem; font-weight: 700; color: var(--accent); }
.btn-cancel { display: inline-block; font-size: 0.85rem; color: var(--mut); text-decoration: none; margin-top: 20px; transition: color .2s; }
.btn-cancel:hover { color: var(--txt); text-decoration: underline; }
</style>
</head>
<body>
<div class="card">
    <div class="spinner"></div>
    <h1>Redirecting to eSewa</h1>
    <p class="sub">Please wait. You are being securely redirected<br>to complete your payment.</p>
    <div class="amount-box">
        <p class="amount-label">Amount to pay</p>
        <p class="amount-value">Rs <?= number_format($total_amount, 2) ?></p>
    </div>
    <p style="font-size:0.85rem; color:var(--mut); margin-bottom:20px;">
        Order: <strong><?= htmlspecialchars($transaction_uuid) ?></strong>
    </p>
    <a href="cart.php" class="btn-cancel">← Cancel & go back</a>
</div>

<!-- Hidden eSewa Form -->
<form id="esewaForm" action="<?= ESEEWA_API_URL ?>" method="POST" style="display:none;">
    <input type="hidden" name="amount"                    value="<?= htmlspecialchars($total_amount) ?>">
    <input type="hidden" name="tax_amount"                value="0">
    <input type="hidden" name="product_service_charge"    value="0">
    <input type="hidden" name="product_delivery_charge"   value="0">
    <input type="hidden" name="total_amount"              value="<?= htmlspecialchars($total_amount) ?>">
    <input type="hidden" name="transaction_uuid"          value="<?= htmlspecialchars($transaction_uuid) ?>">
    <input type="hidden" name="product_code"              value="<?= ESEEWA_MERCHANT_CODE ?>">
    <input type="hidden" name="success_url"               value="<?= ESEEWA_SUCCESS_URL ?>">
    <input type="hidden" name="failure_url"               value="<?= ESEEWA_FAILURE_URL ?>">
    <input type="hidden" name="signed_field_names"        value="total_amount,transaction_uuid,product_code">
    <input type="hidden" name="signature"                 value="<?= htmlspecialchars($signature) ?>">
</form>
<script>setTimeout(() => document.getElementById('esewaForm').submit(), 1200);</script>
</body>
</html>
