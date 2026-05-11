<?php 
require_once 'user_check.php';
require_once '../db.php';

$userId = $_SESSION['user_id'];
$msg = $_GET['msg'] ?? '';

// Handle Remove / Move to Cart
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['wish_action'] ?? '';
    $prodId = intval($_POST['product_id'] ?? 0);

    if ($prodId > 0) {
        if ($action === 'remove') {
            $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?")->execute([$userId, $prodId]);
        } elseif ($action === 'move_to_cart') {
            $stmt = $pdo->prepare("SELECT stock_quantity FROM products WHERE id = ?");
            $stmt->execute([$prodId]);
            $stock = $stmt->fetchColumn();

            if ($stock > 0) {
                // Remove from wishlist
                $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?")->execute([$userId, $prodId]);
                // Add to cart
                $cartStmt = $pdo->prepare("SELECT id FROM carts WHERE user_id = ? AND status = 'active' LIMIT 1");
                $cartStmt->execute([$userId]);
                $cart = $cartStmt->fetch();
                $cartId = $cart ? $cart['id'] : ($pdo->prepare("INSERT INTO carts (user_id) VALUES (?)")->execute([$userId]) ? $pdo->lastInsertId() : null);
                if ($cartId) {
                    $pdo->prepare("INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE quantity = quantity + 1")
                        ->execute([$cartId, $prodId]);
                    header("Location: cart.php"); exit;
                }
            }
        }
    }
    header("Location: wishlist.php");
    exit;
}

