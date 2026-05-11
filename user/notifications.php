<?php
require_once 'user_check.php';
require_once '../db.php';

$userId = $_SESSION['user_id'];

// Mark single as read
if (isset($_GET['mark_read']) && isset($_GET['id'])) {
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?")->execute([$_GET['id'], $userId]);
    header("Location: notifications.php"); exit;
}
// Mark all as read
if (isset($_GET['mark_all'])) {
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$userId]);
    header("Location: notifications.php"); exit;
}

// Fetch notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$userId]);
$notifs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications | Lumière</title>
    <style>
        :root { --bg:#E0D4C3; --card:#F4ECE1; --accent:#A89078; --txt:#3A3532; --mut:#7A726C; --bdr:#CDBBA6; }
        * { box-sizing:border-box; margin:0; padding:0; font-family:system-ui,sans-serif; }
        body { background:var(--bg); color:var(--txt); padding:2rem 1rem; }
        .container { max-width:800px; margin:0 auto; }
        .header { display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem; }
        h1 { font-size:1.6rem; }
        .btn-back { background:var(--bdr); color:var(--txt); padding:.5rem 1rem; border-radius:6px; text-decoration:none; font-weight:500; }
        .notif { background:var(--card); border:1px solid var(--bdr); border-radius:10px; padding:1rem; margin-bottom:.8rem; display:flex; justify-content:space-between; align-items:center; transition:.2s; }
        .notif.unread { border-left:4px solid var(--accent); background:#FAF8F5; }
        .notif h3 { font-size:.95rem; margin-bottom:.3rem; }
        .notif p { font-size:.85rem; color:var(--mut); }
        .notif small { font-size:.75rem; color:var(--mut); }
        .mark-link { color:var(--accent); text-decoration:none; font-size:.8rem; font-weight:500; margin-left:.5rem; }
        .mark-link:hover { text-decoration:underline; }
        .empty { text-align:center; padding:3rem; background:var(--card); border:1px dashed var(--bdr); border-radius:10px; color:var(--mut); }
        @media(max-width:600px){ .notif{flex-direction:column; align-items:flex-start; gap:.5rem;} .mark-link{margin-left:0;margin-top:.5rem;} }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔔 My Notifications</h1>
            <div>
                <a href="?mark_all" style="color:var(--mut); font-size:.9rem; margin-right:1rem;">Mark all as read</a>
                <a href="index.php" class="btn-back">← Dashboard</a>
            </div>
        </div>
        <?php if(empty($notifs)): ?>
            <div class="empty">
                <h2>All caught up!</h2>
                <p>You'll see updates about your orders here.</p>
            </div>
        <?php else: ?>
            <?php foreach($notifs as $n): ?>
            <div class="notif <?= $n['is_read']?'':'unread' ?>">
                <div>
                    <h3><?= htmlspecialchars($n['title']) ?></h3>
                    <p><?= $n['message'] ?></p>
                    <small><?= date('M d, Y • h:i A', strtotime($n['created_at'])) ?></small>
                </div>
                <?php if(!$n['is_read']): ?>
                    <a href="?mark_read&id=<?= $n['id'] ?>" class="mark-link">Mark Read</a>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
