<?php
require_once 'user_check.php';
require_once '../db.php';

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT id, order_number, delivery_code, status, total, created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$userId]);
$orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History | Lumière</title>
    <style>
        :root { --bg:#E0D4C3; --card:#F4ECE1; --accent:#A89078; --accent-h:#8F7963; --txt:#3A3532; --mut:#7A726C; --bdr:#CDBBA6; }
        * { box-sizing:border-box; margin:0; padding:0; font-family:system-ui,-apple-system,sans-serif; }
        body { background:var(--bg); color:var(--txt); line-height:1.6; padding:2rem 1rem; }
        .container { max-width:1000px; margin:0 auto; }
        .header { display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem; }
        h1 { font-size:1.8rem; }
        table { width:100%; border-collapse:collapse; background:var(--card); border-radius:10px; overflow:hidden; border:1px solid var(--bdr); }
        th, td { padding:1rem; text-align:left; border-bottom:1px solid var(--bdr); }
        th { background:#FAF8F5; color:var(--mut); font-size:.85rem; text-transform:uppercase; }
        .badge { padding:.25rem .6rem; border-radius:12px; font-size:.75rem; font-weight:600; text-transform:uppercase; }
        .badge.confirmed { background:#E8F5E9; color:#2E7D32; }
        .badge.processing { background:#E3F2FD; color:#1565C0; }
        .badge.shipped { background:#FFF3E0; color:#E65100; }
        .badge.delivered { background:#F3E5F5; color:#6A1B9A; }
        .badge.cancelled { background:#FDECEA; color:#C62828; }
        .btn { background:var(--accent); color:#fff; padding:.5rem 1rem; border-radius:6px; text-decoration:none; font-weight:500; transition:.2s; display:inline-block; }
        .btn:hover { background:var(--accent-h); }
        .btn-back { background:var(--bdr); color:var(--txt); }
        .empty { text-align:center; padding:3rem; background:var(--card); border:1px dashed var(--bdr); border-radius:10px; color:var(--mut); }
        .code { font-family:monospace; background:var(--bdr); padding:.2rem .4rem; border-radius:4px; font-size:.85rem; }
        @media(max-width:768px){ table,thead,tbody,th,td,tr{display:block;} thead{display:none;} tr{margin-bottom:1rem; border:1px solid var(--bdr); border-radius:8px; padding:1rem;} td{display:flex; justify-content:space-between; padding:.5rem 0; border:none;} td::before{content:attr(data-label); font-weight:600; color:var(--mut); margin-right:1rem;} }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📦 Order History</h1>
            <a href="index.php" class="btn btn-back">← Dashboard</a>
        </div>
        <?php if(empty($orders)): ?>
            <div class="empty">
                <h2>No orders yet</h2>
                <p>Your past orders will appear here once you complete a purchase.</p>
                <a href="../index.php" class="btn" style="margin-top:1rem;">Start Shopping</a>
            </div>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table>
                    <thead><tr><th>Order #</th><th>Delivery Code</th><th>Date</th><th>Status</th><th>Total</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php foreach($orders as $o): ?>
                        <tr>
                            <td data-label="Order #"><strong><?= htmlspecialchars($o['order_number']) ?></strong></td>
                            <td data-label="Code"><span class="code"><?= htmlspecialchars($o['delivery_code']) ?></span></td>
                            <td data-label="Date"><?= date('M d, Y', strtotime($o['created_at'])) ?></td>
                            <td data-label="Status"><span class="badge <?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
                            <td data-label="Total">$<?= number_format($o['total'], 2) ?></td>
                            <td data-label="Action"><a href="order_details.php?id=<?= $o['id'] ?>" class="btn">View Details</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
