<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/config.php';

if (!isset($_SESSION['ADMINLOGIN'])) {
    header('Location: login.php');
    exit;
}

$adminId    = (int)$_SESSION['ADMINLOGIN'];
$adminEmail = $_SESSION['admin_email'] ?? '';
$adminName  = $_SESSION['admin_name']  ?? 'Admin';
$is_demo    = ($adminEmail === 'admin@aitrading.com');
