<?php
// admin_sidebar.php

// Ensure database connection is available
if (!isset($pdo)) {
    require_once '../db.php';
}

// Fetch unread notifications count if not already fetched
if (!isset($unreadNotifs)) {
    $unreadNotifs = $pdo->query("SELECT COUNT(*) FROM notifications WHERE user_id IS NULL AND is_read = 0")->fetchColumn();
}
?>
<style>
    /* Global layout adjustment for sidebar */
    body { display: flex; min-height: 100vh; margin: 0; padding: 0; flex-direction: row; }
    
    /* Sidebar CSS */
    .sidebar {
        width: 250px; background: var(--accent, #800000); border-right: 4px solid var(--accent-hover, #5C0000);
        padding: 1.5rem; position: fixed; height: 100vh; display: flex; flex-direction: column;
        box-shadow: 2px 0 10px rgba(128, 0, 0, 0.1);
        z-index: 1000;
        top: 0;
        left: 0;
        box-sizing: border-box;
    }
    .sidebar h2 { font-size: 1.3rem; margin-bottom: 2rem; letter-spacing: 1px; color: #FFFFFF; font-weight: 700; text-transform: uppercase; margin-top: 0; }
    .sidebar a {
        display: block; padding: 0.85rem 1rem; margin-bottom: 0.5rem; text-decoration: none;
        color: rgba(255, 255, 255, 0.85); border-radius: 8px; transition: all 0.2s ease; font-weight: 500;
        font-family: system-ui, sans-serif;
    }
    .sidebar a:hover { background: rgba(255, 255, 255, 0.15); color: #fff; }
    .sidebar a.active { background: #FFFFFF; color: var(--accent, #800000); font-weight: 600; }
    .sidebar .logout { margin-top: auto; color: #FFD1D1; border: 1px solid rgba(255,255,255,0.3); }
    .sidebar .logout:hover { background: var(--accent-hover, #5C0000); color: #fff; border-color: transparent; }

    /* Main Content Wrapper */
    .main { margin-left: 250px; flex: 1; padding: 2rem; box-sizing: border-box; width: calc(100% - 250px); }

    /* Responsive CSS */
    @media(max-width: 768px) {
        body { flex-direction: column; }
        .sidebar { 
            width: 100%; height: auto; position: relative; flex-direction: row; align-items: center; 
            padding: 1rem; overflow-x: auto; border-right: none; border-bottom: 4px solid var(--accent-hover, #5C0000); 
        }
        .sidebar h2 { margin-bottom: 0; margin-right: 1rem; font-size: 1.1rem; white-space: nowrap; }
        .sidebar a { margin-bottom: 0; margin-right: 0.5rem; white-space: nowrap; }
        .sidebar .logout { margin-top: 0; margin-left: auto; }
        .main { margin-left: 0; padding: 1rem; width: 100%; }
    }
</style>

<nav class="sidebar">
    <h2>Lumière Admin</h2>
    <a href="index.php" class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">Dashboard</a>
    <a href="products.php" class="<?= basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : '' ?>">Products</a>
    <a href="orders.php" class="<?= basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : '' ?>">Orders</a>
    <a href="notifications.php" class="<?= basename($_SERVER['PHP_SELF']) == 'notifications.php' ? 'active' : '' ?>">
        Notifications <?= $unreadNotifs > 0 ? "<span style='background:#C62828;color:#fff;padding:.1rem .4rem;border-radius:10px;font-size:.75rem;'>$unreadNotifs</span>" : '' ?>
    </a>
    <a href="admin_revenue.php" class="<?= basename($_SERVER['PHP_SELF']) == 'admin_revenue.php' ? 'active' : '' ?>">Revenue</a>
    <a href="reviews.php" class="<?= basename($_SERVER['PHP_SELF']) == 'reviews.php' ? 'active' : '' ?>">Reviews</a>
    <a href="analyrics.php" class="<?= basename($_SERVER['PHP_SELF']) == 'analyrics.php' ? 'active' : '' ?>">Analytics</a>
    <a href="../logout.php" class="logout">Logout</a>
</nav>
