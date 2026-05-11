<?php
require_once 'user_check.php';
require_once '../db.php';

$orderId = intval($_GET['id'] ?? 0);
if ($orderId <= 0) { header("Location: orders.php"); exit; }

// 🔒 Security: Only allow owner to view
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ? LIMIT 1");
$stmt->execute([$orderId, $_SESSION['user_id']]);
$order = $stmt->fetch();
if (!$order) { header("Location: orders.php"); exit; }

$itemsStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$itemsStmt->execute([$orderId]);
$items = $itemsStmt->fetchAll();

// 📍 Tracking Timeline Logic
$statusSteps = ['confirmed', 'processing', 'shipped', 'delivered'];
$currentStep = array_search($order['status'], $statusSteps);
if ($currentStep === false) $currentStep = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details | Lumière</title>
    <style>
        :root { --bg:#E0D4C3; --card:#F4ECE1; --accent:#A89078; --accent-h:#8F7963; --txt:#3A3532; --mut:#7A726C; --bdr:#CDBBA6; --success:#4A7C59; }
        * { box-sizing:border-box; margin:0; padding:0; font-family:system-ui,-apple-system,sans-serif; }
        body { background:var(--bg); color:var(--txt); line-height:1.6; padding:2rem 1rem; }
        .container { max-width:900px; margin:0 auto; }
        .header { display:flex; justify-content:space-between; align-items:center; margin-bottom:2rem; flex-wrap:wrap; gap:1rem; }
        h1 { font-size:1.6rem; }
        .back-link { color:var(--mut); text-decoration:none; font-weight:500; }
        .back-link:hover { color:var(--txt); }

        /* Tracking Timeline */
        .tracker { background:var(--card); padding:1.5rem; border-radius:12px; border:1px solid var(--bdr); margin-bottom:1.5rem; }
        .tracker h3 { margin-bottom:1rem; font-size:1rem; color:var(--mut); }
        .steps { display:flex; justify-content:space-between; position:relative; margin-top:1.5rem; }
        .steps::before { content:''; position:absolute; top:15px; left:10%; right:10%; height:3px; background:var(--bdr); z-index:1; }
        .step { position:relative; z-index:2; text-align:center; flex:1; }
        .step .dot { width:30px; height:30px; background:var(--bdr); border-radius:50%; margin:0 auto .5rem; display:flex; align-items:center; justify-content:center; color:#fff; font-size:.8rem; transition:.3s; }
        .step.active .dot { background:var(--accent); }
        .step.completed .dot { background:var(--success); }
        .step .label { font-size:.75rem; color:var(--mut); font-weight:500; }
        .step.active .label { color:var(--accent); font-weight:600; }

        .card { background:var(--card); border:1px solid var(--bdr); border-radius:12px; padding:1.5rem; margin-bottom:1.5rem; }
        .grid { display:grid; grid-template-columns:1fr 1fr; gap:1rem; }
        .info label { display:block; font-size:.85rem; color:var(--mut); margin-bottom:.2rem; }
        .info p { font-weight:500; }
        table { width:100%; border-collapse:collapse; margin-top:1rem; }
        th, td { padding:.8rem; text-align:left; border-bottom:1px solid var(--bdr); }
        th { color:var(--mut); font-size:.85rem; background:#FAF8F5; }
        .totals { margin-top:1.5rem; border-top:1px dashed var(--bdr); padding-top:1rem; }
        .totals .row { display:flex; justify-content:space-between; margin-bottom:.4rem; }
        .totals .grand { font-weight:700; font-size:1.2rem; margin-top:.5rem; padding-top:.5rem; border-top:1px solid var(--bdr); }
        .code-box { background:var(--accent); color:#fff; padding:1rem; border-radius:8px; text-align:center; margin-top:1rem; cursor:pointer; transition:.2s; }
        .code-box:hover { opacity:.9; }
        .btn-cancel { background:#C62828; color:#fff; padding:.7rem 1.5rem; border:none; border-radius:50px; font-weight:600; cursor:pointer; transition:.2s; }
        .btn-cancel:hover { background:#A31F1F; }
        .status-notice { background:#F5F1EB; color:#7A726C; padding:.8rem 1.2rem; border-radius:8px; font-size:.9rem; border:1px solid #E6DFD6; display:inline-block; }
        @media(max-width:600px){ .grid{grid-template-columns:1fr;} .steps .label{font-size:.65rem;} }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📦 Order #<?= htmlspecialchars($order['order_number']) ?></h1>
            <a href="orders.php" class="back-link">← Back to Orders</a>
        </div>

        <!-- 📍 Tracking Timeline -->
        <div class="tracker">
            <h3>Order Tracking</h3>
            <div class="steps">
                <?php foreach($statusSteps as $idx => $step): 
                    $isActive = ($idx == $currentStep);
                    $isCompleted = ($idx < $currentStep);
                ?>
                <div class="step <?= $isActive ? 'active' : ($isCompleted ? 'completed' : '') ?>">
                    <div class="dot"><?= $isCompleted ? '✓' : ($idx + 1) ?></div>
                    <div class="label"><?= ucfirst($step) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <p style="text-align:center; margin-top:1rem; font-size:.9rem; color:var(--mut);">
                Current Status: <strong style="color:var(--accent);"><?= ucfirst($order['status']) ?></strong>
            </p>
        </div>

        <!-- 📦 Delivery Code (Tap to Copy) -->
        <div class="code-box" onclick="navigator.clipboard.writeText('<?= $order['delivery_code'] ?>'); alert('✅ Delivery code copied to clipboard!');">
            Delivery Tracking Code: <strong style="font-size:1.1rem; letter-spacing:1px;"><?= htmlspecialchars($order['delivery_code']) ?></strong>
            <div style="font-size:.75rem; margin-top:.3rem; opacity:.8;">Tap to copy</div>
        </div>

        <!--  Cancel Order Button (only for confirmed/processing orders) -->
        <div style="text-align:center; margin-top:2rem;">
            <?php if (in_array($order['status'], ['confirmed', 'processing'])): ?>
                <form method="POST" action="cancel_order.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to cancel this order? This action cannot be undone.');">
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    <button type="submit" class="btn-cancel"> Cancel Order</button>
                </form>
            <?php elseif ($order['status'] === 'shipped' || $order['status'] === 'delivered'): ?>
                <div class="status-notice"> This order has been shipped/delivered and cannot be cancelled. Please contact support for returns.</div>
            <?php else: ?>
                <div class="status-notice"> This order is <?= htmlspecialchars($order['status']) ?> and cannot be cancelled.</div>
            <?php endif; ?>
            
            <a href="orders.php" class="btn btn-back" style="margin-left:.5rem;"> Back to Orders</a>
        </div>

        <?php if (isset($_SESSION['cancel_msg'])): ?>
            <div class="msg <?= strpos($_SESSION['cancel_msg'], ' ') === 0 ? 'success' : 'error' ?>" style="margin-bottom:1.5rem; text-align:center; padding:.8rem; border-radius:8px; <?= strpos($_SESSION['cancel_msg'], ' ') === 0 ? 'background:#E8F5E9; color:#2E7D32;' : 'background:#FDECEA; color:#C62828;' ?>">
                <?= htmlspecialchars($_SESSION['cancel_msg']) ?>
            </div>
            <?php unset($_SESSION['cancel_msg']); ?>
        <?php endif; ?>

        <!-- �� Order Details -->
        <div class="card">
            <div class="grid">
                <div class="info"><label>Order Date</label><p><?= date('M d, Y • h:i A', strtotime($order['created_at'])) ?></p></div>
                <div class="info"><label>Shipping Address</label><p><?= nl2br(htmlspecialchars($order['address'])) ?></p></div>
            </div>
        </div>

        <div class="card">
            <h3 style="margin-bottom:1rem;">Order Items</h3>
            <div style="overflow-x:auto;">
                <table>
                    <thead><tr><th>Product</th><th>Qty</th><th>Price</th><th>Subtotal</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php foreach($items as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['product_name']) ?></td>
                            <td><?= $item['quantity'] ?></td>
                            <td>$<?= number_format($item['price'], 2) ?></td>
                            <td>$<?= number_format($item['quantity'] * $item['price'], 2) ?></td>
                            <td>
                                <?php if($order['status'] === 'delivered'): ?>
                                    <a href="write_review.php?order_id=<?= $order['id'] ?>&product_id=<?= $item['product_id'] ?>" class="btn" style="padding:.4rem .8rem; font-size:.8rem;">⭐ Review</a>
                                <?php else: ?>
                                    <span style="color:var(--mut); font-size:.8rem;">Deliver to review</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="totals">
                <div class="row"><span>Subtotal</span><span>$<?= number_format($order['subtotal'], 2) ?></span></div>
                <div class="row"><span>Tax (8%)</span><span>$<?= number_format($order['tax'], 2) ?></span></div>
                <div class="row"><span>Shipping</span><span><?= $order['shipping'] == 0 ? 'FREE' : '$'.number_format($order['shipping'], 2) ?></span></div>
                <div class="row grand"><span>Total Paid</span><span>$<?= number_format($order['total'], 2) ?></span></div>
            </div>
        </div>
    </div>
</body>
</html>
