<?php 
require_once 'user_check.php';
require_once '../db.php';

$userId = $_SESSION['user_id'];
$userName = htmlspecialchars($_SESSION['user_name'] ?? 'User');

// 🔹 Handle Cart & Checkout Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['cart_action'] ?? '';
    $stmt = $pdo->prepare("SELECT id FROM carts WHERE user_id = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$userId]);
    $cart = $stmt->fetch();
    $cartId = $cart['id'] ?? 0;

    try {
        if ($action === 'update' && isset($_POST['item_id'], $_POST['qty'])) {
            $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE id = ? AND cart_id = ?")
                ->execute([max(1, intval($_POST['qty'])), $_POST['item_id'], $cartId]);
        } elseif ($action === 'remove' && isset($_POST['item_id'])) {
            $pdo->prepare("DELETE FROM cart_items WHERE id = ? AND cart_id = ?")
                ->execute([$_POST['item_id'], $cartId]);
        } elseif ($action === 'clear') {
            $pdo->prepare("DELETE FROM cart_items WHERE cart_id = ?")->execute([$cartId]);
        } elseif ($action === 'checkout' && $cartId > 0) {
            $address = trim($_POST['address'] ?? '');
            $paymentMethod = in_array($_POST['payment_method'] ?? '', ['cod', 'khalti', 'esewa']) ? $_POST['payment_method'] : 'cod';
            
            if (empty($address)) throw new Exception("Please enter a shipping address.");

            $pdo->beginTransaction();

            $items = $pdo->prepare("SELECT ci.*, p.name, p.price, p.stock_quantity FROM cart_items ci JOIN products p ON ci.product_id = p.id WHERE ci.cart_id = ?");
            $items->execute([$cartId]);
            $cartItems = $items->fetchAll();
            
            $subtotal = 0;
            foreach ($cartItems as $i) $subtotal += $i['price'] * $i['quantity'];
            $tax = $subtotal * 0.08;
            $shipping = $subtotal > 50 ? 0 : ($subtotal > 0 ? 5.00 : 0);
            $total = $subtotal + $tax + $shipping;

            $orderNum = 'ORD-' . strtoupper(bin2hex(random_bytes(3))) . '-' . date('ym');
            $deliveryCode = 'DLV-' . strtoupper(bin2hex(random_bytes(4)));

            $pdo->prepare("INSERT INTO orders (user_id, order_number, delivery_code, status, subtotal, tax, shipping, total, address, payment_method) VALUES (?, ?, ?, 'confirmed', ?, ?, ?, ?, ?, ?)")
                ->execute([$userId, $orderNum, $deliveryCode, $subtotal, $tax, $shipping, $total, $address, $paymentMethod]);
            $orderId = $pdo->lastInsertId();

            foreach ($cartItems as $item) {
                if ($item['stock_quantity'] < $item['quantity']) {
                    throw new Exception(" Not enough stock for '{$item['name']}' (Available: {$item['stock_quantity']})");
                }
                $pdo->prepare("INSERT INTO order_items (order_id, product_id, product_name, quantity, price) VALUES (?, ?, ?, ?, ?)")
                    ->execute([$orderId, $item['product_id'], $item['name'], $item['quantity'], $item['price']]);
                $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?")
                    ->execute([$item['quantity'], $item['product_id']]);
            }

            $pdo->prepare("DELETE FROM cart_items WHERE cart_id = ?")->execute([$cartId]);
            $pdo->commit();
            
            // Redirect based on payment method
            if ($paymentMethod === 'khalti') {
                header("Location: khalti_initiate.php?order_id=$orderId");
            } elseif ($paymentMethod === 'esewa') {
                header("Location: esewa_payment.php?id=$orderId");
            } else {
                $_SESSION['last_order'] = $orderId;
                header("Location: order_success.php"); // Or order_details.php
            }
            exit;
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $checkoutError = $e->getMessage();
    }
    header("Location: cart.php");
    exit;
}

// 🔹 Fetch Active Cart
$cartItems = []; $subtotal = 0;
$stmt = $pdo->prepare("SELECT id FROM carts WHERE user_id = ? AND status = 'active' LIMIT 1");
$stmt->execute([$userId]);
$cart = $stmt->fetch();
$cartId = $cart['id'] ?? 0;

if ($cartId > 0) {
    $stmt = $pdo->prepare("SELECT ci.id as item_id, ci.quantity, p.id as product_id, p.name, p.price, p.image_url FROM cart_items ci JOIN products p ON ci.product_id = p.id WHERE ci.cart_id = ?");
    $stmt->execute([$cartId]);
    $cartItems = $stmt->fetchAll();
    foreach ($cartItems as &$item) { $item['line_total'] = $item['price'] * $item['quantity']; $subtotal += $item['line_total']; }
}
$tax = $subtotal * 0.08;
$shipping = $subtotal > 50 ? 0 : ($subtotal > 0 ? 5.00 : 0);
$total = $subtotal + $tax + $shipping;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Cart | Lumière</title>
    <style>
        :root { --bg:#F5F1EB; --card:#FFF; --accent:#C8B9A5; --accent-h:#A89580; --txt:#3A3532; --mut:#7A726C; --bdr:#E6DFD6; --danger:#C62828; }
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
        .container { max-width:1100px; margin:2.5rem auto; padding:0 1.5rem; display:grid; grid-template-columns:1fr 320px; gap:2rem; }
        .item { background:var(--card); border:1px solid var(--bdr); border-radius:10px; padding:1rem; display:flex; gap:1rem; align-items:center; margin-bottom:1rem; }
        .item img { width:80px; height:80px; object-fit:cover; border-radius:8px; background:#EAE3D9; }
        .item-info { flex:1; }
        .item-info h3 { font-size:1.05rem; margin-bottom:.3rem; }
        .item-info .price { color:var(--mut); font-size:.95rem; }
        .qty-box { display:flex; align-items:center; gap:.5rem; background:#FAF8F5; border:1px solid var(--bdr); border-radius:6px; padding:.3rem; }
        .qty-box input { width:45px; text-align:center; border:none; background:transparent; font-size:.95rem; }
        .qty-box input:focus { outline:none; }
        .item-total { font-weight:600; min-width:60px; text-align:right; }
        .remove-btn { background:none; border:none; color:var(--mut); cursor:pointer; font-size:1.2rem; }
        .remove-btn:hover { color:var(--danger); }
        .summary { background:var(--card); border:1px solid var(--bdr); border-radius:10px; padding:1.5rem; height:fit-content; }
        .summary h2 { font-size:1.3rem; margin-bottom:1rem; padding-bottom:.8rem; border-bottom:1px solid var(--bdr); }
        .row { display:flex; justify-content:space-between; margin-bottom:.6rem; font-size:.95rem; }
        .total { font-weight:700; font-size:1.2rem; margin-top:1rem; padding-top:1rem; border-top:2px solid var(--bdr); }
        .checkout-box { margin-top:1.5rem; border-top:1px dashed var(--bdr); padding-top:1rem; }
        .checkout-box textarea { width:100%; padding:.8rem; border:1px solid var(--bdr); border-radius:6px; background:#FAF8F5; margin-bottom:.8rem; resize:vertical; }
        .empty { grid-column:1/-1; text-align:center; padding:4rem 2rem; background:var(--card); border:1px dashed var(--bdr); border-radius:10px; color:var(--mut); }
        .err { background:#FDECEA; color:var(--danger); padding:.8rem; border-radius:6px; margin-bottom:1rem; text-align:center; }
        .payment-options { margin-top:1.5rem; border-top:1px dashed var(--bdr); padding-top:1rem; }
        .radio-card { display:flex; align-items:center; gap:.8rem; padding:.85rem; border:1px solid var(--bdr); border-radius:8px; margin-bottom:.5rem; cursor:pointer; transition:.2s; background:#FAF8F5; }
        .radio-card:hover { border-color:var(--accent); background:#FFF; }
        .radio-card input[type="radio"] { accent-color:var(--accent); width:18px; height:18px; margin:0; }
        .radio-card input[type="radio"]:checked + .radio-content { color:var(--txt); font-weight:500; }
        .radio-content span { display:block; font-size:.95rem; }
        .radio-content small { color:var(--mut); font-size:.8rem; display:block; margin-top:.2rem; }
        @media(max-width:850px){ .container{grid-template-columns:1fr;} .item{flex-wrap:wrap;} }
    </style>
</head>
<body>
    <header>
        <nav>
            <a href="../index.php" class="logo">LUMIÈRE</a>
            <div class="nav-links">
                <a href="../index.php">🛍️ Store</a>
                <a href="index.php">👤 Account</a>
                <a href="cart.php" style="color:var(--txt); font-weight:600;">🛒 Cart</a>
                <a href="../logout.php" class="btn-outline">Logout</a>
            </div>
        </nav>
    </header>

    <main class="container">
        <?php if (empty($cartItems)): ?>
            <div class="empty">
                <h2>Your cart is empty</h2>
                <p>Add products from the store to get started.</p>
                <a href="../index.php" class="btn" style="margin-top:1.5rem; display:inline-block;">Browse Products</a>
            </div>
        <?php else: ?>
            <div class="cart-items">
                <h2 style="margin-bottom:1.5rem;">Shopping Cart (<?= count($cartItems) ?>)</h2>
                <?php foreach ($cartItems as $item): ?>
                    <div class="item">
                        <img src="<?= htmlspecialchars($item['image_url']) ?: 'https://placehold.co/80x80/EAE3D9/7A726C?text=Img' ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                        <div class="item-info">
                            <h3><?= htmlspecialchars($item['name']) ?></h3>
                            <p class="price">$<?= number_format($item['price'], 2) ?> each</p>
                        </div>
                        <form method="POST" style="display:flex; align-items:center; gap:.8rem;">
                            <input type="hidden" name="item_id" value="<?= $item['item_id'] ?>">
                            <div class="qty-box">
                                <button type="submit" name="cart_action" value="update" style="background:none;border:none;cursor:pointer;color:var(--mut);">-</button>
                                <input type="number" name="qty" value="<?= $item['quantity'] ?>" min="1" onchange="this.form.submit()">
                                <button type="submit" name="cart_action" value="update" style="background:none;border:none;cursor:pointer;color:var(--mut);">+</button>
                            </div>
                            <div class="item-total">$<?= number_format($item['line_total'], 2) ?></div>
                            <button type="submit" name="cart_action" value="remove" class="remove-btn" title="Remove">✕</button>
                        </form>
                    </div>
                <?php endforeach; ?>
                <form method="POST" style="display:inline; margin-top:1rem;">
                    <button type="submit" name="cart_action" value="clear" class="btn-outline" style="width:auto;">Clear Cart</button>
                </form>
            </div>

            <aside class="summary">
                <h2>Order Summary</h2>
                <div class="row"><span>Subtotal</span><span>$<?= number_format($subtotal, 2) ?></span></div>
                <div class="row"><span>Tax (8%)</span><span>$<?= number_format($tax, 2) ?></span></div>
                <div class="row"><span>Shipping</span><span><?= $shipping == 0 ? 'FREE' : '$'.number_format($shipping, 2) ?></span></div>
                <div class="row total"><span>Total</span><span>$<?= number_format($total, 2) ?></span></div>

                <div class="checkout-box">
                    <h3 style="margin-bottom:.8rem;"> Shipping Details</h3>
                    <?php if (isset($checkoutError)): ?>
                        <div class="err"><?= htmlspecialchars($checkoutError) ?></div>
                    <?php endif; ?>
                    <form method="POST" id="checkoutForm">
                        <textarea name="address" placeholder="Enter full shipping address (Street, City, Zip)..." required rows="3" style="width:100%;padding:.8rem;border:1px solid var(--bdr);border-radius:6px;background:#FAF8F5;margin-bottom:1rem;resize:vertical;"></textarea>
                        
                        <div class="payment-options">
                            <label class="radio-card">
                                <input type="radio" name="payment_method" value="cod" checked>
                                <div class="radio-content">
                                    <span> Cash on Delivery</span>
                                    <small>Pay when you receive your order</small>
                                </div>
                            </label>
                            <label class="radio-card">
                                <input type="radio" name="payment_method" value="khalti">
                                <div class="radio-content">
                                    <span> Khalti Digital Wallet</span>
                                    <small>Secure online payment via Khalti app</small>
                                </div>
                            </label>
                            <label class="radio-card">
                                <input type="radio" name="payment_method" value="esewa">
                                <div class="radio-content">
                                    <span> 🟩 eSewa Digital Wallet</span>
                                    <small>Pay securely via eSewa</small>
                                </div>
                            </label>
                        </div>

                        <button type="submit" name="cart_action" value="checkout" class="btn" style="width:100%; text-align:center; margin-top:1rem;">Proceed to Checkout</button>
                    </form>
                </div>
            </aside>
        <?php endif; ?>
    </main>
</body>
</html>