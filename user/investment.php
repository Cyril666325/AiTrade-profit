<?php
require_once __DIR__ . '/session.php';

$error = $msg = '';

if ($is_demo && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = 'Activating investments is disabled in the demo account.';
}

if (!$is_demo && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $planId = (int)($_POST['plan'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);

    if ($planId <= 0) {
        $error = 'Please select a valid plan.';
    } elseif ($amount <= 0) {
        $error = 'Enter an investment amount.';
    } elseif ($amount > $balance) {
        $error = 'Insufficient balance. Please deposit funds first.';
    } else {
        $pq = $conn->prepare("SELECT * FROM plans WHERE id = ? LIMIT 1");
        $pq->bind_param('i', $planId);
        $pq->execute();
        $plan = $pq->get_result()->fetch_assoc();
        $pq->close();

        if (!$plan) {
            $error = 'Invalid plan selected.';
        } elseif ($amount < (float)$plan['min_amt']) {
            $error = 'Amount is below the minimum of $' . number_format((float)$plan['min_amt'], 2);
        } elseif ($amount > (float)$plan['max_amt']) {
            $error = 'Amount exceeds the maximum of $' . number_format((float)$plan['max_amt'], 2);
        } else {
            $total_profit = round(($plan['increase'] / 100) * $amount, 2);
            $start_date   = date('Y-m-d H:i:s');
            $unit         = $plan['duration_unit'] ?? 'days';
            $end_date     = date('Y-m-d H:i:s', strtotime('+' . (int)$plan['duration'] . ' ' . $unit));
            $bot_type     = $plan['bot_type'];
            $plan_name    = $plan['pname'];
            $increase     = $plan['increase'];
            $duration     = (int)$plan['duration'];

            $ins = $conn->prepare(
                "INSERT INTO investments
                 (userId, planId, invest_amt, plan_name, bot_type, increase, duration, total_profit, start_date, end_date, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'running')"
            );
            $ins->bind_param('iidssidiss',
                $userId, $planId, $amount, $plan_name, $bot_type,
                $increase, $duration, $total_profit, $start_date, $end_date
            );
            if ($ins->execute()) {
                // Deduct from balance
                $upd = $conn->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
                $upd->bind_param('di', $amount, $userId);
                $upd->execute();
                $upd->close();

                $balance -= $amount; // Update local var for display

                $body = "<p>Hi <strong>$fname</strong>,</p>
                         <p>Your investment has been activated!</p>
                         <ul>
                           <li><strong>Plan:</strong> $plan_name</li>
                           <li><strong>Bot:</strong> AI " . ucfirst($bot_type) . "</li>
                           <li><strong>Amount:</strong> \$$amount</li>
                           <li><strong>ROI:</strong> {$increase}%</li>
                           <li><strong>Profit:</strong> \$$total_profit</li>
                           <li><strong>Matures:</strong> " . date('D, j M Y', strtotime($end_date)) . "</li>
                         </ul>";
                sendMail($email, "Investment Activated — $sitename", $body);
                $msg = "Investment activated! Your AI bot is now running.";
            } else {
                $error = 'Failed to start investment. Please try again.';
            }
            $ins->close();
        }
    }
}

// Load all plans with their bot settings
$plansQ = $conn->prepare(
    "SELECT p.*, b.bot_name, b.win_rate, b.accuracy, b.uptime_pct, b.trades_per_day
     FROM plans p
     LEFT JOIN bot_settings b ON b.plan_id = p.id
     ORDER BY p.min_amt ASC"
);
$plansQ->execute();
$plans = $plansQ->get_result()->fetch_all(MYSQLI_ASSOC);
$plansQ->close();

// Active investment
$aq = $conn->prepare(
    "SELECT i.*, b.bot_name FROM investments i
     LEFT JOIN bot_settings b ON b.plan_id = i.planId
     WHERE i.userId = ? AND i.status = 'running'
     ORDER BY i.id DESC LIMIT 1"
);
$aq->bind_param('i', $userId);
$aq->execute();
$activeInv = $aq->get_result()->fetch_assoc();
$aq->close();
?>
<?php include 'header.php'; ?>

<div class="page-header">
  <h2>Investment Plans</h2>
  <nav class="breadcrumb"><a href="index.php">Dashboard</a> / Invest</nav>
</div>

<?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($msg):   ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<!-- Wallet balance reminder -->
<div class="alert-banner info" style="justify-content: center; font-size: 0.9rem;">
  <span>Available Balance: <strong>$<?= number_format($balance, 2) ?></strong></span>
  <?php if ($balance < 50): ?>
    <span style="margin: 0 8px;">—</span> <a href="deposit.php" class="alert-banner-link" style="margin-left: 0;">Deposit Funds →</a>
  <?php endif; ?>
</div>

<!-- Active investment banner -->
<?php if ($activeInv): ?>
<div class="alert-banner" style="background: rgba(0, 255, 170, 0.08); border-color: rgba(0, 255, 170, 0.2); color: var(--success); justify-content: center;">
  <span class="pulse-dot"></span>
  <span><strong><?= htmlspecialchars($activeInv['bot_name'] ?? 'AI Bot') ?></strong> is running your 
  <strong><?= htmlspecialchars($activeInv['plan_name']) ?></strong> plan — 
  $<?= number_format((float)$activeInv['invest_amt'], 2) ?> invested · 
  matures <?= date('j M Y', strtotime($activeInv['end_date'])) ?></span>
  <a href="index.php" class="alert-banner-link" style="margin-left:12px;">View Dashboard →</a>
</div>
<?php endif; ?>

<!-- Plan cards -->
<div class="plan-grid">
  <?php foreach ($plans as $p):
    $botTypeLabel = strtoupper($p['bot_type']);
    $botColors = [
      'scalper'   => '#3b82f6',
      'arbitrage' => '#8b5cf6',
      'trend'     => '#10b981',
      'quantum'   => '#f59e0b',
    ];
    $botColor = $botColors[$p['bot_type']] ?? '#c9a84c';
  ?>
  <div class="plan-card">
    <div class="plan-card-badge" style="background:<?= $botColor ?>22;color:<?= $botColor ?>;">
      <?= $botTypeLabel ?>
    </div>
    <div class="plan-card-name"><?= htmlspecialchars($p['pname']) ?></div>
    <div class="plan-card-detail"><?= htmlspecialchars($p['bot_name'] ?? 'AI Bot') ?></div>
    <div class="plan-card-roi">+<?= number_format((float)$p['increase'], 1) ?>% <span>ROI</span></div>
    <div class="plan-card-detail">$<?= number_format((float)$p['min_amt'], 0) ?> – $<?= number_format((float)$p['max_amt'], 0) ?> limit</div>
    
    <div class="plan-card-stats">
      <div class="plan-card-stat">
        <div class="plan-card-stat-val"><?= (int)$p['duration'] ?><?= substr($p['duration_unit'] ?? 'days', 0, 1) ?></div>
        <div class="plan-card-stat-lbl">Duration</div>
      </div>
      <div class="plan-card-stat">
        <div class="plan-card-stat-val"><?= $p['win_rate'] ?? '87' ?>%</div>
        <div class="plan-card-stat-lbl">Win Rate</div>
      </div>
      <div class="plan-card-stat">
        <div class="plan-card-stat-val"><?= $p['accuracy'] ?? '92' ?>%</div>
        <div class="plan-card-stat-lbl">Accuracy</div>
      </div>
    </div>

    <!-- Invest trigger -->
    <button class="btn-ghost btn-full" style="margin-top: 16px;" onclick="scrollToInvest(<?= $p['id'] ?>)">
      Select Plan
    </button>
  </div>
  <?php endforeach; ?>
</div>

<!-- My investments table -->
<div class="card" style="margin-top:32px;">
  <div class="card-title">My Investments</div>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Date</th><th>Plan</th><th>Amount</th><th>Profit</th><th>Matures</th><th>Status</th></tr></thead>
      <tbody>
      <?php
        $iq = $conn->prepare("SELECT * FROM investments WHERE userId = ? ORDER BY id DESC");
        $iq->bind_param('i', $userId);
        $iq->execute();
        $irows = $iq->get_result();
        if ($irows->num_rows === 0): ?>
          <tr><td colspan="6" class="empty">No investments yet.</td></tr>
        <?php else:
          while ($ir = $irows->fetch_assoc()): ?>
          <tr>
            <td><?= date('d M Y', strtotime($ir['date'])) ?></td>
            <td><?= htmlspecialchars($ir['plan_name']) ?></td>
            <td>$<?= number_format((float)$ir['invest_amt'], 2) ?></td>
            <td>$<?= number_format((float)$ir['total_profit'], 2) ?></td>
            <td><?= date('d M Y', strtotime($ir['end_date'])) ?></td>
            <td><span class="badge badge-<?= $ir['status'] ?>"><?= ucfirst($ir['status']) ?></span></td>
          </tr>
        <?php endwhile; endif; $iq->close(); ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Inline Invest Form -->
<div class="card" id="investFormSection" style="margin-top: 32px; border-color: var(--primary); box-shadow: 0 10px 30px rgba(0,255,170,0.05);">
  <div class="card-title"><i class="fa fa-terminal" style="color: var(--primary); margin-right: 8px;"></i> New Investment</div>
  <form method="POST" action="investment.php">
    <div class="form-row">
      <div class="form-group">
        <label>Select AI Plan</label>
        <select name="plan" id="inlinePlanId" onchange="updateMinMax()" required style="background: rgba(0,0,0,0.2);">
          <option value="">-- Choose a Plan --</option>
          <?php foreach ($plans as $p): ?>
            <option value="<?= $p['id'] ?>" data-min="<?= (float)$p['min_amt'] ?>" data-max="<?= (float)$p['max_amt'] ?>">
              <?= htmlspecialchars($p['pname']) ?> (<?= strtoupper($p['bot_type']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Amount (USD) <span id="inlineRange" style="color:var(--text-muted);font-size:0.75rem;margin-left:6px;"></span></label>
        <div style="display:flex; align-items:center; background:var(--bg-input); border:1px solid var(--border); border-radius:50px; padding:0 16px;">
          <span style="color:var(--text-muted); font-size:1.2rem; font-weight:700; margin-right:8px;">$</span>
          <input type="number" name="amount" id="inlineAmount" step="0.01" required style="background:transparent; border:none; border-radius:0; padding:10px 0; outline:none; color:var(--text); width:100%; font-size:1.1rem; font-weight:700;">
        </div>
      </div>
    </div>
    <div style="margin-top: 24px; display: flex; align-items: center; justify-content: space-between;">
      <span style="font-size: 0.85rem; color: var(--text-muted);">Available Balance: <strong style="color:var(--text);">$<?= number_format($balance, 2) ?></strong></span>
      <button type="submit" class="btn-primary" style="padding: 12px 32px; font-size: 1rem;"><i class="fa fa-bolt"></i> Activate Bot</button>
    </div>
  </form>
</div>

<script>
function scrollToInvest(id) {
  const sel = document.getElementById('inlinePlanId');
  sel.value = id;
  updateMinMax();
  document.getElementById('investFormSection').scrollIntoView({ behavior: 'smooth', block: 'center' });
  
  // Highlight the form briefly
  const formCard = document.getElementById('investFormSection');
  formCard.style.transition = 'box-shadow 0.3s';
  formCard.style.boxShadow = '0 0 40px rgba(0,255,170,0.3)';
  setTimeout(() => { formCard.style.boxShadow = '0 10px 30px rgba(0,255,170,0.05)'; }, 1000);
}

function updateMinMax() {
  const sel = document.getElementById('inlinePlanId');
  if (!sel.value) {
    document.getElementById('inlineRange').textContent = '';
    return;
  }
  const opt = sel.options[sel.selectedIndex];
  const min = opt.getAttribute('data-min');
  const max = opt.getAttribute('data-max');
  document.getElementById('inlineRange').textContent = 'Limit: $' + Number(min).toLocaleString() + ' – $' + Number(max).toLocaleString();
  const inp = document.getElementById('inlineAmount');
  inp.min = min; inp.max = max; 
  inp.placeholder = 'Min: ' + min;
  inp.focus();
}
</script>

<?php include 'footer.php'; ?>
