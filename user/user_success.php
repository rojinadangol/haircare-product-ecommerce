<?php session_start(); if(!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; } ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed</title>
    <style>
        :root{--bg:#F5F1EB;--card:#FFF;--accent:#C8B9A5;--txt:#3A3532;--mut:#7A726C;--bdr:#E6DFD6;}
        *{box-sizing:border-box;margin:0;padding:0;font-family:system-ui,sans-serif;}
        body{background:var(--bg);color:var(--txt);display:flex;justify-content:center;align-items:center;min-height:100vh;padding:2rem;}
        .box{background:var(--card);padding:2.5rem;border-radius:12px;border:1px solid var(--bdr);text-align:center;max-width:450px;}
        .icon{font-size:3rem;margin-bottom:1rem;}
        h1{margin-bottom:.5rem;} p{color:var(--mut);margin-bottom:1.5rem;}
        .btn{display:inline-block;background:var(--accent);color:#fff;padding:.7rem 1.5rem;border-radius:50px;text-decoration:none;font-weight:600;}
        .btn:hover{opacity:.9;}
    </style>
</head>
<body>
    <div class="box">
        <div class="icon">🎉</div>
        <h1>Order Placed!</h1>
        <p>Thank you for your purchase. Your order number is:<br>
        <strong style="font-size:1.2rem; color:var(--txt);"><?= htmlspecialchars($_SESSION['last_order'] ?? 'N/A') ?></strong></p>
        <a href="index.php" class="btn">Return to Dashboard</a>
    </div>
</body>
</html>
<?php unset($_SESSION['last_order']); ?>