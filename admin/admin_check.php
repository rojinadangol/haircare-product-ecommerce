<?php
session_start();
require_once '../db.php';

// 🔐 Strict role check
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}
?>