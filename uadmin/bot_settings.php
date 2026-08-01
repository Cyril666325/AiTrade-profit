<?php
require_once __DIR__ . '/session.php';

$msg = $error = '';

if ($is_demo && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = 'This action is disabled in the demo account.';
}

if (!$is_demo && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $plan_id        = (int)($_POST['plan_id']        ?? 0);
    $bot_name       = text_input($_POST['bot_name']       ?? '');
    $bot_type       = text_input($_POST['bot_type']       ?? 'scalper');
    $win_rate       = floatval($_POST['win_rate']         ?? 87.5);
    $accuracy       = floatval($_POST['accuracy']         ?? 92.3);
    $uptime_pct     = floatval($_POST['uptime_pct']       ?? 99.7);
    $trades_per_day = (int)($_POST['trades_per_day']      ?? 45);

    // Upsert bot settings
    $chk = $conn->prepare("SELECT id FROM bot_settings WHERE plan_id = ? LIMIT 1");
    $chk->bind_param('i', $plan_id);
    $chk->execute();
    $chk->store_result();
    $exists = $chk->num_rows > 0;
    $chk->close();

    if ($exists) {
        $stmt = $conn->prepare(
            "UPDATE bot_settings SET bot_name=?, bot_type=?, win_rate=?, accuracy=?, uptime_pct=?, trades_per_day=? WHERE plan_id=?"
        );
        $stmt->bind_param('ssdddii', $bot_name, $bot_type, $win_rate, $accuracy, $uptime_pct, $trades_per_day, $plan_id);
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO bot_settings (plan_id, bot_name, bot_type, win_rate, accuracy, uptime_pct, trades_per_day) VALUES (?,?,?,?,?,?,?)"
        );
        $stmt->bind_param('issddddi', $plan_id, $bot_name, $bot_type, $win_rate, $accuracy, $uptime_pct, $trades_per_day);
    }
    $stmt->execute() ? $msg = 'Bot settings saved.' : $error = 'Failed to save.';
    $stmt->close();
}

$planFilter = (int)($_GET['plan'] ?? 0);
$plans = $conn->query(
    "SELECT p.*, b.id as bs_id, b.bot_name, b.bot_type as b_type, b.win_rate, b.accuracy, b.uptime_pct, b.trades_per_day
     FROM plans p LEFT JOIN bot_settings b ON b.plan_id = p.id ORDER BY p.min_amt ASC"
)->fetch_all(MYSQLI_ASSOC);
?>
<?php include 'header.php'; ?>

<div class="adm-page-header"><div class="adm-page-title">Bot Settings</div></div>

<?php if ($msg):   ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<p style="font-size:0.84rem;color:var(--text-muted);margin-bottom:16px;">
  These are the display values shown to users on their dashboard and plan cards. They are cosmetic and do not affect actual ROI calculations.
</p>

<?php foreach ($plans as $p): ?>
<div class="adm-card" style="margin-bottom:20px;" id="plan-<?= $p['id'] ?>">
  <div class="adm-card-title"><?= htmlspecialchars($p['pname']) ?> — Bot Settings</div>
  <form method="POST">
    <input type="hidden" name="plan_id" value="<?= $p['id'] ?>">
    <div class="form-row">
      <div class="form-group">
        <label>Bot Name</label>
        <input type="text" name="bot_name" value="<?= htmlspecialchars($p['bot_name'] ?? 'AI Bot v1.0') ?>" placeholder="AI Scalper v2.4">
      </div>
      <div class="form-group">
        <label>Bot Type</label>
        <select name="bot_type">
          <?php foreach (['scalper','arbitrage','trend','quantum'] as $bt): ?>
            <option value="<?= $bt ?>" <?= ($p['b_type'] ?? $p['bot_type']) === $bt ? 'selected' : '' ?>><?= ucfirst($bt) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Win Rate (%)</label><input type="number" name="win_rate" step="0.01" min="0" max="100" value="<?= $p['win_rate'] ?? 87.5 ?>"></div>
      <div class="form-group"><label>Accuracy (%)</label><input type="number" name="accuracy" step="0.01" min="0" max="100" value="<?= $p['accuracy'] ?? 92.3 ?>"></div>
      <div class="form-group"><label>Uptime (%)</label><input type="number" name="uptime_pct" step="0.01" min="0" max="100" value="<?= $p['uptime_pct'] ?? 99.7 ?>"></div>
      <div class="form-group"><label>Trades/Day</label><input type="number" name="trades_per_day" min="1" value="<?= $p['trades_per_day'] ?? 45 ?>"></div>
    </div>
    <button type="submit" class="btn-primary">Save</button>
  </form>
</div>
<?php endforeach; ?>

<?php include 'footer.php'; ?>
