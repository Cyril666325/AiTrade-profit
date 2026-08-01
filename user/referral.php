<?php
require_once __DIR__ . '/session.php';

// Referrals list
$rq = $conn->prepare(
    "SELECT fname, lname, email, date FROM users WHERE referral = ? ORDER BY date DESC"
);
$rq->bind_param('s', $refcode);
$rq->execute();
$referrals = $rq->get_result()->fetch_all(MYSQLI_ASSOC);
$rq->close();
?>
<?php include 'header.php'; ?>

<div class="page-header">
  <h2>Referral Program</h2>
  <nav class="breadcrumb"><a href="index.php">Dashboard</a> / Referrals</nav>
</div>

<!-- Stats -->
<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-label">Referral Bonus</div>
    <div class="stat-value">$<?= number_format($ref_balance, 2) ?></div>
    <div class="stat-sub">Earned from referrals</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Total Referrals</div>
    <div class="stat-value"><?= count($referrals) ?></div>
    <div class="stat-sub">Users you referred</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Commission Rate</div>
    <div class="stat-value"><?= number_format($ref_bonus, 0) ?>%</div>
    <div class="stat-sub">Per approved deposit</div>
  </div>
</div>

<!-- Referral link -->
<div class="card" style="margin-top:20px;">
  <div class="card-title">Your Referral Link</div>
  <p style="font-size:0.84rem;color:var(--text-muted);margin-bottom:14px;">
    Share this link. When someone signs up and makes a deposit, you earn <?= number_format($ref_bonus, 0) ?>% commission automatically.
  </p>
  <div class="ref-link-wrap">
    <input type="text" id="refInput" value="<?= htmlspecialchars($siteurl) ?>/user/signup.php?ref=<?= htmlspecialchars($refcode) ?>" readonly class="ref-link-input">
    <button onclick="copyRef()" class="btn-primary" id="refCopyBtn">Copy</button>
  </div>
  <div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--border);">
    <span style="font-size:0.74rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.8px;">Your Code</span>
    <div style="font-size:1.4rem;font-weight:800;color:var(--primary);letter-spacing:3px;margin-top:4px;"><?= htmlspecialchars($refcode) ?></div>
  </div>
</div>

<!-- Referrals table -->
<div class="card" style="margin-top:20px;">
  <div class="card-title">Referred Users (<?= count($referrals) ?>)</div>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Name</th><th>Email</th><th>Joined</th></tr></thead>
      <tbody>
      <?php if (empty($referrals)): ?>
        <tr><td colspan="3" class="empty">No referrals yet. Share your link!</td></tr>
      <?php else:
        foreach ($referrals as $r): ?>
        <tr>
          <td><?= htmlspecialchars($r['fname'] . ' ' . $r['lname']) ?></td>
          <td><?= htmlspecialchars($r['email']) ?></td>
          <td><?= date('d M Y', strtotime($r['date'])) ?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
function copyRef() {
  const inp = document.getElementById('refInput');
  inp.select(); inp.setSelectionRange(0, 99999);
  navigator.clipboard.writeText(inp.value).then(() => {
    const btn = document.getElementById('refCopyBtn');
    btn.textContent = 'Copied!';
    setTimeout(() => btn.textContent = 'Copy', 2000);
  });
}
</script>

<?php include 'footer.php'; ?>
