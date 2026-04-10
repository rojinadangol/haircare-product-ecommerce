<?php
session_start();
// Capture return URL if provided
if (isset($_GET['redirect'])) {
    $_SESSION['redirect_after_login'] = basename($_GET['redirect']);
}
require_once 'db.php';
$error = '';

// If already logged in, redirect
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'login') {
        $email = trim($_POST['email']);
        $pass  = $_POST['password'];
        $stmt = $pdo->prepare("SELECT id, first_name, password_hash, role FROM users WHERE LOWER(email) = LOWER(?)");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($pass, $user['password_hash'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['first_name'];
            $_SESSION['user_role'] = $user['role'];

            // Redirect back to store if coming from "Login to Purchase"
            if (isset($_SESSION['redirect_after_login'])) {
                $target = $_SESSION['redirect_after_login'];
                unset($_SESSION['redirect_after_login']);
                header("Location: $target");
                exit;
            }

            // Default role-based redirect
            if ($user['role'] === 'admin') {
                header("Location: admin/index.php");
            } else {
                header("Location: user/index.php");
            }
            exit;
        } else {
            $error = "Invalid email or password.";
        }
    } 
    elseif ($action === 'register') {
        $fname = trim($_POST['first_name']);
        $lname = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        $pass  = $_POST['password'];
        $conf  = $_POST['confirm_password'];

        if ($pass !== $conf) $error = "Passwords do not match.";
        elseif (strlen($pass) < 8) $error = "Password must be at least 8 characters.";
        else {
            $check = $pdo->prepare("SELECT id FROM users WHERE LOWER(email) = LOWER(?)");
            $check->execute([$email]);
            if ($check->fetch()) {
                $error = "Email already registered.";
            } else {
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password_hash) VALUES (?, ?, LOWER(?), ?)");
                $stmt->execute([$fname, $lname, $email, $hash]);
                $error = "Account created! You can now log in.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | Lumière</title>
    <style>
        :root { --bg:#F5F1EB; --card:#FFF; --accent:#C8B9A5; --accent-h:#A89580; --txt:#3A3532; --mut:#7A726C; --bdr:#E6DFD6; --err:#C62828; }
        * { box-sizing:border-box; margin:0; padding:0; font-family:system-ui,-apple-system,sans-serif; }
        body { background:var(--bg); color:var(--txt); display:flex; align-items:center; justify-content:center; min-height:100vh; padding:1rem; }
        .box { background:var(--card); padding:2rem; border-radius:12px; width:100%; max-width:400px; border:1px solid var(--bdr); box-shadow:0 4px 16px rgba(0,0,0,.05); }
        h2 { text-align:center; margin-bottom:1.5rem; }
        .fg { margin-bottom:1rem; }
        input { width:100%; padding:.85rem; border:1px solid var(--bdr); border-radius:8px; background:#FAF8F5; }
        input:focus { outline:none; border-color:var(--accent); }
        button { width:100%; padding:.9rem; background:var(--accent); color:#fff; border:none; border-radius:8px; font-weight:600; cursor:pointer; }
        button:hover { background:var(--accent-h); }
        .toggle { text-align:center; margin-top:1.2rem; color:var(--mut); font-size:.9rem; }
        .toggle a { color:var(--accent-h); text-decoration:none; font-weight:600; cursor:pointer; }
        .msg { margin-top:1rem; padding:.8rem; text-align:center; border-radius:6px; font-size:.9rem; display:<?= $error ? 'block' : 'none' ?>; background:<?= str_contains($error,'created') || str_contains($error,'created') ? '#E8F5E9' : '#FDECEA' ?>; color:<?= str_contains($error,'created') || str_contains($error,'created') ? '#2E7D32' : 'var(--err)' ?>; }
        .hidden { display:none; }
    </style>
</head>
<body>
    <div class="box">
        <h2 id="title">Welcome Back</h2>
        <div class="msg"><?= htmlspecialchars($error) ?></div>

        <form id="loginForm" method="POST">
            <input type="hidden" name="action" value="login">
            <div class="fg"><input type="email" name="email" placeholder="Email" required></div>
            <div class="fg"><input type="password" name="password" placeholder="Password" required></div>
            <button>Sign In</button>
        </form>

        <form id="regForm" method="POST" class="hidden">
            <input type="hidden" name="action" value="register">
            <div class="fg"><input type="text" name="first_name" placeholder="First Name" required></div>
            <div class="fg"><input type="text" name="last_name" placeholder="Last Name" required></div>
            <div class="fg"><input type="email" name="email" placeholder="Email" required></div>
            <div class="fg"><input type="password" name="password" placeholder="Password (min 8)" required minlength="8"></div>
            <div class="fg"><input type="password" name="confirm_password" placeholder="Confirm Password" required minlength="8"></div>
            <button>Create Account</button>
        </form>

        <p class="toggle" id="toggleText">New here? <a id="toggleLink">Create an account</a></p>
    </div>

    <script>
        const loginForm = document.getElementById('loginForm');
        const regForm = document.getElementById('regForm');
        const toggleLink = document.getElementById('toggleLink');
        const title = document.getElementById('title');
        let isLogin = true;

        toggleLink.addEventListener('click', () => {
            isLogin = !isLogin;
            loginForm.classList.toggle('hidden', !isLogin);
            regForm.classList.toggle('hidden', isLogin);
            title.textContent = isLogin ? 'Welcome Back' : 'Create Account';
            document.querySelector('.msg').style.display = 'none';
            toggleLink.textContent = isLogin ? 'Create an account' : 'Sign In';
            toggleLink.previousSibling.textContent = isLogin ? 'New here? ' : 'Already have an account? ';
        });
    </script>
</body>
</html>