<?php
require_once __DIR__ . '/session.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($sitename) ?> Admin</title>
  <link rel="stylesheet" href="../assets/css/admin.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
</head>
<body class="adm-body">

<!-- Sidebar -->
<aside class="adm-sidebar" id="sidebar">
  <div class="adm-logo">
    <i class="fa-solid fa-qrcode"></i> <?= htmlspecialchars($sitename) ?>
  </div>

  <nav class="adm-nav">
    <div class="adm-nav-section">Main</div>
    <a href="index.php"        class="adm-nav-item <?= basename($_SERVER['PHP_SELF']) === 'index.php'        ? 'active' : '' ?>"><i class="fa fa-gauge"></i> Dashboard</a>
    <a href="users.php"        class="adm-nav-item <?= basename($_SERVER['PHP_SELF']) === 'users.php'        ? 'active' : '' ?>"><i class="fa fa-users"></i> Users</a>
    <a href="kyc.php"          class="adm-nav-item <?= basename($_SERVER['PHP_SELF']) === 'kyc.php'          ? 'active' : '' ?>"><i class="fa fa-id-card"></i> KYC Verification</a>
    <a href="investments.php"  class="adm-nav-item <?= basename($_SERVER['PHP_SELF']) === 'investments.php'  ? 'active' : '' ?>"><i class="fa fa-chart-line"></i> Investments</a>

    <div class="adm-nav-section">Transactions</div>
    <a href="deposit.php"      class="adm-nav-item <?= basename($_SERVER['PHP_SELF']) === 'deposit.php'      ? 'active' : '' ?>"><i class="fa fa-arrow-down"></i> Deposits</a>
    <a href="withdraw.php"     class="adm-nav-item <?= basename($_SERVER['PHP_SELF']) === 'withdraw.php'     ? 'active' : '' ?>"><i class="fa fa-arrow-up"></i> Withdrawals</a>

    <div class="adm-nav-section">AI Trading</div>
    <a href="ai_trades.php"    class="adm-nav-item <?= basename($_SERVER['PHP_SELF']) === 'ai_trades.php'    ? 'active' : '' ?>"><i class="fa fa-robot"></i> AI Trade Manager</a>
    <a href="bot_settings.php" class="adm-nav-item <?= basename($_SERVER['PHP_SELF']) === 'bot_settings.php' ? 'active' : '' ?>"><i class="fa fa-sliders"></i> Bot Settings</a>
    <a href="plans.php"        class="adm-nav-item <?= basename($_SERVER['PHP_SELF']) === 'plans.php'        ? 'active' : '' ?>"><i class="fa fa-layer-group"></i> Plans</a>

    <div class="adm-nav-section">Platform</div>
    <a href="settings.php"     class="adm-nav-item <?= basename($_SERVER['PHP_SELF']) === 'settings.php'     ? 'active' : '' ?>"><i class="fa fa-gear"></i> Settings</a>
    <a href="logout.php"       class="adm-nav-item"><i class="fa fa-right-from-bracket"></i> Logout</a>
  </nav>
</aside>

<!-- Top bar -->
<div class="adm-topbar">
  <button class="adm-menu-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')">
    <i class="fa fa-bars"></i>
  </button>
  <div class="adm-topbar-right">
    <span style="font-size:0.83rem;color:var(--adm-text-muted)"><?= htmlspecialchars($adminName) ?></span>
    <a href="logout.php" style="font-size:0.8rem;color:var(--adm-text-muted);margin-left:14px;">Logout</a>
  </div>
</div>

<!-- Page content -->
<main class="adm-main">
<?php if ($is_demo): ?>
<div style="background:rgba(245,158,11,0.12);border:1px solid #f59e0b;color:#f59e0b;text-align:center;padding:9px 16px;font-size:0.82rem;font-weight:600;border-radius:8px;margin-bottom:18px;">
  Demo account — some write actions are disabled. Activity here is visible to all buyers.
</div>
<?php endif; ?>
