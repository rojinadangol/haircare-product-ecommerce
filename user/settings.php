<?php
require_once 'user_check.php';
require_once '../db.php';

$userId = $_SESSION['user_id'];
$msg = ''; $type = '';

// 🔹 Fetch current settings
$stmt = $pdo->prepare("SELECT email_notifications, promo_emails FROM users WHERE id = ?");
$stmt->execute([$userId]);
$settings = $stmt->fetch();

// 🔹 Handle Preference Update
if (isset($_POST['update_preferences'])) {
    $emailNotif = isset($_POST['email_notifications']) ? 1 : 0;
    $promoNotif = isset($_POST['promo_emails']) ? 1 : 0;
    $pdo->prepare("UPDATE users SET email_notifications = ?, promo_emails = ? WHERE id = ?")
        ->execute([$emailNotif, $promoNotif, $userId]);
    $msg = "✅ Preferences updated successfully."; $type = "success";
    $settings = ['email_notifications' => $emailNotif, 'promo_emails' => $promoNotif];
}

// 🔹 Handle Account Deletion
if (isset($_POST['delete_account'])) {
    $confirmPhrase = trim($_POST['confirm_phrase'] ?? '');
    if ($confirmPhrase !== 'DELETE MY ACCOUNT') {
        $msg = "❌ You must type exactly 'DELETE MY ACCOUNT' to confirm."; $type = "error";
    } else {
        try {
            $pdo->beginTransaction();
            // Anonymize orders to preserve business history
            $pdo->prepare("UPDATE orders SET user_id = NULL WHERE user_id = ?")->execute([$userId]);
            $pdo->prepare("DELETE FROM cart_items ci JOIN carts c ON ci.cart_id = c.id WHERE c.user_id = ?")->execute([$userId]);
            $pdo->prepare("DELETE FROM carts WHERE user_id = ?")->execute([$userId]);
            $pdo->prepare("DELETE FROM wishlist WHERE user_id = ?")->execute([$userId]);
            $pdo->prepare("DELETE FROM notifications WHERE user_id = ?")->execute([$userId]);
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);
            $pdo->commit();
            session_destroy();
            header("Location: ../login.php?msg=account_deleted");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Account Deletion Error: " . $e->getMessage());
            $msg = "❌ Failed to delete account. Please contact support."; $type = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings | Lumière</title>
    <style>
        :root { --bg:#E0D4C3; --card:#F4ECE1; --accent:#A89078; --accent-h:#8F7963; --txt:#3A3532; --mut:#7A726C; --bdr:#CDBBA6; --danger:#C62828; --success:#4A7C59; }
        * { box-sizing:border-box; margin:0; padding:0; font-family:system-ui,-apple-system,sans-serif; }
        body { background:var(--bg); color:var(--txt); line-height:1.6; padding:2rem 1rem; }
        .container { max-width:800px; margin:0 auto; }
        .header { display:flex; justify-content:space-between; align-items:center; margin-bottom:2rem; flex-wrap:wrap; gap:1rem; }
        h1 { font-size:1.8rem; }
        .btn-back { background:var(--bdr); color:var(--txt); padding:.5rem 1rem; border-radius:6px; text-decoration:none; font-weight:500; transition:.2s; }
        .btn-back:hover { background:#D5C9C0; }
        
        .msg { padding:.9rem; border-radius:8px; margin-bottom:1.5rem; text-align:center; font-weight:500; }
        .success { background:#E8F5E9; color:var(--success); border:1px solid #C3E6CB; }
        .error { background:#FDECEA; color:var(--danger); border:1px solid #F5C6CB; }
        
        .card { background:var(--card); border:1px solid var(--bdr); border-radius:12px; padding:1.8rem; margin-bottom:1.5rem; }
        .card h2 { font-size:1.3rem; margin-bottom:1.2rem; padding-bottom:.6rem; border-bottom:1px solid var(--bdr); }
        
        .pref-row { display:flex; justify-content:space-between; align-items:center; padding:.8rem 0; border-bottom:1px dashed var(--bdr); }
        .pref-row:last-child { border-bottom:none; }
        .pref-info h3 { font-size:1rem; margin-bottom:.2rem; }
        .pref-info p { font-size:.85rem; color:var(--mut); }
        
        /* Custom Toggle Switch */
        .toggle { position:relative; width:48px; height:24px; }
        .toggle input { opacity:0; width:0; height:0; }
        .slider { position:absolute; cursor:pointer; top:0;left:0;right:0;bottom:0; background-color:#E6DFD6; border-radius:24px; transition:.3s; }
        .slider:before { position:absolute; content:""; height:18px; width:18px; left:3px; bottom:3px; background:#fff; border-radius:50%; transition:.3s; }
        input:checked + .slider { background-color:var(--accent); }
        input:checked + .slider:before { transform:translateX(24px); }
        
        .btn { background:var(--accent); color:#fff; padding:.8rem 1.5rem; border:none; border-radius:8px; font-weight:600; cursor:pointer; transition:.2s; }
        .btn:hover { background:var(--accent-h); }
        .btn-danger { background:var(--danger); }
        .btn-danger:hover { background:#A31F1F; }
        
        .danger-zone { border:1px solid #F5C6CB; background:#FFF8F7; }
        .danger-zone h2 { color:var(--danger); border-bottom-color:#F5C6CB; }
        .confirm-input { width:100%; padding:.8rem; border:1px solid #F5C6CB; border-radius:8px; background:#FFF; margin:.8rem 0; font-size:.95rem; }
        .warning { color:var(--danger); font-size:.85rem; margin-bottom:.8rem; display:block; }
        
        @media(max-width:600px){ .pref-row{flex-direction:column; align-items:flex-start; gap:.5rem;} .toggle{align-self:flex-start;} }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚙️ Account Settings</h1>
            <a href="index.php" class="btn-back">← Dashboard</a>
        </div>

        <?php if ($msg): ?>
            <div class="msg <?= $type ?>"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <!-- 🔔 Notification Preferences -->
        <div class="card">
            <h2>📬 Notification Preferences</h2>
            <form method="POST">
                <div class="pref-row">
                    <div class="pref-info">
                        <h3>Order & Shipping Updates</h3>
                        <p>Receive emails about order status and delivery tracking.</p>
                    </div>
                    <label class="toggle">
                        <input type="checkbox" name="email_notifications" <?= $settings['email_notifications'] ? 'checked' : '' ?>>
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="pref-row">
                    <div class="pref-info">
                        <h3>Promotions & New Products</h3>
                        <p>Get updates on sales, discounts, and new arrivals.</p>
                    </div>
                    <label class="toggle">
                        <input type="checkbox" name="promo_emails" <?= $settings['promo_emails'] ? 'checked' : '' ?>>
                        <span class="slider"></span>
                    </label>
                </div>
                <button type="submit" name="update_preferences" class="btn" style="margin-top:1rem;">Save Preferences</button>
            </form>
        </div>

        <!-- 🗑️ Account Management -->
        <div class="card danger-zone">
            <h2>🗑️ Delete Account</h2>
            <p style="margin-bottom:1rem; color:var(--mut); font-size:.95rem;">Permanently remove your profile, saved addresses, and wishlist. Your order history will be anonymized for business records.</p>
            <form method="POST" id="deleteForm">
                <span class="warning">⚠️ This action cannot be undone.</span>
                <input type="text" name="confirm_phrase" class="confirm-input" placeholder="Type DELETE MY ACCOUNT to confirm" required>
                <button type="submit" name="delete_account" class="btn btn-danger" onclick="return confirm('Are you absolutely sure? This will permanently delete your account.');">Permanently Delete Account</button>
            </form>
        </div>
    </div>

    <script>
        // Auto-disable submit after first click to prevent double submission
        document.getElementById('deleteForm').addEventListener('submit', function(e) {
            const btn = this.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.textContent = 'Deleting...';
        });
    </script>
</body>
</html>
