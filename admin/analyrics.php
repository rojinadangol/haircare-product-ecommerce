<?php require_once 'admin_check.php'; ?>
<?php
// 🔹 Timeline Analytics Query (Daily Revenue)
$timelineStmt = $pdo->query("
    SELECT DATE(o.created_at) as date, SUM(oi.quantity * oi.price) as daily_revenue
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    WHERE o.status IN ('confirmed','processing','shipped','delivered')
    GROUP BY DATE(o.created_at)
    ORDER BY date ASC
");
$timelineData = $timelineStmt->fetchAll();

// 🔹 Category Analytics Query
$catStmt = $pdo->query("
    SELECT 
        p.category,
        COUNT(DISTINCT o.id) as orders,
        SUM(oi.quantity) as units,
        SUM(oi.quantity * oi.price) as revenue,
        AVG(oi.price) as avg_price
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE o.status IN ('confirmed','processing','shipped','delivered')
    GROUP BY p.category
    ORDER BY revenue DESC
");
$categoryStats = $catStmt->fetchAll();

// 🔹 Overall Stats
$overall = $pdo->query("
    SELECT 
        COUNT(DISTINCT o.id) as total_orders,
        SUM(oi.quantity) as total_units,
        SUM(oi.quantity * oi.price) as total_revenue
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    WHERE o.status IN ('confirmed','processing','shipped','delivered')
");
$totals = $overall->fetch();

// 🔹 Top Products by Category
$topProducts = [];
foreach(['shampoo','conditioner','treatment','hair oil'] as $cat) {
    $stmt = $pdo->prepare("
        SELECT p.name, SUM(oi.quantity) as sold, SUM(oi.quantity * oi.price) as rev
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        JOIN orders o ON oi.order_id = o.id
        WHERE p.category = ? AND o.status IN ('confirmed','processing','shipped','delivered')
        GROUP BY p.id ORDER BY sold DESC LIMIT 3
    ");
    $stmt->execute([$cat]);
    $topProducts[$cat] = $stmt->fetchAll();
}

// 🔹 Stock Health per Category
$stockStmt = $pdo->prepare("
    SELECT category, 
           COUNT(*) as total_products,
           SUM(CASE WHEN stock_quantity = 0 THEN 1 ELSE 0 END) as out_of_stock,
           SUM(CASE WHEN stock_quantity <= 5 AND stock_quantity > 0 THEN 1 ELSE 0 END) as low_stock
    FROM products GROUP BY category
");
$stockStmt->execute();
$stockStats = $stockStmt->fetchAll(PDO::FETCH_ASSOC);
$stockByCat = array_column($stockStats, null, 'category');

// 🔹 CSV Export Handler
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="business-analytics-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Category', 'Orders', 'Units Sold', 'Revenue', 'Avg Price']);
    foreach ($categoryStats as $row) {
        fputcsv($out, [
            ucfirst($row['category']),
            $row['orders'],
            $row['units'],
            '$' . number_format($row['revenue'], 2),
            '$' . number_format($row['avg_price'], 2)
        ]);
    }
    fclose($out);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics | Admin</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root{--bg:#F5F1EB;--card:#FFF;--accent:#800000;--accent-h:#5C0000;--txt:#2C1810;--mut:#6B4C4C;--bdr:#E6D5D5;--light:#F4EAEA;--success:#4A7C59;--warning:#E65100;--danger:#C62828;}
        *{box-sizing:border-box;margin:0;padding:0;font-family:system-ui,sans-serif;}
        body{background:var(--bg);color:var(--txt);padding:2rem;}
        .container{max-width:1200px;margin:0 auto;}
        h1{margin-bottom:1rem;color:var(--accent);}
        
        /* Header Actions */
        .header-actions{background:var(--card);padding:1rem;border-radius:8px;border:1px solid var(--bdr);margin-bottom:1.5rem;display:flex;justify-content:space-between;align-items:center;}
        .header-actions p{color:var(--mut); font-weight:500;}
        .export-btn{background:var(--success);color:#fff;padding:.5rem 1rem;border-radius:6px;text-decoration:none;font-weight:500;transition: background 0.2s;}
        .export-btn:hover{background:#3d664a;}
        
        /* Stats Grid */
        .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin-bottom:2rem;}
        .stat-card{background:var(--card);padding:1.2rem;border-radius:8px;border:1px solid var(--bdr);text-align:center;}
        .stat-card h3{font-size:.85rem;color:var(--mut);margin-bottom:.3rem;text-transform:uppercase;}
        .stat-card p{font-size:1.8rem;font-weight:700;color:var(--accent);}
        
        /* Charts */
        .charts-full{margin-bottom:1.5rem;}
        .charts{display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:2rem;}
        .chart-box{background:var(--card);padding:1.5rem;border-radius:8px;border:1px solid var(--bdr);box-shadow:0 2px 8px rgba(0,0,0,0.02);}
        .chart-box h3{margin-bottom:1rem;font-size:1rem;color:var(--txt);}
        
        /* Tables */
        table{width:100%;border-collapse:collapse;background:var(--card);border-radius:8px;overflow:hidden;border:1px solid var(--bdr);margin-bottom:1.5rem;}
        th,td{padding:.9rem;text-align:left;border-bottom:1px solid var(--bdr);}
        th{background:var(--light);color:var(--accent);font-size:.8rem;text-transform:uppercase;}
        .badge{padding:.2rem .5rem;border-radius:4px;font-size:.75rem;font-weight:600;}
        .badge.success{background:#E8F5E9;color:var(--success);}
        .badge.warning{background:#FFF3E0;color:var(--warning);}
        .badge.danger{background:#FDECEA;color:var(--danger);}
        
        /* Top Products */
        .top-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:1rem;}
        .top-card{background:var(--card);padding:1.5rem;border-radius:8px;border:1px solid var(--bdr);}
        .top-card h4{margin-bottom:.8rem;color:var(--accent);font-size:1.1rem;}
        .top-card ol{padding-left:1.2rem;}
        .top-card li{margin-bottom:.6rem;font-size:.95rem;}
        .top-card small{color:var(--mut);}
        
        @media(max-width:900px){.charts{grid-template-columns:1fr;}}
    </style>
</head>
<body>
    <?php require_once 'admin_sidebar.php'; ?>
    <main class="main">
    <div class="container">
        <h1>📊 Business Analytics</h1>
        
        <!-- Header Actions -->
        <div class="header-actions">
            <p>Showing All-Time Data</p>
            <a href="?export=csv" class="export-btn">📥 Export CSV</a>
        </div>
        
        <!-- Overall Stats -->
        <div class="stats-grid">
            <div class="stat-card"><h3>Total Orders</h3><p><?= number_format($totals['total_orders'] ?? 0) ?></p></div>
            <div class="stat-card"><h3>Units Sold</h3><p><?= number_format($totals['total_units'] ?? 0) ?></p></div>
            <div class="stat-card"><h3>Total Revenue</h3><p>$<?= number_format($totals['total_revenue'] ?? 0, 2) ?></p></div>
            <div class="stat-card"><h3>Avg Order Value</h3><p>$<?= number_format(($totals['total_revenue'] ?? 0) / max(1, $totals['total_orders']), 2) ?></p></div>
        </div>
        
        <!-- Full Width Line Chart -->
        <div class="charts-full">
            <div class="chart-box">
                <h3>Revenue Over Time (Daily)</h3>
                <canvas id="timelineChart" height="80"></canvas>
            </div>
        </div>

        <!-- Charts -->
        <div class="charts">
            <div class="chart-box">
                <h3>Revenue by Category</h3>
                <canvas id="revenueChart"></canvas>
            </div>
            <div class="chart-box">
                <h3>Units Sold by Category</h3>
                <canvas id="unitsChart"></canvas>
            </div>
        </div>
        
        <!-- Category Table -->
        <h3 style="margin:1.5rem 0 1rem;">📋 Category Performance</h3>
        <table>
            <thead><tr><th>Category</th><th>Orders</th><th>Units Sold</th><th>Revenue</th><th>Avg Price</th><th>Stock Health</th></tr></thead>
            <tbody>
                <?php foreach($categoryStats as $c): 
                    $stock = $stockByCat[$c['category']] ?? [];
                    $health = ($stock['out_of_stock'] ?? 0) > 0 ? 'danger' : (($stock['low_stock'] ?? 0) > 0 ? 'warning' : 'success');
                ?>
                <tr>
                    <td><strong><?= ucfirst($c['category']) ?></strong></td>
                    <td><?= $c['orders'] ?></td>
                    <td><?= $c['units'] ?></td>
                    <td><strong>$<?= number_format($c['revenue'], 2) ?></strong></td>
                    <td>$<?= number_format($c['avg_price'], 2) ?></td>
                    <td><span class="badge <?= $health ?>"><?= 
                        $health === 'danger' ? '⚠️ Out of Stock' : 
                        ($health === 'warning' ? '🔶 Low Stock' : '✅ Healthy') 
                    ?></span></td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($categoryStats)): ?>
                    <tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--mut);">No data available.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <!-- Top Products by Category -->
        <h3 style="margin:1.5rem 0 1rem;">🏆 Top Products by Category</h3>
        <div class="top-grid">
            <?php foreach($topProducts as $cat => $products): ?>
            <div class="top-card">
                <h4><?= ucfirst($cat) ?></h4>
                <?php if(empty($products)): ?>
                    <p style="color:var(--mut);font-size:.9rem;">No sales data available.</p>
                <?php else: ?>
                    <ol>
                        <?php foreach($products as $p): ?>
                        <li>
                            <?= htmlspecialchars($p['name']) ?>
                            <br><small>📦 <?= $p['sold'] ?> sold • 💰 $<?= number_format($p['rev'],2) ?></small>
                        </li>
                        <?php endforeach; ?>
                    </ol>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        // Set default Chart.js font
        Chart.defaults.font.family = "system-ui, sans-serif";

        // 📈 Timeline Chart (Line)
        const timelineData = <?= json_encode($timelineData) ?>;
        const timelineLabels = timelineData.map(d => d.date);
        const timelineRevenues = timelineData.map(d => parseFloat(d.daily_revenue));
        
        const timelineCtx = document.getElementById('timelineChart').getContext('2d');
        new Chart(timelineCtx, {
            type: 'line',
            data: {
                labels: timelineLabels,
                datasets: [{
                    label: 'Daily Revenue ($)',
                    data: timelineRevenues,
                    borderColor: '#800000',
                    backgroundColor: 'rgba(128, 0, 0, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4, // Smooth curve
                    pointBackgroundColor: '#800000',
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: { 
                responsive: true, 
                plugins: {
                    legend: { display: false }
                },
                scales: { 
                    y: { 
                        beginAtZero: true, 
                        ticks: { callback: v => '$' + v.toLocaleString() },
                        grid: { color: '#E6D5D5', borderDash: [5, 5] }
                    },
                    x: {
                        grid: { display: false }
                    }
                } 
            }
        });

        // 📈 Revenue Chart (Bar)
        const revCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_map(fn($c)=>ucfirst($c['category']), $categoryStats)) ?>,
                datasets: [{
                    label: 'Revenue ($)',
                    data: <?= json_encode(array_map(fn($c)=>floatval($c['revenue']), $categoryStats)) ?>,
                    backgroundColor: 'rgba(128, 0, 0, 0.8)',
                    borderColor: '#800000',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: { 
                responsive: true, 
                plugins: { legend: { display: false } },
                scales: { 
                    y: { 
                        beginAtZero: true, 
                        ticks: { callback: v => '$' + v.toLocaleString() },
                        grid: { color: '#E6D5D5' }
                    },
                    x: { grid: { display: false } }
                } 
            }
        });

        // 📦 Units Chart (Doughnut)
        const unitsCtx = document.getElementById('unitsChart').getContext('2d');
        new Chart(unitsCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_map(fn($c)=>ucfirst($c['category']), $categoryStats)) ?>,
                datasets: [{
                    data: <?= json_encode(array_map(fn($c)=>intval($c['units']), $categoryStats)) ?>,
                    backgroundColor: ['#800000','#B22222','#CD5C5C', '#E6A5A5', '#F4EAEA'],
                    borderWidth: 0
                }]
            },
            options: { 
                responsive: true, 
                plugins: { legend: { position: 'bottom' } },
                cutout: '65%' 
            }
        });
    </script>
    </main>
</body>
</html>