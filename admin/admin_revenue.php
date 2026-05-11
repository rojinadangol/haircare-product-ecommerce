<?php
// admin_revenue.php
session_start();

// 🔒 Basic Admin Check (Adjust to match your auth system)
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

require_once '../db.php';

// 📅 Date Range Filtering (Default: Current Month)
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate   = $_GET['end_date']   ?? date('Y-m-d');

// Ensure valid date format
if (!strtotime($startDate) || !strtotime($endDate)) {
    $startDate = date('Y-m-01');
    $endDate   = date('Y-m-d');
}

// 📊 Fetch Orders by Payment Method
$validStatuses = ['confirmed', 'shipped', 'delivered'];
$statusPlaceholders = implode(',', array_fill(0, count($validStatuses), '?'));

$paramsCash = array_merge($validStatuses, [$startDate, "{$endDate} 23:59:59"]);
$paramsEsewa = $paramsCash; // Same params for consistency

$sqlBase = "SELECT id, status, subtotal, created_at, user_id FROM orders 
            WHERE payment_method = ? AND status IN ($statusPlaceholders) 
            AND created_at BETWEEN ? AND ? ORDER BY created_at DESC";

$stmtCash = $pdo->prepare($sqlBase);
$stmtCash->execute(array_merge(['cod'], $validStatuses, [$startDate, "{$endDate} 23:59:59"]));
$cashOrders = $stmtCash->fetchAll();

$stmtEsewa = $pdo->prepare($sqlBase);
$stmtEsewa->execute(array_merge(['esewa'], $validStatuses, [$startDate, "{$endDate} 23:59:59"]));
$esewaOrders = $stmtEsewa->fetchAll();

// 💰 Calculate Totals
$cashTotal   = array_sum(array_map('floatval', array_column($cashOrders, 'subtotal')));
$esewaTotal  = array_sum(array_map('floatval', array_column($esewaOrders, 'subtotal')));
$grandTotal  = $cashTotal + $esewaTotal;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin | Revenue Dashboard</title>
    <style>
        :root { --bg: #F5F1EB; --card: #FFF; --text: #2C1810; --muted: #6B4C4C; --border: #E6D5D5; --cash: #2E7D32; --esewa: #1565C0; --primary: #800000; --primary-h: #5C0000; }
        body { font-family: system-ui, -apple-system, sans-serif; background: var(--bg); color: var(--text); margin: 0; padding: 2rem; }
        .header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 2rem; }
        h1 { margin: 0; font-size: 1.8rem; }
        .filter-form { display: flex; gap: 0.5rem; align-items: center; background: var(--card); padding: 0.75rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid var(--border); }
        .filter-form input, .filter-form button { padding: 0.5rem; border: 1px solid var(--border); border-radius: 6px; }
        .filter-form button { background: var(--primary); color: #fff; cursor: pointer; border: none; font-weight: 500; transition: background 0.2s; }
        .filter-form button:hover { background: var(--primary-h); }
        .summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .card { background: var(--card); padding: 1.5rem; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid var(--border); }
        .card h3 { margin: 0 0 0.5rem; font-size: 0.9rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.05em; }
        .amount { font-size: 1.8rem; font-weight: 700; margin: 0; }
        .cash { color: var(--cash); } .esewa { color: var(--esewa); } .total { color: var(--primary); }
        .table-section { background: var(--card); padding: 1.5rem; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        h2 { margin: 0 0 1rem; font-size: 1.25rem; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.75rem; border-bottom: 1px solid var(--border); text-align: left; }
        th { background: #f8fafc; font-weight: 600; }
        .empty { text-align: center; padding: 1.5rem; color: var(--muted); }
        .badge { display: inline-block; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem; font-weight: 500; background: #e2e8f0; }
    </style>
</head>
<body>
    <?php require_once 'admin_sidebar.php'; ?>
    <main class="main">
    <div class="header">
        <h1>💰 Revenue Dashboard</h1>
        <form method="GET" class="filter-form">
            <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" required>
            <span>to</span>
            <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>" required>
            <button type="submit">Filter</button>
        </form>
    </div>

    <div class="summary">
        <div class="card">
            <h3>💵 Cash Revenue</h3>
            <p class="amount cash">$<?= number_format($cashTotal, 2) ?></p>
        </div>
        <div class="card">
            <h3>📱 eSewa Revenue</h3>
            <p class="amount esewa">$<?= number_format($esewaTotal, 2) ?></p>
        </div>
        <div class="card">
            <h3>📈 Grand Total</h3>
            <p class="amount total">$<?= number_format($grandTotal, 2) ?></p>
        </div>
    </div>

    <div class="table-section">
        <h2>💵 Cash Orders</h2>
        <?php if (empty($cashOrders)): ?>
            <p class="empty">No cash orders found for this period.</p>
        <?php else: ?>
            <table>
                <thead><tr><th>Order ID</th><th>Date</th><th>Customer ID</th><th>Status</th><th>Amount</th></tr></thead>
                <tbody>
                    <?php foreach ($cashOrders as $o): ?>
                        <tr>
                            <td>#<?= htmlspecialchars($o['id']) ?></td>
                            <td><?= date('M j, Y g:i A', strtotime($o['created_at'])) ?></td>
                            <td><span class="badge">UID: <?= htmlspecialchars($o['user_id']) ?></span></td>
                            <td><span class="badge"><?= htmlspecialchars($o['status']) ?></span></td>
                            <td style="font-weight:600;">$<?= number_format($o['subtotal'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="table-section">
        <h2>📱 eSewa Orders</h2>
        <?php if (empty($esewaOrders)): ?>
            <p class="empty">No eSewa orders found for this period.</p>
        <?php else: ?>
            <table>
                <thead><tr><th>Order ID</th><th>Date</th><th>Customer ID</th><th>Status</th><th>Amount</th></tr></thead>
                <tbody>
                    <?php foreach ($esewaOrders as $o): ?>
                        <tr>
                            <td>#<?= htmlspecialchars($o['id']) ?></td>
                            <td><?= date('M j, Y g:i A', strtotime($o['created_at'])) ?></td>
                            <td><span class="badge">UID: <?= htmlspecialchars($o['user_id']) ?></span></td>
                            <td><span class="badge"><?= htmlspecialchars($o['status']) ?></span></td>
                            <td style="font-weight:600;">$<?= number_format($o['subtotal'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    </main>
</body>
</html>