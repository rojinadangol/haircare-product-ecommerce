<?php
require_once 'user_check.php';
require_once '../db.php';

$orderId   = intval($_GET['order_id'] ?? 0);
$productId = intval($_GET['product_id'] ?? 0);
$userId    = $_SESSION['user_id'];

// 🔒 Validation Chain
$stmt = $pdo->prepare("SELECT status FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$orderId, $userId]);
$order = $stmt->fetch();
if (!$order || $order['status'] !== 'delivered') { header("Location: order_details.php?id=$orderId&err=not_delivered"); exit; }

$itemStmt = $pdo->prepare("SELECT product_name FROM order_items WHERE order_id = ? AND product_id = ? LIMIT 1");
$itemStmt->execute([$orderId, $productId]);
$item = $itemStmt->fetch();
if (!$item) { header("Location: order_details.php?id=$orderId"); exit; }

$checkStmt = $pdo->prepare("SELECT id FROM reviews WHERE order_id = ? AND product_id = ?");
$checkStmt->execute([$orderId, $productId]);
if ($checkStmt->fetch()) { header("Location: order_details.php?id=$orderId&err=already_reviewed"); exit; }

// Handle Submit
$msg = ''; $type = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating  = intval($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    if ($rating < 1 || $rating > 5) { $msg = "Please select a star rating."; $type = "error"; }
    else {
        try {
            $pdo->prepare("INSERT INTO reviews (user_id, product_id, order_id, rating, comment) VALUES (?, ?, ?, ?, ?)")
                ->execute([$userId, $productId, $orderId, $rating, $comment]);
            header("Location: order_details.php?id=$orderId&success=review");
            exit;
        } catch (Exception $e) { $msg = "Failed to submit review."; $type = "error"; }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Write Review | Lumière</title>
    <style>
        :root { --bg:#F5F1EB; --card:#FFF; --accent:#C8B9A5; --accent-h:#A89580; --txt:#3A3532; --mut:#7A726C; --bdr:#E6DFD6; --danger:#C62828; }
        * { box-sizing:border-box; margin:0; padding:0; font-family:system-ui,sans-serif; }
        body { background:var(--bg); color:var(--txt); display:flex; justify-content:center; align-items:center; min-height:100vh; padding:1rem; }
        .box { background:var(--card); padding:2rem; border-radius:12px; width:100%; max-width:500px; border:1px solid var(--bdr); }
        h2 { margin-bottom:.5rem; } .sub { color:var(--mut); margin-bottom:1.5rem; font-size:.9rem; }
        .stars { display:flex; gap:.3rem; direction:rtl; margin-bottom:1rem; justify-content:flex-end; }
        .stars input { display:none; }
        .stars label { cursor:pointer; font-size:2rem; color:#E0D5C9; transition:.2s; }
        .stars input:checked ~ label, .stars label:hover, .stars label:hover ~ label { color:#F4B400; }
        textarea { width:100%; padding:.9rem; border:1px solid var(--bdr); border-radius:8px; background:#FAF8F5; resize:vertical; min-height:100px; margin-bottom:1rem; }
        textarea:focus { outline:none; border-color:var(--accent); }
        .btn { background:var(--accent); color:#fff; padding:.9rem; border:none; border-radius:8px; width:100%; font-weight:600; cursor:pointer; }
        .btn:hover { background:var(--accent-h); }
        .back { text-align:center; margin-top:1rem; } .back a { color:var(--mut); text-decoration:none; font-size:.9rem; }
        .msg { padding:.8rem; border-radius:6px; margin-bottom:1rem; text-align:center; font-size:.9rem; background:#FDECEA; color:var(--danger); }
    </style>
</head>
<body>
    <div class="box">
        <h2>⭐ Rate Your Purchase</h2>
        <p class="sub">Reviewing: <strong><?= htmlspecialchars($item['product_name']) ?></strong></p>
        <?php if($msg): ?><div class="msg"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
        <form method="POST">
            <div class="stars">
                <input type="radio" name="rating" id="r5" value="5" required><label for="r5">★</label>
                <input type="radio" name="rating" id="r4" value="4"><label for="r4">★</label>
                <input type="radio" name="rating" id="r3" value="3"><label for="r3">★</label>
                <input type="radio" name="rating" id="r2" value="2"><label for="r2">★</label>
                <input type="radio" name="rating" id="r1" value="1"><label for="r1">★</label>
            </div>
            <textarea name="comment" placeholder="Share your experience (optional)..."></textarea>
            <button type="submit" class="btn">Submit Review</button>
        </form>
        <p class="back"><a href="order_details.php?id=<?= $orderId ?>">← Cancel</a></p>
    </div>
</body>
</html>