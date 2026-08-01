<?php
require_once __DIR__ . '/session.php';
$tab = in_array($_GET['tab'] ?? '', ['deposits','withdrawals','investments','ai_trades']) ? $_GET['tab'] : 'deposits';
?>
<?php include 'header.php'; ?>

<div class="page-header">
  <h2>Transaction History</h2>
  <nav class="breadcrumb"><a href="index.php">Dashboard</a> / History</nav>
</div>

<!-- Tabs -->
<div class="tab-bar">
  <a href="?tab=deposits"    class="tab-link <?= $tab==='deposits'    ? 'active':'' ?>">Deposits</a>
  <a href="?tab=withdrawals" class="tab-link <?= $tab==='withdrawals' ? 'active':'' ?>">Withdrawals</a>
  <a href="?tab=investments" class="tab-link <?= $tab==='investments' ? 'active':'' ?>">Investments</a>
  <a href="?tab=ai_trades"   class="tab-link <?= $tab==='ai_trades'   ? 'active':'' ?>">AI Trades</a>
</div>

<div class="card" style="margin-top:0;border-top-left-radius:0;border-top-right-radius:0;">
<?php if ($tab === 'deposits'): ?>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Date</th><th>Trans. ID</th><th>Method</th><th>Amount</th><th>Status</th></tr></thead>
      <tbody>
      <?php
        $q = $conn->prepare("SELECT * FROM deposits WHERE userId = ? ORDER BY date DESC");
        $q->bind_param('i', $userId); $q->execute();
        $rows = $q->get_result();
        if ($rows->num_rows === 0): ?><tr><td colspan="5" class="empty">No deposits found.</td></tr>
        <?php else: while ($r = $rows->fetch_assoc()): ?>
          <tr>
            <td><?= date('d M Y H:i', strtotime($r['date'])) ?></td>
            <td class="mono"><?= htmlspecialchars($r['tranxId']) ?></td>
            <td><?= htmlspecialchars($r['method']) ?></td>
            <td>$<?= number_format((float)$r['amount'], 2) ?></td>
            <td><span class="badge badge-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
          </tr>
        <?php endwhile; endif; $q->close(); ?>
      </tbody>
    </table>
  </div>

<?php elseif ($tab === 'withdrawals'): ?>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Date</th><th>Trans. ID</th><th>Method</th><th>Amount</th><th>Status</th></tr></thead>
      <tbody>
      <?php
        $q = $conn->prepare("SELECT * FROM withdrawals WHERE userId = ? ORDER BY date DESC");
        $q->bind_param('i', $userId); $q->execute();
        $rows = $q->get_result();
        if ($rows->num_rows === 0): ?><tr><td colspan="5" class="empty">No withdrawals found.</td></tr>
        <?php else: while ($r = $rows->fetch_assoc()): ?>
          <tr>
            <td><?= date('d M Y H:i', strtotime($r['date'])) ?></td>
            <td class="mono"><?= htmlspecialchars($r['tranxId']) ?></td>
            <td><?= htmlspecialchars($r['method']) ?></td>
            <td>$<?= number_format((float)$r['amount'], 2) ?></td>
            <td><span class="badge badge-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
          </tr>
        <?php endwhile; endif; $q->close(); ?>
      </tbody>
    </table>
  </div>

<?php elseif ($tab === 'investments'): ?>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Date</th><th>Plan</th><th>Bot</th><th>Amount</th><th>Profit</th><th>Matures</th><th>Status</th></tr></thead>
      <tbody>
      <?php
        $q = $conn->prepare("SELECT * FROM investments WHERE userId = ? ORDER BY date DESC");
        $q->bind_param('i', $userId); $q->execute();
        $rows = $q->get_result();
        if ($rows->num_rows === 0): ?><tr><td colspan="7" class="empty">No investments found.</td></tr>
        <?php else: while ($r = $rows->fetch_assoc()): ?>
          <tr>
            <td><?= date('d M Y', strtotime($r['date'])) ?></td>
            <td><?= htmlspecialchars($r['plan_name']) ?></td>
            <td><?= ucfirst($r['bot_type']) ?></td>
            <td>$<?= number_format((float)$r['invest_amt'], 2) ?></td>
            <td>$<?= number_format((float)$r['total_profit'], 2) ?></td>
            <td><?= date('d M Y', strtotime($r['end_date'])) ?></td>
            <td><span class="badge badge-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
          </tr>
        <?php endwhile; endif; $q->close(); ?>
      </tbody>
    </table>
  </div>

<?php elseif ($tab === 'ai_trades'): ?>
  <p style="font-size:0.83rem;color:var(--text-muted);padding:12px 0 4px;">
    Live AI trade activity across all bots on the platform.
  </p>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Time</th><th>Pair</th><th>Action</th><th>Entry</th><th>Exit</th><th>P/L</th><th>Bot</th><th>Status</th></tr></thead>
      <tbody>
      <?php
        $q = $conn->prepare("SELECT * FROM ai_trades ORDER BY created_at DESC LIMIT 50");
        $q->execute();
        $rows = $q->get_result();
        if ($rows->num_rows === 0): ?><tr><td colspan="8" class="empty">No AI trades recorded yet.</td></tr>
        <?php else: while ($r = $rows->fetch_assoc()): ?>
          <tr>
            <td><?= date('d M H:i', strtotime($r['created_at'])) ?></td>
            <td class="mono"><?= htmlspecialchars($r['pair']) ?></td>
            <td><span class="badge badge-<?= strtolower($r['action']) ?>"><?= $r['action'] ?></span></td>
            <td>$<?= number_format((float)$r['entry_price'], 2) ?></td>
            <td><?= $r['exit_price'] ? '$' . number_format((float)$r['exit_price'], 2) : '—' ?></td>
            <td style="color:<?= ($r['profit_pct'] ?? 0) >= 0 ? '#10b981' : '#ef4444' ?>">
              <?= $r['profit_pct'] !== null ? '+' . $r['profit_pct'] . '%' : '—' ?>
            </td>
            <td><?= ucfirst(htmlspecialchars($r['bot_type'])) ?></td>
            <td><span class="badge badge-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
          </tr>
        <?php endwhile; endif; $q->close(); ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
</div>

<?php include 'footer.php'; ?>
