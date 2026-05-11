<?php require_once 'admin_check.php'; ?>
<?php
// Mark as read
if (isset($_GET['mark_read']) && isset($_GET['id'])) {
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?")->execute([$_GET['id']]);
    header("Location: notifications.php"); exit;
}
// Clear all
if (isset($_GET['clear_all'])) {
    $pdo->query("UPDATE notifications SET is_read = 1");
    header("Location: notifications.php"); exit;
}
$notifs = $pdo->query("SELECT * FROM notifications ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications | Admin</title>
    <style>
        :root{--bg:#F5F1EB;--card:#FFF;--accent:#800000;--mut:#6B4C4C;--bdr:#E6D5D5;--txt:#2C1810;}
        *{box-sizing:border-box;margin:0;padding:0;font-family:system-ui,sans-serif;}
        body{background:var(--bg);color:var(--txt);padding:2rem;}
        .container{max-width:900px;margin:0 auto;}
        h1{margin-bottom:1rem;color:var(--accent);}
        .notif{background:var(--card);border:1px solid var(--bdr);border-radius:8px;padding:1rem;margin-bottom:.8rem;display:flex;justify-content:space-between;align-items:center;}
        .notif.unread{border-left:4px solid var(--accent);}
        .notif a{color:var(--accent);text-decoration:none;font-weight:500;font-size:.85rem;}
        .empty{text-align:center;padding:2rem;color:var(--mut);background:var(--card);border-radius:8px;}
    </style>
</head>
<body>
    <?php require_once 'admin_sidebar.php'; ?>
    <main class="main">
    <div class="container">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
            <h1> Admin Notifications</h1>
        </div>
        <a href="?clear_all" style="display:inline-block;margin-bottom:1rem;color:var(--mut);font-size:.9rem;">Mark all as read</a>
        <?php if(empty($notifs)): ?>
            <p class="empty">No notifications.</p>
        <?php else: ?>
            <?php foreach($notifs as $n): ?>
            <div class="notif <?= $n['is_read']?'':'unread' ?>">
                <div>
                    <strong><?= htmlspecialchars($n['title']) ?></strong><br>
                    <small style="color:var(--mut)"><?= htmlspecialchars($n['message']) ?></small><br>
                    <small style="color:var(--mut)"><?= date('M d, Y H:i', strtotime($n['created_at'])) ?></small>
                </div>
                <?php if(!$n['is_read']): ?>
                    <a href="?mark_read&id=<?= $n['id'] ?>">Mark Read</a>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    </main>
</body>
</html>