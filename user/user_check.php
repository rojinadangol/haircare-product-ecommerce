<?php
session_start();
// 🔐 Redirect to login if session is missing
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}
?>
