<?php 
require_once 'user_check.php';
require_once '../db.php';

$userId   = $_SESSION['user_id'];
$userName = htmlspecialchars($_SESSION['user_name'] ?? 'User');

$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([$_SESSION['user_id']]);
$unreadCount = $stmt->fetchColumn();

// Quick cart count preview
$stmt = $pdo->prepare("SELECT c.id FROM carts c WHERE c.user_id = ? AND c.status = 'active' LIMIT 1");
$stmt->execute([$userId]);
$cart = $stmt->fetch();

$cartCount = 0;
if ($cart) {
    $qtyStmt = $pdo->prepare("SELECT COALESCE(SUM(quantity), 0) FROM cart_items WHERE cart_id = ?");
    $qtyStmt->execute([$cart['id']]);
    $cartCount = (int) $qtyStmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account | Lumière</title>
    <style>
        :root { --bg:#E0D4C3; --card:#F4ECE1; --accent:#A89078; --accent-h:#8F7963; --txt:#3A3532; --mut:#7A726C; --bdr:#CDBBA6; }
        * { box-sizing:border-box; margin:0; padding:0; font-family:system-ui,-apple-system,sans-serif; }
        body { background:var(--bg); color:var(--txt); min-height:100vh; display:flex; flex-direction:column; }
        
        /* Top Header */
        header { background:var(--card); padding:.8rem 5%; border-bottom:1px solid var(--bdr); }
        .top-nav { width:100%; margin:0 auto; display:flex; justify-content:space-between; align-items:center; }
        .logo { font-size:1.4rem; font-weight:700; letter-spacing:1px; color:var(--txt); text-decoration:none; }
        .top-links a { text-decoration:none; color:var(--mut); margin-left:1.5rem; font-weight:500; font-size:.9rem; }
        .top-links a:hover { color:var(--txt); }
        .btn-sm { background:var(--accent); color:#fff; padding:.4rem 1rem; border-radius:50px; text-decoration:none; font-weight:500; font-size:.85rem; }
        
        /* Dashboard Layout */
        .dash-wrapper { display:flex; width:100%; margin:2rem 0; padding:0 5%; gap:2rem; flex:1; box-sizing:border-box; }
        
        /* Sidebar */
        .sidebar { width:240px; background:var(--card); border:1px solid var(--bdr); border-radius:12px; padding:1.5rem; height:fit-content; position:sticky; top:2rem; }
        .sidebar h3 { font-size:1rem; color:var(--mut); margin-bottom:1rem; text-transform:uppercase; letter-spacing:.5px; }
        .sidebar a { display:flex; align-items:center; gap:.6rem; padding:.75rem 1rem; margin-bottom:.4rem; text-decoration:none; color:var(--txt); border-radius:8px; font-weight:500; transition:.2s; }
        .sidebar a:hover, .sidebar a.active { background:var(--accent-light, #F9F5F0); color:var(--accent); }
        .sidebar .badge { background:var(--accent); color:#fff; font-size:.75rem; padding:.1rem .45rem; border-radius:10px; margin-left:auto; }
        .sidebar .logout { margin-top:1.5rem; color:var(--mut); border-top:1px solid var(--bdr); padding-top:1rem; }
        
        /* Main Content */
        .content { flex:1; }
        .welcome { margin-bottom:2rem; }
        .welcome h1 { font-size:1.8rem; font-weight:600; margin-bottom:.3rem; }
        .welcome p { color:var(--mut); }
        
        .func-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:1.2rem; }
        .func-card { background:var(--card); padding:1.5rem; border-radius:10px; border:1px solid var(--bdr); text-align:center; text-decoration:none; color:var(--txt); transition:.2s; }
        .func-card:hover { transform:translateY(-3px); box-shadow:0 6px 15px rgba(58,53,50,.08); }
        .func-card .icon { font-size:2rem; margin-bottom:.5rem; display:block; }
        .func-card h3 { font-size:1.1rem; margin-bottom:.3rem; }
        .func-card p { color:var(--mut); font-size:.9rem; }
        
        @media(max-width:850px){
            .dash-wrapper { flex-direction:column; }
            .sidebar { width:100%; position:static; display:flex; flex-wrap:wrap; gap:.5rem; padding:1rem; }
            .sidebar h3 { width:100%; margin-bottom:.5rem; }
            .sidebar a { flex:1 1 45%; margin-bottom:0; }
            .sidebar .logout { width:100%; margin-top:.5rem; padding-top:.5rem; border-top:none; border-bottom:1px solid var(--bdr); }
        }
    </style>
</head>
<body>
    <header>
        <nav class="top-nav">
            <a href="../index.php" class="logo">LUMIÈRE</a>
            <div class="search-box" style="margin-bottom:1.5rem;">
                <form action="../index.php" method="GET">
                    <input type="text" name="q" placeholder="Search products..." style="width:100%; padding:.8rem; border:1px solid var(--bdr); border-radius:8px; background:#FAF8F5;">
                </form>
            </div>
            <div class="top-links">
                <a href="../index.php">Continue Shopping</a>
                <a href="../logout.php" class="btn-sm">Logout</a>
            </div>
        </nav>
    </header>

    <div class="dash-wrapper">
        <aside class="sidebar">
            <h3>My Account</h3>
            <a href="index.php" class="active">🏠 Dashboard</a>
            <a href="cart.php">🛒 My Cart <span class="badge"><?= $cartCount ?></span></a>
            <a href="notifications.php" style="display:flex; justify-content:space-between; align-items:center;">
                 Notifications
                <?php if($unreadCount > 0): ?>
                    <span style="background:#C62828; color:#fff; font-size:.75rem; padding:.1rem .45rem; border-radius:10px;"><?= $unreadCount ?></span>
                <?php endif; ?>
            </a>
            <a href="orders.php">📦 Orders & Tracking</a>
            <a href="profile.php">👤 Profile & Address</a>
            <a href="wishlist.php">❤️ Wishlist</a>
            <a href="settings.php">⚙️ Account Settings</a>
            <a href="../logout.php" class="logout">🚪 Sign Out</a>
        </aside>

        <main class="content">
            <div class="welcome">
                <h1>Welcome back, <?= $userName ?>!</h1>
                <p>Manage your orders, update your details, and review your saved items.</p>
            </div>

            <div class="func-grid">
                <a href="cart.php" class="func-card">
                    <span class="icon">🛒</span>
                    <h3>Shopping Cart</h3>
                    <p><?= $cartCount > 0 ? "$cartCount item(s) waiting" : "Cart is empty" ?></p>
                </a>
                <a href="orders.php" class="func-card">
                    <span class="icon">📦</span>
                    <h3>Order History</h3>
                    <p>Track deliveries & invoices</p>
                </a>
                <a href="profile.php" class="func-card">
                    <span class="icon">👤</span>
                    <h3>Profile & Settings</h3>
                    <p>Update name, email & password</p>
                </a>
                <a href="address.php" class="func-card">
                    <span class="icon">📍</span>
                    <h3>Shipping Address</h3>
                    <p>Manage delivery locations</p>
                </a>
                <a href="wishlist.php" class="func-card">
                    <span class="icon">❤️</span>
                    <h3>Wishlist</h3>
                    <p>Saved products for later</p>
                </a>
                <a href="settings.php" class="func-card">
                    <span class="icon">⚙️</span>
                    <h3>Account Settings</h3>
                    <p>Notifications, privacy & deletion</p>
                </a>
            </div>
        </main>
    </div>
</body>
</html>
