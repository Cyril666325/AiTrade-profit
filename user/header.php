<?php
require_once __DIR__ . '/session.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($sitename) ?></title>
  <link rel="stylesheet" href="../assets/css/app.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
</head>
<body class="app-body">

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <i class="fa-solid fa-qrcode"></i> <?= htmlspecialchars($sitename) ?>
  </div>
<?php
$baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
?>
  <nav class="sidebar-nav">
    <div class="nav-section">Main</div>
    <a href="<?= $baseUrl ?>/index.php"      class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'index.php'      ? 'active':'' ?>"><i class="fa fa-gauge"></i> Dashboard</a>
    <a href="<?= $baseUrl ?>/investment.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'investment.php' ? 'active':'' ?>"><i class="fa fa-robot"></i> AI Trading</a>

    <div class="nav-section">Funds</div>
    <a href="<?= $baseUrl ?>/deposit.php"    class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'deposit.php'    ? 'active':'' ?>"><i class="fa fa-arrow-down"></i> Deposit</a>
    <a href="<?= $baseUrl ?>/withdrawal.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'withdrawal.php' ? 'active':'' ?>"><i class="fa fa-arrow-up"></i> Withdraw</a>
    <a href="<?= $baseUrl ?>/history.php"    class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'history.php'    ? 'active':'' ?>"><i class="fa fa-clock-rotate-left"></i> History</a>

    <div class="nav-section">Account</div>
    <a href="<?= $baseUrl ?>/referral.php"   class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'referral.php'   ? 'active':'' ?>"><i class="fa fa-users"></i> Referrals</a>
    <a href="<?= $baseUrl ?>/profile.php"    class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'profile.php'    ? 'active':'' ?>">
      <i class="fa fa-id-card"></i> Profile
      <?php if ($status === 'Not Verified'): ?>
        <span class="nav-badge">KYC</span>
      <?php endif; ?>
    </a>
    <a href="<?= $baseUrl ?>/logout.php" class="nav-item"><i class="fa fa-right-from-bracket"></i> Logout</a>
  </nav>

  <!-- User chip at bottom -->
  <div class="sidebar-user">
    <div class="sidebar-user-name"><?= htmlspecialchars($fname . ' ' . $lname) ?></div>
    <div class="sidebar-user-email"><?= htmlspecialchars($email) ?></div>
    <div class="sidebar-kyc-status">
      <span class="badge badge-<?= strtolower(str_replace(' ', '-', $status)) ?>"><?= htmlspecialchars($status) ?></span>
    </div>
  </div>
</aside>

<!-- Top bar -->
<div class="topbar">
  <button class="menu-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')">
    <i class="fa fa-bars"></i>
  </button>
  <div class="topbar-right">
    <span class="topbar-greeting">Hi, <?= htmlspecialchars($fname) ?></span>
  </div>
</div>

<!-- Main content area -->
<main class="app-main">
<?php if ($is_demo): ?>
<div style="background:rgba(245,158,11,0.12);border:1px solid #f59e0b;color:#f59e0b;text-align:center;padding:9px 16px;font-size:0.82rem;font-weight:600;border-radius:8px;margin-bottom:18px;">
  Demo account — some actions are disabled. Activity here is visible to all buyers.
</div>
<?php endif; ?>
