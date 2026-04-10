<?php require_once 'admin_check.php'; ?>
<?php
$stats = [
    'products' => $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn(),
    'users'    => $pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn(),
    'admins'   => $pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn()
];
$unreadNotifs = $pdo->query("SELECT COUNT(*) FROM notifications WHERE user_id IS NULL AND is_read = 0")->fetchColumn();

// Fetch all active user carts with item counts & totals
$stmt = $pdo->query("
    SELECT c.id, c.user_id, u.email, u.first_name, COUNT(ci.id) as item_count, SUM(ci.quantity * p.price) as cart_total
    FROM carts c
    JOIN users u ON c.user_id = u.id
    JOIN cart_items ci ON c.id = ci.cart_id
    JOIN products p ON ci.product_id = p.id
    WHERE c.status = 'active'
    GROUP BY c.id, c.user_id, u.email, u.first_name
    ORDER BY c.updated_at DESC
");
$activeCarts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <style>
        :root {
            --bg: #F9F2F2;
            --card: #FFFFFF;
            --accent: #800000;
            --accent-hover: #5C0000;
            --accent-light: #F4EAEA;
            --txt: #2C1810;
            --mut: #6B4C4C;
            --bdr: #E6D5D5;
            --success: #2E7D32;
            --success-bg: #E8F5E9;
            --error: #C62828;
            --error-bg: #FDECEA;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; }
        body { background: var(--bg); color: var(--txt); display: flex; min-height: 100vh; }

        /* Sidebar */
        .sidebar {
            width: 250px; background: var(--accent); border-right: 4px solid var(--accent-hover);
            padding: 1.5rem; position: fixed; height: 100vh; display: flex; flex-direction: column;
            box-shadow: 2px 0 10px rgba(128, 0, 0, 0.1);
        }
        .sidebar h2 { font-size: 1.3rem; margin-bottom: 2rem; letter-spacing: 1px; color: #FFFFFF; font-weight: 700; }
        .sidebar a {
            display: block; padding: 0.85rem 1rem; margin-bottom: 0.5rem; text-decoration: none;
            color: rgba(255, 255, 255, 0.85); border-radius: 8px; transition: all 0.2s ease; font-weight: 500;
        }
        .sidebar a:hover { background: rgba(255, 255, 255, 0.15); color: #fff; }
        .sidebar a.active { background: #FFFFFF; color: var(--accent); font-weight: 600; }
        .sidebar .logout { margin-top: auto; color: #FFD1D1; border: 1px solid rgba(255,255,255,0.3); }
        .sidebar .logout:hover { background: var(--accent-hover); color: #fff; border-color: transparent; }

        /* Main Content */
        .main { margin-left: 250px; flex: 1; padding: 2rem; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .header h1 { font-size: 1.8rem; font-weight: 700; color: var(--txt); }
        .header span { color: var(--accent); font-size: 0.95rem; background: var(--accent-light); padding: 0.4rem 0.8rem; border-radius: 20px; font-weight: 500; }

        /* Stats Cards */
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.2rem; margin-bottom: 2rem; }
        .stat {
            background: var(--card); padding: 1.5rem; border-radius: 10px; border: 1px solid var(--bdr);
            text-align: center; box-shadow: 0 2px 8px rgba(128, 0, 0, 0.04); transition: transform 0.2s;
        }
        .stat:hover { transform: translateY(-3px); }
        .stat h3 { font-size: 0.85rem; color: var(--mut); margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat p { font-size: 2.2rem; font-weight: 700; color: var(--accent); }

        /* Forms & Boxes */
        .box, .form-box {
            background: var(--card); padding: 1.5rem; border-radius: 10px; border: 1px solid var(--bdr);
            margin-bottom: 1.5rem; box-shadow: 0 2px 8px rgba(128, 0, 0, 0.04);
        }
        .box h3 { margin-bottom: 1rem; color: var(--txt); }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; }
        .fg { display: flex; flex-direction: column; gap: 0.4rem; }
        label { font-size: 0.85rem; font-weight: 500; color: var(--mut); }
        input, textarea, select {
            padding: 0.8rem; border: 1px solid var(--bdr); border-radius: 8px; background: #FCF9F9;
            font-size: 0.95rem; transition: all 0.2s; color: var(--txt);
        }
        input:focus, textarea:focus, select:focus {
            outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px rgba(128, 0, 0, 0.15);
        }

        /* Buttons */
        .btn {
            display: inline-block; padding: 0.75rem 1.5rem; background: var(--accent); color: #fff;
            text-decoration: none; border-radius: 8px; font-weight: 600; border: none; cursor: pointer;
            transition: all 0.2s; font-size: 0.95rem;
        }
        .btn:hover { background: var(--accent-hover); transform: translateY(-1px); }
        .btn-gray { background: var(--accent-light); color: var(--accent); border: 1px solid var(--bdr); }
        .btn-gray:hover { background: #EBE3E3; }
        .btn-red { background: var(--error); color: #fff; }
        .btn-red:hover { background: #A52A2A; }
        .btn-sm { padding: 0.4rem 0.9rem; font-size: 0.85rem; }

        /* Messages */
        .msg { padding: 0.85rem; border-radius: 8px; margin-bottom: 1.2rem; text-align: center; font-weight: 500; }
        .success { background: var(--success-bg); color: var(--success); border: 1px solid #C3E6CB; }
        .error { background: var(--error-bg); color: var(--error); border: 1px solid #F5C6CB; }

        /* Tables */
        table { width: 100%; border-collapse: collapse; background: var(--card); border-radius: 10px; overflow: hidden; border: 1px solid var(--bdr); box-shadow: 0 2px 8px rgba(128, 0, 0, 0.03); }
        th, td { padding: 1rem; text-align: left; border-bottom: 1px solid var(--bdr); }
        th { background: var(--accent-light); font-weight: 600; color: var(--accent); font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background: #FCF9F9; }
        td { font-size: 0.95rem; vertical-align: middle; }
        .actions { display: flex; gap: 0.5rem; flex-wrap: wrap; }
        .empty { text-align: center; padding: 2.5rem; color: var(--mut); background: var(--accent-light); border-radius: 8px; }
        img.thumb { width: 50px; height: 50px; object-fit: cover; border-radius: 6px; background: #F0E8E8; border: 1px solid var(--bdr); }

        /* Responsive */
        @media(max-width: 768px) {
            .sidebar { width: 100%; height: auto; position: relative; flex-direction: row; align-items: center; padding: 1rem; overflow-x: auto; }
            .sidebar h2 { margin-bottom: 0; margin-right: 1rem; font-size: 1.1rem; white-space: nowrap; }
            .sidebar a { margin-bottom: 0; margin-right: 0.5rem; white-space: nowrap; }
            .main { margin-left: 0; padding: 1rem; }
            .stats { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <nav class="sidebar">
    <h2>ADMIN</h2>
    <a href="index.php" class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>"> Dashboard</a>
    <a href="products.php" class="<?= basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : '' ?>"> Products</a>
    <a href="orders.php" class="<?= basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : '' ?>"> Orders</a>
    <a href="notifications.php" class="<?= basename($_SERVER['PHP_SELF']) == 'notifications.php' ? 'active' : '' ?>">
        Notifications <?= $unreadNotifs > 0 ? "<span style='background:#C62828;color:#fff;padding:.1rem .4rem;border-radius:10px;font-size:.75rem;'>$unreadNotifs</span>" : '' ?>
    </a>
    <a href="reviews.php" class="<?= basename($_SERVER['PHP_SELF'])=='reviews.php'?'active':'' ?>">⭐ Reviews</a>
    <a href="analytics.php" class="<?= basename($_SERVER['PHP_SELF'])=='analytics.php'?'active':'' ?>">📊 Analytics</a>
    <a href="../logout.php" class="logout"> Logout</a>
</nav>

    <main class="main">
        <div class="header">
            <h1>Welcome, <?= htmlspecialchars($_SESSION['user_name']) ?></h1>
            <span style="color:var(--mut)">Admin Control Panel</span>
        </div>

        <div class="stats">
            <div class="stat"><h3>Total Products</h3><p><?= $stats['products'] ?></p></div>
            <div class="stat"><h3>Customers</h3><p><?= $stats['users'] ?></p></div>
            <div class="stat"><h3>Admins</h3><p><?= $stats['admins'] ?></p></div>
        </div>

        <div class="quick">
            <h3>Quick Actions</h3>
            <a href="products.php?action=add" class="btn">Add New Product</a>
        </div>

        <div class="box">
            <h3>🛒 Active Carts (<?= count($activeCarts) ?>)</h3>
            <?php if (count($activeCarts) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Email</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activeCarts as $cart): ?>
                            <tr>
                                <td><?= htmlspecialchars($cart['first_name']) ?></td>
                                <td><?= htmlspecialchars($cart['email']) ?></td>
                                <td><?= $cart['item_count'] ?></td>
                                <td>$<?= number_format($cart['cart_total'], 2) ?></td>
                                <td>
                                    <a href="#" class="btn btn-sm btn-gray" onclick="alert('View cart details coming soon!')">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="empty">No active carts at the moment.</p>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>