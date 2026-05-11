<?php require_once 'admin_check.php'; ?>
<?php
// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'], $_POST['order_id'])) {
    $newStatus = $_POST['status'];
    $orderId   = intval($_POST['order_id']);

    try {
        $pdo->beginTransaction();
        
        // 1 Get user ID before update
        $stmt = $pdo->prepare("SELECT user_id FROM orders WHERE id = ? LIMIT 1");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();

        if ($order) {
            // 2 Update status
            $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$newStatus, $orderId]);

            // 3 Notify User
            $msg = "Your order status has been updated to: <strong>" . ucfirst($newStatus) . "</strong>";
            $pdo->prepare("INSERT INTO notifications (type, title, message, related_id, user_id) VALUES (?, ?, ?, ?, ?)")
                ->execute(['order_status_update', 'Order Status Updated', $msg, $orderId, $order['user_id']]);
        }
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Admin Status Update Error: " . $e->getMessage());
    }

    header("Location: orders.php?status=" . urlencode($newStatus));
    exit;
}

// Filters
$statusFilter = $_GET['status'] ?? 'all';
$search = trim($_GET['q'] ?? '');

$sql = "SELECT o.*, u.email, u.first_name FROM orders o JOIN users u ON o.user_id = u.id";
$where = [];
$params = [];

