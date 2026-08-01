<?php
require_once __DIR__ . '/session.php';

// Platform stats
$stats = [];

$q = $conn->query("SELECT COUNT(*) FROM users"); $stats['users'] = (int)$q->fetch_row()[0];
$q = $conn->query("SELECT COALESCE(SUM(amount),0) FROM deposits WHERE status='completed'"); $stats['deposits'] = (float)$q->fetch_row()[0];
$q = $conn->query("SELECT COALESCE(SUM(amount),0) FROM withdrawals WHERE status='approved'"); $stats['withdrawals'] = (float)$q->fetch_row()[0];
$q = $conn->query("SELECT COUNT(*) FROM investments WHERE status='running'"); $stats['active_inv'] = (int)$q->fetch_row()[0];
$q = $conn->query("SELECT COUNT(*) FROM deposits WHERE status='pending'"); $stats['pending_dep'] = (int)$q->fetch_row()[0];
$q = $conn->query("SELECT COUNT(*) FROM withdrawals WHERE status='pending'"); $stats['pending_wd'] = (int)$q->fetch_row()[0];
$q = $conn->query("SELECT COUNT(*) FROM users WHERE status='submitted'"); $stats['pending_kyc'] = (int)$q->fetch_row()[0];
?>
<?php include 'header.php'; ?>

<div class="adm-page-header">
  <div class="adm-page-title">Dashboard</div>
  <div class="adm-page-sub"><?= date('l, j F Y') ?></div>
</div>

<!-- Stat cards -->
<div class="adm-stat-grid">
  <div class="adm-stat-card">
    <div class="adm-stat-label">Total Users</div>
    <div class="adm-stat-value"><?= number_format($stats['users']) ?></div>
    <a href="users.php" class="adm-stat-link">Manage →</a>
  </div>
  <div class="adm-stat-card">
    <div class="adm-stat-label">Total Deposited</div>
    <div class="adm-stat-value">$<?= number_format($stats['deposits'], 2) ?></div>
    <a href="deposit.php" class="adm-stat-link">View deposits →</a>
  </div>
  <div class="adm-stat-card">
    <div class="adm-stat-label">Total Withdrawn</div>
    <div class="adm-stat-value">$<?= number_format($stats['withdrawals'], 2) ?></div>
    <a href="withdraw.php" class="adm-stat-link">View withdrawals →</a>
  </div>
  <div class="adm-stat-card">
    <div class="adm-stat-label">Active Investments</div>
    <div class="adm-stat-value"><?= number_format($stats['active_inv']) ?></div>
    <a href="investments.php" class="adm-stat-link">View all →</a>
  </div>
</div>

<!-- Action required -->
<div class="adm-action-row">
  <?php if ($stats['pending_dep'] > 0): ?>
  <a href="deposit.php?s=pending" class="adm-action-card adm-action-warn">
    <strong><?= $stats['pending_dep'] ?></strong> pending deposit<?= $stats['pending_dep'] > 1 ? 's' : '' ?> awaiting approval
  </a>
  <?php endif; ?>
  <?php if ($stats['pending_wd'] > 0): ?>
  <a href="withdraw.php?s=pending" class="adm-action-card adm-action-warn">
    <strong><?= $stats['pending_wd'] ?></strong> pending withdrawal<?= $stats['pending_wd'] > 1 ? 's' : '' ?> awaiting processing
  </a>
  <?php endif; ?>
  <?php if ($stats['pending_kyc'] > 0): ?>
  <a href="kyc.php?s=submitted" class="adm-action-card adm-action-info">
    <strong><?= $stats['pending_kyc'] ?></strong> KYC submission<?= $stats['pending_kyc'] > 1 ? 's' : '' ?> to review
  </a>
  <?php endif; ?>
  <?php if (!$stats['pending_dep'] && !$stats['pending_wd'] && !$stats['pending_kyc']): ?>
  <div class="adm-action-card adm-action-ok">All clear — no pending actions.</div>
  <?php endif; ?>
</div>

<!-- Recent users + recent deposits -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:24px;">

  <div class="adm-card">
    <div class="adm-card-header">
      <span class="adm-card-title">Recent Users</span>
      <a href="users.php" class="adm-link-sm">View all →</a>
    </div>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>Name</th><th>Country</th><th>Balance</th><th>Status</th></tr></thead>
        <tbody>
        <?php
          $q = $conn->query("SELECT fname, lname, country, balance, status FROM users ORDER BY date DESC LIMIT 8");
          while ($r = $q->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($r['fname'] . ' ' . $r['lname']) ?></td>
            <td><?= htmlspecialchars($r['country'] ?? '—') ?></td>
            <td>$<?= number_format((float)$r['balance'], 2) ?></td>
            <td><span class="badge badge-<?= strtolower(str_replace(' ', '-', $r['status'])) ?>"><?= htmlspecialchars($r['status']) ?></span></td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="adm-card">
    <div class="adm-card-header">
      <span class="adm-card-title">Pending Deposits</span>
      <a href="deposit.php" class="adm-link-sm">View all →</a>
    </div>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>User</th><th>Method</th><th>Amount</th><th>Date</th></tr></thead>
        <tbody>
        <?php
          $q = $conn->query(
            "SELECT d.amount, d.method, d.date, u.fname, u.lname
             FROM deposits d JOIN users u ON u.id = d.userId
             WHERE d.status = 'pending' ORDER BY d.date DESC LIMIT 8"
          );
          if ($q->num_rows === 0): ?>
            <tr><td colspan="4" class="empty">No pending deposits.</td></tr>
          <?php else: while ($r = $q->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($r['fname'] . ' ' . $r['lname']) ?></td>
            <td><?= htmlspecialchars($r['method']) ?></td>
            <td>$<?= number_format((float)$r['amount'], 2) ?></td>
            <td><?= date('d M', strtotime($r['date'])) ?></td>
          </tr>
        <?php endwhile; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<?php include 'footer.php'; ?>
