<?php
require_once __DIR__ . '/session.php';

$msg = $error = '';

if ($is_demo && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = 'This action is disabled in the demo account.';
}

if (!$is_demo && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $pair       = text_input($_POST['pair']       ?? '');
        $act        = in_array($_POST['act'] ?? '', ['BUY','SELL']) ? $_POST['act'] : 'BUY';
        $entry      = floatval($_POST['entry_price']  ?? 0);
        $exit       = $_POST['exit_price']  !== '' ? floatval($_POST['exit_price'])  : null;
        $profit_pct = $_POST['profit_pct']  !== '' ? floatval($_POST['profit_pct'])  : null;
        $status     = in_array($_POST['status'] ?? '', ['open','closed']) ? $_POST['status'] : 'closed';
        $bot_type   = text_input($_POST['bot_type'] ?? 'scalper');

        if (empty($pair) || $entry <= 0) {
            $error = 'Pair and entry price are required.';
        } else {
            $ins = $conn->prepare(
                "INSERT INTO ai_trades (pair, action, entry_price, exit_price, profit_pct, status, bot_type)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $ins->bind_param('ssdddss', $pair, $act, $entry, $exit, $profit_pct, $status, $bot_type);
            $ins->execute() ? $msg = 'Trade added.' : $error = 'Failed to add trade.';
            $ins->close();
        }
    }

    elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $del = $conn->prepare("DELETE FROM ai_trades WHERE id = ?");
        $del->bind_param('i', $id);
        $del->execute();
        $del->close();
        $msg = 'Trade deleted.';
    }

    elseif ($action === 'toggle_autogen') {
        $newVal = (int)($_POST['autogen'] ?? 0);
        $upd = $conn->prepare("UPDATE settings SET ai_autogen = ? WHERE id = 1");
        $upd->bind_param('i', $newVal);
        $upd->execute();
        $upd->close();
        $settingsRow['ai_autogen'] = $newVal;
        $msg = 'Auto-generate setting updated.';
    }
}
?>
<?php include 'header.php'; ?>

<div class="adm-page-header">
  <div class="adm-page-title">AI Trade Manager</div>
</div>

<?php if ($msg):   ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<!-- Auto-generate toggle -->
<div class="adm-card" style="margin-bottom:20px;">
  <div class="adm-card-title">Auto-Generation (Cron)</div>
  <p style="font-size:0.83rem;color:var(--text-muted);margin-bottom:12px;">
    When enabled, the cron job automatically generates 2–8 realistic AI trades per run using live crypto prices.
  </p>
  <form method="POST">
    <input type="hidden" name="action" value="toggle_autogen">
    <?php $ag = (int)($settingsRow['ai_autogen'] ?? 1); ?>
    <input type="hidden" name="autogen" value="<?= $ag ? 0 : 1 ?>">
    <button type="submit" class="<?= $ag ? 'btn-danger' : 'btn-primary' ?>">
      <?= $ag ? 'Disable Auto-Generate' : 'Enable Auto-Generate' ?>
    </button>
    <span style="margin-left:12px;font-size:0.84rem;color:<?= $ag ? '#10b981' : 'var(--text-muted)' ?>">
      Currently: <?= $ag ? 'ENABLED' : 'DISABLED' ?>
    </span>
  </form>
</div>

<!-- Add trade form -->
<div class="adm-card" style="margin-bottom:20px;">
  <div class="adm-card-title">Add Manual Trade</div>
  <form method="POST">
    <input type="hidden" name="action" value="add">
    <div class="form-row">
      <div class="form-group">
        <label>Pair</label>
        <input type="text" name="pair" placeholder="e.g. BTC/USDT" required>
      </div>
      <div class="form-group">
        <label>Action</label>
        <select name="act">
          <option value="BUY">BUY</option>
          <option value="SELL">SELL</option>
        </select>
      </div>
      <div class="form-group">
        <label>Bot Type</label>
        <select name="bot_type">
          <option value="scalper">Scalper</option>
          <option value="arbitrage">Arbitrage</option>
          <option value="trend">Trend</option>
          <option value="quantum">Quantum</option>
        </select>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Entry Price ($)</label>
        <input type="number" name="entry_price" step="0.0001" required>
      </div>
      <div class="form-group">
        <label>Exit Price ($) <span style="color:var(--text-muted)">(optional)</span></label>
        <input type="number" name="exit_price" step="0.0001">
      </div>
      <div class="form-group">
        <label>Profit % <span style="color:var(--text-muted)">(optional)</span></label>
        <input type="number" name="profit_pct" step="0.01">
      </div>
      <div class="form-group">
        <label>Status</label>
        <select name="status">
          <option value="closed">Closed</option>
          <option value="open">Open</option>
        </select>
      </div>
    </div>
    <button type="submit" class="btn-primary">Add Trade</button>
  </form>
</div>

<!-- Trades table -->
<div class="adm-card">
  <div class="adm-card-title">Recent AI Trades</div>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>#</th><th>Time</th><th>Pair</th><th>Action</th><th>Entry</th><th>Exit</th><th>P/L%</th><th>Bot</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php
        $rows = $conn->query("SELECT * FROM ai_trades ORDER BY created_at DESC LIMIT 100");
        if ($rows->num_rows === 0): ?>
          <tr><td colspan="10" class="empty">No trades yet.</td></tr>
        <?php else: while ($r = $rows->fetch_assoc()): ?>
          <tr>
            <td><?= $r['id'] ?></td>
            <td><?= date('d M H:i', strtotime($r['created_at'])) ?></td>
            <td class="mono"><?= htmlspecialchars($r['pair']) ?></td>
            <td><span class="badge badge-<?= strtolower($r['action']) ?>"><?= $r['action'] ?></span></td>
            <td>$<?= number_format((float)$r['entry_price'], 2) ?></td>
            <td><?= $r['exit_price'] !== null ? '$' . number_format((float)$r['exit_price'], 2) : '—' ?></td>
            <td style="color:#10b981"><?= $r['profit_pct'] !== null ? '+' . $r['profit_pct'] . '%' : '—' ?></td>
            <td><?= ucfirst(htmlspecialchars($r['bot_type'])) ?></td>
            <td><span class="badge badge-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
            <td>
              <form method="POST" onsubmit="return confirm('Delete trade?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                <button type="submit" class="btn-sm btn-danger">Del</button>
              </form>
            </td>
          </tr>
        <?php endwhile; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include 'footer.php'; ?>
