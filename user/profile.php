<?php
require_once 'user_check.php';
require_once '../db.php';

$userId = $_SESSION['user_id'];
$msg = '';
$type = '';

// 🔹 Fetch current user data
$stmt = $pdo->prepare("SELECT first_name, last_name, email FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// 🔹 Handle Profile Update
if (isset($_POST['update_profile'])) {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName  = trim($_POST['last_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');

    if (empty($firstName) || empty($lastName) || empty($email)) {
        $msg = "All fields are required."; $type = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = "Invalid email format."; $type = "error";
    } else {
        try {
            $pdo->beginTransaction();
            // Check email uniqueness
            $check = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $check->execute([$email, $userId]);
            if ($check->fetch()) throw new Exception("Email is already in use.");

            $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ?")
                ->execute([$firstName, $lastName, $email, $userId]);
            
            $_SESSION['user_name'] = $firstName;
            $pdo->commit();
            
            $msg = "✅ Profile updated successfully."; $type = "success";
            $user = ['first_name' => $firstName, 'last_name' => $lastName, 'email' => $email];
        } catch (Exception $e) {
            $pdo->rollBack();
            $msg = $e->getMessage(); $type = "error";
        }
    }
}

// 🔹 Handle Password Change
if (isset($_POST['change_password'])) {
    $currentPass = $_POST['current_pass'] ?? '';
    $newPass     = $_POST['new_pass'] ?? '';
    $confirmPass = $_POST['confirm_pass'] ?? '';

    if (empty($currentPass) || empty($newPass) || empty($confirmPass)) {
        $msg = "All password fields are required."; $type = "error";
    } elseif (strlen($newPass) < 8) {
        $msg = "New password must be at least 8 characters."; $type = "error";
    } elseif ($newPass !== $confirmPass) {
        $msg = "New passwords do not match."; $type = "error";
    } else {
        $passStmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
        $passStmt->execute([$userId]);
        $storedHash = $passStmt->fetchColumn();

        if (!password_verify($currentPass, $storedHash)) {
            $msg = "❌ Current password is incorrect."; $type = "error";
        } else {
            $newHash = password_hash($newPass, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$newHash, $userId]);
            $msg = "✅ Password updated successfully. Please log in again."; $type = "success";
            session_destroy();
            header("Location: ../login.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings | Lumière</title>
    <style>
        :root { --bg:#F5F1EB; --card:#FFF; --accent:#C8B9A5; --accent-h:#A89580; --txt:#3A3532; --mut:#7A726C; --bdr:#E6DFD6; --danger:#C62828; --success:#4A7C59; }
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
        .card h2 { font-size:1.3rem; margin-bottom:1rem; padding-bottom:.6rem; border-bottom:1px solid var(--bdr); }
        
        .form-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:1rem; margin-bottom:1rem; }
        .fg { display:flex; flex-direction:column; gap:.4rem; }
        label { font-size:.85rem; font-weight:500; color:var(--mut); }
        input { padding:.8rem; border:1px solid var(--bdr); border-radius:8px; background:#FAF8F5; font-size:1rem; transition:.2s; }
        input:focus { outline:none; border-color:var(--accent); box-shadow:0 0 0 3px rgba(200,185,165,.2); }
        
        .btn { background:var(--accent); color:#fff; padding:.8rem 1.5rem; border:none; border-radius:8px; font-weight:600; cursor:pointer; transition:.2s; }
        .btn:hover { background:var(--accent-h); }
        .btn:disabled { background:var(--mut); cursor:not-allowed; }
        
        .pass-wrap { position:relative; }
        .toggle-pass { position:absolute; right:12px; top:50%; transform:translateY(-50%); background:none; border:none; color:var(--mut); cursor:pointer; font-size:.9rem; }
        
        @media(max-width:600px){ .form-grid{grid-template-columns:1fr;} }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>👤 Profile Settings</h1>
            <a href="index.php" class="btn-back">← Dashboard</a>
        </div>

        <?php if ($msg): ?>
            <div class="msg <?= $type ?>"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <!-- 🔹 Personal Info -->
        <div class="card">
            <h2>Personal Information</h2>
            <form method="POST" id="profileForm">
                <div class="form-grid">
                    <div class="fg"><label>First Name</label><input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required></div>
                    <div class="fg"><label>Last Name</label><input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" required></div>
                    <div class="fg"><label>Email Address</label><input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required></div>
                </div>
                <button type="submit" name="update_profile" class="btn">Save Changes</button>
            </form>
        </div>

        <!-- 🔹 Security / Password -->
        <div class="card">
            <h2>🔒 Change Password</h2>
            <form method="POST" id="passForm">
                <div class="form-grid">
                    <div class="fg pass-wrap">
                        <label>Current Password</label>
                        <input type="password" name="current_pass" required minlength="8">
                        <button type="button" class="toggle-pass" onclick="togglePass(this, 'current_pass')">👁️</button>
                    </div>
                    <div class="fg pass-wrap">
                        <label>New Password</label>
                        <input type="password" name="new_pass" required minlength="8">
                        <button type="button" class="toggle-pass" onclick="togglePass(this, 'new_pass')">👁️</button>
                    </div>
                    <div class="fg pass-wrap">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_pass" required minlength="8">
                        <button type="button" class="toggle-pass" onclick="togglePass(this, 'confirm_pass')">👁️</button>
                    </div>
                </div>
                <button type="submit" name="change_password" class="btn">Update Password</button>
            </form>
        </div>
    </div>

    <script>
        // Toggle Password Visibility
        function togglePass(btn, inputId) {
            const input = document.querySelector(`input[name="${inputId}"]`);
            input.type = input.type === 'password' ? 'text' : 'password';
            btn.textContent = input.type === 'password' ? '👁️' : '🙈';
        }

        // Prevent Double Submit
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const btn = this.querySelector('button[type="submit"]');
                if (btn) {
                    btn.disabled = true;
                    btn.textContent = btn.textContent.includes('Save') ? 'Saving...' : 'Updating...';
                }
            });
        });
    </script>
</body>
</html>