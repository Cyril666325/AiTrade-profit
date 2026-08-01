<?php
require_once __DIR__ . '/session.php';

$msg = $error = '';

if ($is_demo && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = 'This action is disabled in the demo account.';
}

if (!$is_demo && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action   = $_POST['action']   ?? '';
    $planId   = (int)($_POST['id'] ?? 0);

    if ($action === 'save') {
        $pname    = text_input($_POST['pname']    ?? '');
        $bot_type = text_input($_POST['bot_type'] ?? 'scalper');
        $increase = floatval($_POST['increase']   ?? 0);
        $duration = (int)($_POST['duration']      ?? 1);
        $duration_unit = text_input($_POST['duration_unit'] ?? 'days');
        $min_amt  = floatval($_POST['min_amt']    ?? 0);
        $max_amt  = floatval($_POST['max_amt']    ?? 0);

        if (empty($pname) || $increase <= 0 || $duration <= 0 || $min_amt <= 0 || $max_amt <= $min_amt || !in_array($duration_unit, ['minutes', 'hours', 'days'])) {
            $error = 'Fill all fields correctly. Max must be greater than min.';
        } else {
            if ($planId > 0) {
                $stmt = $conn->prepare(
                    "UPDATE plans SET pname=?, bot_type=?, increase=?, duration=?, duration_unit=?, min_amt=?, max_amt=? WHERE id=?"
                );
                $stmt->bind_param('ssdisddi', $pname, $bot_type, $increase, $duration, $duration_unit, $min_amt, $max_amt, $planId);
            } else {
                $stmt = $conn->prepare(
                    "INSERT INTO plans (pname, bot_type, increase, duration, duration_unit, min_amt, max_amt) VALUES (?,?,?,?,?,?,?)"
                );
                $stmt->bind_param('ssdisdd', $pname, $bot_type, $increase, $duration, $duration_unit, $min_amt, $max_amt);
            }
            $stmt->execute() ? $msg = 'Plan saved.' : $error = 'Failed to save plan.';
            $stmt->close();
        }
    }

    elseif ($action === 'delete' && $planId > 0) {
        $del = $conn->prepare("DELETE FROM plans WHERE id = ?");
        $del->bind_param('i', $planId);
        $del->execute();
        $del->close();
        $msg = "Plan #$planId deleted.";
    }
}

$plans = $conn->query("SELECT p.*, b.bot_name, b.win_rate, b.accuracy FROM plans p LEFT JOIN bot_settings b ON b.plan_id = p.id ORDER BY p.min_amt ASC")->fetch_all(MYSQLI_ASSOC);
?>
<?php include 'header.php'; ?>

<div class="adm-page-header"><div class="adm-page-title">Investment Plans</div></div>

<?php if ($msg):   ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<!-- Add plan form -->
<div class="adm-card" style="margin-bottom:20px;">
  <div class="adm-card-title">Add / Edit Plan</div>
  <form method="POST">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" id="editPlanId" value="0">
    <div class="form-row">
      <div class="form-group"><label>Plan Name</label><input type="text" name="pname" id="editPname" required placeholder="AI Scalper"></div>
      <div class="form-group"><label>Bot Type</label>
        <select name="bot_type" id="editBotType">
          <option value="scalper">Scalper</option>
          <option value="arbitrage">Arbitrage</option>
          <option value="trend">Trend</option>
          <option value="quantum">Quantum</option>
        </select>
      </div>
      <div class="form-group"><label>ROI %</label><input type="number" name="increase" id="editIncrease" step="0.01" min="0.01" required></div>
      <div class="form-group"><label>Duration</label>
        <div style="display:flex; gap:8px;">
          <input type="number" name="duration" id="editDuration" min="1" required style="width:60%">
          <select name="duration_unit" id="editDurationUnit" style="width:40%">
            <option value="minutes">Mins</option>
            <option value="hours">Hrs</option>
            <option value="days">Days</option>
          </select>
        </div>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Min Amount ($)</label><input type="number" name="min_amt" id="editMin" step="0.01" min="1" required></div>
      <div class="form-group"><label>Max Amount ($)</label><input type="number" name="max_amt" id="editMax" step="0.01" min="1" required></div>
    </div>
    <button type="submit" class="btn-primary">Save Plan</button>
    <button type="button" class="btn-ghost" onclick="clearForm()">Clear</button>
  </form>
</div>

<!-- Plans table -->
<div class="adm-card">
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>#</th><th>Name</th><th>Bot</th><th>ROI %</th><th>Duration</th><th>Min</th><th>Max</th><th>Win Rate</th><th>Actions</th></tr></thead>
      <tbody>
      <?php if (empty($plans)): ?>
        <tr><td colspan="9" class="empty">No plans configured.</td></tr>
      <?php else: foreach ($plans as $p): ?>
        <tr>
          <td><?= $p['id'] ?></td>
          <td><?= htmlspecialchars($p['pname']) ?></td>
          <td><?= ucfirst($p['bot_type']) ?></td>
          <td><?= $p['increase'] ?>%</td>
          <td><?= $p['duration'] ?> <?= substr($p['duration_unit'] ?? 'days', 0, 1) ?></td>
          <td>$<?= number_format((float)$p['min_amt'], 2) ?></td>
          <td>$<?= number_format((float)$p['max_amt'], 2) ?></td>
          <td><?= $p['win_rate'] ?? '—' ?>%</td>
          <td>
            <button class="btn-sm" onclick="editPlan(<?= htmlspecialchars(json_encode($p)) ?>)" title="Edit"><i class="fa fa-pen"></i></button>
            <a href="bot_settings.php?plan=<?= $p['id'] ?>" class="btn-sm" title="Bot Settings"><i class="fa fa-robot"></i></a>
            <form method="POST" style="display:inline" onsubmit="return confirm('Delete plan?')">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= $p['id'] ?>">
              <button type="submit" class="btn-sm btn-danger" title="Delete"><i class="fa fa-trash"></i></button>
            </form>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
function editPlan(p) {
  document.getElementById('editPlanId').value   = p.id;
  document.getElementById('editPname').value    = p.pname;
  document.getElementById('editBotType').value  = p.bot_type;
  document.getElementById('editIncrease').value = p.increase;
  document.getElementById('editDuration').value = p.duration;
  document.getElementById('editDurationUnit').value = p.duration_unit || 'days';
  document.getElementById('editMin').value      = p.min_amt;
  document.getElementById('editMax').value      = p.max_amt;
  window.scrollTo({top: 0, behavior: 'smooth'});
}
function clearForm() {
  document.getElementById('editPlanId').value = '0';
  document.getElementById('editDurationUnit').value = 'days';
  ['editPname','editIncrease','editDuration','editMin','editMax'].forEach(id => document.getElementById(id).value = '');
}
</script>

<?php include 'footer.php'; ?>
