<?php
require_once __DIR__ . '/session.php';

$filter = in_array($_GET['s'] ?? '', ['running','completed','cancelled']) ? $_GET['s'] : 'running';

$stmt = $conn->prepare(
    "SELECT i.*, u.fname, u.lname, u.email
     FROM investments i JOIN users u ON u.id = i.userId
     WHERE i.status = ? ORDER BY i.date DESC"
);
$stmt->bind_param('s', $filter);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<?php include 'header.php'; ?>

<div class="adm-page-header">
  <div class="adm-page-title">Investments</div>
</div>

<div class="tab-bar">
  <a href="?s=running"   class="tab-link <?= $filter==='running'   ? 'active':'' ?>">Running</a>
  <a href="?s=completed" class="tab-link <?= $filter==='completed' ? 'active':'' ?>">Completed</a>
  <a href="?s=cancelled" class="tab-link <?= $filter==='cancelled' ? 'active':'' ?>">Cancelled</a>
</div>

<div class="adm-card" style="margin-top:0;border-top-left-radius:0;border-top-right-radius:0;">
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr><th>#</th><th>User</th><th>Plan</th><th>Bot</th><th>Amount</th><th>Profit</th><th>ROI%</th><th>Start</th><th>Matures</th><th>Status</th></tr>
      </thead>
      <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="10" class="empty">No <?= $filter ?> investments.</td></tr>
      <?php else: foreach ($rows as $r): ?>
        <tr>
          <td><?= $r['id'] ?></td>
          <td>
            <?= htmlspecialchars($r['fname'] . ' ' . $r['lname']) ?>
            <div style="font-size:0.72rem;color:var(--adm-text-muted)"><?= htmlspecialchars($r['email']) ?></div>
          </td>
          <td><?= htmlspecialchars($r['plan_name']) ?></td>
          <td><?= ucfirst($r['bot_type']) ?></td>
          <td>$<?= number_format((float)$r['invest_amt'], 2) ?></td>
          <td style="color:#10b981">$<?= number_format((float)$r['total_profit'], 2) ?></td>
          <td><?= $r['increase'] ?>%</td>
          <td><?= date('d M Y', strtotime($r['start_date'])) ?></td>
          <td><?= date('d M Y', strtotime($r['end_date'])) ?></td>
          <td><span class="badge badge-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include 'footer.php'; ?>
