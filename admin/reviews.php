<?php require_once 'admin_check.php'; ?>
<?php
$unreadNotifs = $pdo->query("SELECT COUNT(*) FROM notifications WHERE user_id IS NULL AND is_read = 0")->fetchColumn();

// Handle Delete
if(isset($_GET['delete']) && $_GET['delete']) {
    $pdo->prepare("DELETE FROM reviews WHERE id = ?")->execute([$_GET['delete']]);
    header("Location: reviews.php"); exit;
}
// Fetch reviews
$reviews = $pdo->query("
    SELECT r.id, r.rating, r.comment, r.created_at, r.is_approved, 
           u.first_name, u.email, p.name as product_name
    FROM reviews r 
    JOIN users u ON r.user_id = u.id 
    JOIN products p ON r.product_id = p.id 
    ORDER BY r.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviews | Admin</title>
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
        .header span { color: var(--mut); font-size: 0.95rem; background: var(--accent-light); padding: 0.4rem 0.8rem; border-radius: 20px; font-weight: 500; }

        /* Tables */
        table { width:100%;border-collapse:collapse;background:var(--card);border-radius:8px;overflow:hidden;border:1px solid var(--bdr);}
        th,td{padding:.9rem;text-align:left;border-bottom:1px solid var(--bdr);}
        th{background:var(--accent-light);color:var(--accent);font-size:.8rem;text-transform:uppercase;}
        .stars{color:#F4B400; letter-spacing:2px;}
        .btn-del{background:#EF4444;color:#fff;padding:.3rem .6rem;border:none;border-radius:4px;cursor:pointer;font-size:.8rem;}
        .btn-del:hover{background:#DC2626;}
        .empty{text-align:center;padding:2rem;color:var(--mut);background:var(--card);border-radius:8px;}

        /* Responsive */
        @media(max-width: 768px) {
            .sidebar { width: 100%; height: auto; position: relative; flex-direction: row; align-items: center; padding: 1rem; overflow-x: auto; }
            .sidebar h2 { margin-bottom: 0; margin-right: 1rem; font-size: 1.1rem; white-space: nowrap; }
            .sidebar a { margin-bottom: 0; margin-right: 0.5rem; white-space: nowrap; }
            .main { margin-left: 0; padding: 1rem; }
        }
    </style>
</head>
<body>
    <nav class="sidebar">
        <h2>ADMIN</h2>
        <a href="index.php" class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">Dashboard</a>
        <a href="products.php" class="<?= basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : '' ?>">Products</a>
        <a href="orders.php" class="<?= basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : '' ?>">Orders</a>
        <a href="notifications.php" class="<?= basename($_SERVER['PHP_SELF']) == 'notifications.php' ? 'active' : '' ?>">
            Notifications <?= $unreadNotifs > 0 ? "<span style='background:#C62828;color:#fff;padding:.1rem .4rem;border-radius:10px;font-size:.75rem;'>$unreadNotifs</span>" : '' ?>
        </a>
        <a href="reviews.php" class="<?= basename($_SERVER['PHP_SELF']) == 'reviews.php' ? 'active' : '' ?>">⭐ Reviews</a>
        <a href="../logout.php" class="logout">Logout</a>
    </nav>

    <main class="main">
        <div class="header">
            <h1>⭐ Customer Reviews</h1>
            <span style="color:var(--mut);">Manage customer feedback</span>
        </div>
        <?php if(empty($reviews)): ?>
            <p class="empty">No reviews yet.</p>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table>
                    <thead><tr><th>Product</th><th>Customer</th><th>Rating</th><th>Review</th><th>Date</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php foreach($reviews as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['product_name']) ?></td>
                            <td><?= htmlspecialchars($r['first_name']) ?><br><small style="color:var(--mut)"><?= htmlspecialchars($r['email']) ?></small></td>
                            <td class="stars"><?= str_repeat('★', $r['rating']) . str_repeat('☆', 5-$r['rating']) ?></td>
                            <td><?= nl2br(htmlspecialchars(substr($r['comment'], 0, 80))) . (strlen($r['comment'])>80?'...':'') ?></td>
                            <td><?= date('M d, Y', strtotime($r['created_at'])) ?></td>
                            <td><a href="?delete=<?= $r['id'] ?>" class="btn-del" onclick="return confirm('Delete this review?');">Delete</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>