// Fetch wishlist
$stmt = $pdo->prepare("
    SELECT p.id, p.name, p.price, p.image_url, p.stock_quantity, w.added_at
    FROM wishlist w JOIN products p ON w.product_id = p.id
    WHERE w.user_id = ?
    ORDER BY w.added_at DESC
");
$stmt->execute([$userId]);
$wishlist = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist | Lumière</title>
    <style>
        :root { --bg:#E0D4C3; --card:#F4ECE1; --accent:#A89078; --accent-h:#8F7963; --txt:#3A3532; --mut:#7A726C; --bdr:#CDBBA6; --danger:#C62828; --success:#4A7C59; }
        * { box-sizing:border-box; margin:0; padding:0; font-family:system-ui,-apple-system,sans-serif; }
        body { background:var(--bg); color:var(--txt); line-height:1.6; }
        header { background:var(--card); padding:1rem 5%; border-bottom:1px solid var(--bdr); }
        nav { max-width:1200px; margin:0 auto; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; }
        .logo { font-size:1.5rem; font-weight:700; letter-spacing:1px; color:var(--txt); text-decoration:none; }
        .nav-links a { text-decoration:none; color:var(--mut); font-weight:500; transition:.2s; }
        .nav-links a:hover { color:var(--txt); }
        .btn { background:var(--accent); color:#fff; padding:.6rem 1.5rem; border-radius:50px; text-decoration:none; font-weight:600; border:none; cursor:pointer; transition:.2s; }
        .btn:hover { background:var(--accent-h); }
        .btn-outline { background:transparent; border:1px solid var(--bdr); color:var(--mut); padding:.5rem 1.2rem; border-radius:50px; text-decoration:none; font-weight:500; }
        .btn-outline:hover { border-color:var(--accent); color:var(--txt); }
        .container { max-width:900px; margin:2.5rem auto; padding:0 1.5rem; }
        h1 { font-size:1.8rem; margin-bottom:1rem; }
        .msg { padding:.8rem; border-radius:8px; margin-bottom:1rem; text-align:center; font-size:.95rem; }
        .info { background:#E3F2FD; color:#1565C0; border:1px solid #BBDEFB; }
        .grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(240px,1fr)); gap:1.5rem; }
        .wish-card { background:var(--card); border:1px solid var(--bdr); border-radius:10px; padding:1rem; text-align:center; transition:.2s; }
        .wish-card:hover { box-shadow:0 4px 12px rgba(58,53,50,.06); }
        .wish-card img { width:100%; height:140px; object-fit:cover; border-radius:6px; background:#EAE3D9; margin-bottom:.8rem; }
        .wish-card h3 { font-size:1rem; margin-bottom:.3rem; }
        .wish-card .price { color:var(--mut); font-size:.95rem; margin-bottom:.5rem; }
        .stock { font-size:.85rem; margin-bottom:.8rem; font-weight:500; }
        .in-stock { color:var(--success); } .out-stock { color:var(--danger); }
        .actions { display:flex; flex-direction:column; gap:.5rem; }
        .empty { text-align:center; padding:4rem 2rem; background:var(--card); border:1px dashed var(--bdr); border-radius:10px; color:var(--mut); }
        .empty h2 { margin-bottom:.5rem; color:var(--txt); }
        @media(max-width:600px){ .grid{grid-template-columns:1fr;} }
    </style>
</head>
<body>
    <header>
        <nav>
            <a href="../index.php" class="logo">LUMIÈRE</a>
            <div class="nav-links">
                <a href="../index.php">🛍️ Store</a>
                <a href="index.php">👤 Dashboard</a>
                <a href="wishlist.php" style="color:var(--txt); font-weight:600;">❤️ Wishlist</a>
                <a href="../logout.php" class="btn-outline">Logout</a>
            </div>
        </nav>
    </header>

    <main class="container">
        <h1>❤️ My Wishlist</h1>
        <?php if ($msg === 'out_of_stock'): ?>
            <div class="msg info">📦 This item is out of stock. It has been saved to your wishlist.</div>
        <?php endif; ?>

        <?php if (empty($wishlist)): ?>
            <div class="empty">
                <h2>Your wishlist is empty</h2>
                <p>Products you can't add to cart will appear here.</p>
                <a href="../index.php" class="btn" style="margin-top:1rem; display:inline-block;">Browse Products</a>
            </div>
        <?php else: ?>
            <div class="grid">
                <?php foreach ($wishlist as $w): ?>
                    <div class="wish-card">
                        <img src="<?= htmlspecialchars($w['image_url']) ?: 'https://placehold.co/300x200/EAE3D9/7A726C?text=Product' ?>" alt="<?= htmlspecialchars($w['name']) ?>">
                        <h3><?= htmlspecialchars($w['name']) ?></h3>
                        <p class="price">$<?= number_format($w['price'], 2) ?></p>
                        <?php
                        // Check if recently restocked (added within last 24h & stock > 0)
                        $isFreshRestock = ($w['stock_quantity'] > 0 && strtotime($w['added_at']) > strtotime('-24 hours'));
                        ?>
                        <p class="stock <?= $w['stock_quantity'] > 0 ? 'in-stock' : 'out-stock' ?>">
                            <?= $w['stock_quantity'] > 0 ? "✅ $w[stock_quantity] in stock" : "❌ Out of stock" ?>
                            <?php if ($isFreshRestock): ?>
                                <span style="background:#E8F5E9; color:#2E7D32; padding:2px 6px; border-radius:4px; font-size:.7rem; margin-left:5px;">🔔 Just Restocked</span>
                            <?php endif; ?>
                        </p>
                        <div class="actions">
                            <?php if ($w['stock_quantity'] > 0): ?>
                                <form method="POST">
                                    <input type="hidden" name="product_id" value="<?= $w['id'] ?>">
                                    <button type="submit" name="wish_action" value="move_to_cart" class="btn" style="width:100%; font-size:.9rem;">Move to Cart</button>
                                </form>
                            <?php endif; ?>
                            <form method="POST">
                                <input type="hidden" name="product_id" value="<?= $w['id'] ?>">
                                <button type="submit" name="wish_action" value="remove" class="btn-outline" style="width:100%; font-size:.9rem;">Remove</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