if ($statusFilter !== 'all') {
    $where[] = "o.status = ?";
    $params[] = $statusFilter;
}
if ($search !== '') {
    $like = "%$search%";
    $where[] = "(o.order_number LIKE ? OR o.delivery_code LIKE ? OR u.email LIKE ? OR u.first_name LIKE ?)";
    $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
}

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY o.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Status counts for tabs
$counts = [
    'all' => $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'confirmed' => $pdo->query("SELECT COUNT(*) FROM orders WHERE status='confirmed'")->fetchColumn(),
    'processing' => $pdo->query("SELECT COUNT(*) FROM orders WHERE status='processing'")->fetchColumn(),
    'shipped' => $pdo->query("SELECT COUNT(*) FROM orders WHERE status='shipped'")->fetchColumn(),
    'delivered' => $pdo->query("SELECT COUNT(*) FROM orders WHERE status='delivered'")->fetchColumn()
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders | Admin</title>
    <style>
        :root{--bg:#F5F1EB;--card:#FFF;--accent:#800000;--accent-h:#5C0000;--txt:#2C1810;--mut:#6B4C4C;--bdr:#E6D5D5;--light:#F4EAEA;}
        *{box-sizing:border-box;margin:0;padding:0;font-family:system-ui,sans-serif;}
        body{background:var(--bg);color:var(--txt);padding:2rem;}
        .container{max-width:1200px;margin:0 auto;}
        h1{margin-bottom:1rem;color:var(--accent);font-size:1.6rem;}
        
        /* Tabs */
        .tabs{display:flex;gap:.5rem;margin-bottom:1.5rem;flex-wrap:wrap;}
        .tab{padding:.5rem 1rem;border-radius:20px;text-decoration:none;color:var(--mut);background:var(--card);border:1px solid var(--bdr);font-size:.9rem;transition:.2s;}
        .tab:hover{border-color:var(--accent);}
        .tab.active{background:var(--accent);color:#fff;border-color:var(--accent);}
        .tab span{background:rgba(255,255,255,.2);padding:1px 6px;border-radius:10px;font-size:.8rem;margin-left:4px;}

        .search-box{margin-bottom:1.5rem;display:flex;gap:.5rem;}
        .search-box input{padding:.6rem;border:1px solid var(--bdr);border-radius:6px;flex:1;max-width:350px;}
        
        table{width:100%;border-collapse:collapse;background:var(--card);border-radius:8px;overflow:hidden;border:1px solid var(--bdr);}
        th,td{padding:.9rem;text-align:left;border-bottom:1px solid var(--bdr);}
        th{background:var(--light);color:var(--accent);font-size:.8rem;text-transform:uppercase;letter-spacing:.5px;}
        select{padding:.4rem;border:1px solid var(--bdr);border-radius:5px;background:#fff;cursor:pointer;}
        .btn{background:var(--accent);color:#fff;padding:.4rem .7rem;border:none;border-radius:5px;cursor:pointer;font-size:.85rem;}
        .btn:hover{background:var(--accent-h);}
        .dlv-code{font-family:monospace;background:var(--light);padding:.2rem .4rem;border-radius:4px;font-size:.85rem;font-weight:600;}
        
        /* Status Badges */
        .badge{display:inline-block;padding:.25rem .6rem;border-radius:12px;font-size:.75rem;font-weight:600;text-transform:uppercase;}
        .badge.confirmed{background:#E8F5E9;color:#2E7D32;}
        .badge.processing{background:#E3F2FD;color:#1565C0;}
        .badge.shipped{background:#FFF3E0;color:#E65100;}
        .badge.delivered{background:#F3E5F5;color:#6A1B9A;}
        .badge.pending{background:#F5F5F5;color:#616161;}
        .badge.paid{background:#dcfce7;color:#16a34a;}
        .badge.failed{background:#fee2e2;color:#dc2626;}

        .empty{text-align:center;padding:2.5rem;color:var(--mut);background:var(--card);border-radius:8px;}
    </style>
</head>
<body>
    <?php require_once 'admin_sidebar.php'; ?>
    <main class="main">
    <div class="container">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
            <h1>📦 Order Management</h1>

        </div>
        
        <!-- Status Tabs -->
        <div class="tabs">
            <a href="?status=all&q=<?= urlencode($search) ?>" class="tab <?= $statusFilter=='all'?'active':'' ?>">All <span><?= $counts['all'] ?></span></a>
            <a href="?status=confirmed&q=<?= urlencode($search) ?>" class="tab <?= $statusFilter=='confirmed'?'active':'' ?>">Confirmed <span><?= $counts['confirmed'] ?></span></a>
            <a href="?status=processing&q=<?= urlencode($search) ?>" class="tab <?= $statusFilter=='processing'?'active':'' ?>">Processing <span><?= $counts['processing'] ?></span></a>
            <a href="?status=shipped&q=<?= urlencode($search) ?>" class="tab <?= $statusFilter=='shipped'?'active':'' ?>">Shipped <span><?= $counts['shipped'] ?></span></a>
            <a href="?status=delivered&q=<?= urlencode($search) ?>" class="tab <?= $statusFilter=='delivered'?'active':'' ?>">Delivered <span><?= $counts['delivered'] ?></span></a>
        </div>

        <form class="search-box" method="GET">
            <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
            <input type="text" name="q" placeholder="Search Order #, Delivery Code, or Email..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn">Search</button>
        </form>

        <?php if(empty($orders)): ?>
            <p class="empty">No orders found.</p>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table>
                    <thead><tr><th>Order #</th><th>Delivery Code</th><th>Customer</th><th>Total</th><th>Status</th><th>Payment</th><th>Date</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php foreach($orders as $o): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($o['order_number']) ?></strong></td>
                            <td><span class="dlv-code"><?= htmlspecialchars($o['delivery_code']) ?></span></td>
                            <td><?= htmlspecialchars($o['first_name']) ?><br><small style="color:var(--mut)"><?= htmlspecialchars($o['email']) ?></small></td>
                            <td>$<?= number_format($o['total'],2) ?></td>
                            <td><span class="badge <?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
                            <td>
                                <strong><?= strtoupper($o['payment_method']) ?></strong><br>
                                <span class="badge <?= $o['payment_status'] ?>"><?= ucfirst($o['payment_status'] == 'pending' ? 'Unpaid' : $o['payment_status']) ?></span>
                            </td>
                            <td><?= date('M d, Y', strtotime($o['created_at'])) ?></td>
                            <td>
                                <form method="POST" style="display:flex;gap:.3rem;">
                                    <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                    <select name="status">
                                        <option value="confirmed" <?= $o['status']=='confirmed'?'selected':'' ?>>Confirmed</option>
                                        <option value="processing" <?= $o['status']=='processing'?'selected':'' ?>>Processing</option>
                                        <option value="shipped" <?= $o['status']=='shipped'?'selected':'' ?>>Shipped</option>
                                        <option value="delivered" <?= $o['status']=='delivered'?'selected':'' ?>>Delivered</option>
                                    </select>
                                    <button type="submit" name="update_status" class="btn">Update</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    </main>
</body>
</html>