<?php
require_once __DIR__ . '/session.php';

// Pending deposits count
$pend_dep = 0;
$pd = $conn->prepare("SELECT COUNT(*) FROM deposits WHERE userId=? AND status='pending'");
$pd->bind_param('i', $userId);
$pd->execute();
$pd->bind_result($pend_dep);
$pd->fetch();
$pd->close();

// Pending withdrawals count
$pend_with = 0;
$pw = $conn->prepare("SELECT COUNT(*) FROM withdrawals WHERE userId=? AND status='pending'");
$pw->bind_param('i', $userId);
$pw->execute();
$pw->bind_result($pend_with);
$pw->fetch();
$pw->close();

// Active investment (latest running)
$activeInv = null;
$ai = $conn->prepare(
    "SELECT i.*, p.pname, bs.bot_name, bs.bot_type, bs.win_rate, bs.accuracy, bs.uptime_pct, bs.trades_per_day
     FROM investments i
     JOIN plans p ON p.id = i.planId
     LEFT JOIN bot_settings bs ON bs.plan_id = i.planId
     WHERE i.userId = ? AND i.status = 'running'
     ORDER BY i.date DESC LIMIT 1"
);
$ai->bind_param('i', $userId);
$ai->execute();
$activeInv = $ai->get_result()->fetch_assoc();
$ai->close();

// Bot progress percent
$botProgress = 0;
if ($activeInv) {
    $start = strtotime($activeInv['start_date']);
    $end   = strtotime($activeInv['end_date']);
    $now   = time();
    if ($end > $start) {
        $botProgress = min(100, max(0, round((($now - $start) / ($end - $start)) * 100)));
    }
}

// Recent deposits (last 5)
$deps = [];
$dq = $conn->prepare("SELECT amount, status, date FROM deposits WHERE userId=? ORDER BY id DESC LIMIT 5");
$dq->bind_param('i', $userId);
$dq->execute();
$deps = $dq->get_result()->fetch_all(MYSQLI_ASSOC);
$dq->close();

// Recent withdrawals (last 5)
$withs = [];
$wq = $conn->prepare("SELECT amount, status, date FROM withdrawals WHERE userId=? ORDER BY id DESC LIMIT 5");
$wq->bind_param('i', $userId);
$wq->execute();
$withs = $wq->get_result()->fetch_all(MYSQLI_ASSOC);
$wq->close();
?>
<?php include 'header.php'; ?>

<div class="page-title">Dashboard</div>

<!-- ── Stat cards ───────────────────────────────────────────────── -->
<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-icon"><i class="fa fa-wallet"></i></div>
    <div class="stat-body">
      <div class="stat-label">Account Balance</div>
      <div class="stat-value">$<?= number_format((float)$balance, 2) ?></div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon accent-green"><i class="fa fa-chart-line"></i></div>
    <div class="stat-body">
      <div class="stat-label">Total Profit</div>
      <div class="stat-value">$<?= number_format((float)$profit, 2) ?></div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon accent-purple"><i class="fa fa-users"></i></div>
    <div class="stat-body">
      <div class="stat-label">Referral Bonus</div>
      <div class="stat-value">$<?= number_format((float)$ref_balance, 2) ?></div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon accent-blue"><i class="fa fa-arrow-down"></i></div>
    <div class="stat-body">
      <div class="stat-label">Total Deposited</div>
      <div class="stat-value">$<?= number_format((float)$total_deposit, 2) ?></div>
    </div>
  </div>
</div>

<!-- ── Email verification banner ────────────────────────────── -->
<?php if (!$userData['email_verified']): ?>
<div class="alert-banner">
  <i class="fa fa-envelope"></i>
  Your email address is not verified. <strong>Please verify your email</strong> to secure your account.
  <a href="verify_email.php" class="alert-banner-link">Verify Now →</a>
</div>
<?php endif; ?>

<!-- ── KYC banner (if not verified) ────────────────────────────── -->
<?php if ($status === 'Not Verified'): ?>
<div class="alert-banner">
  <i class="fa fa-triangle-exclamation"></i>
  Your account is not KYC verified. <strong>Withdrawals are locked</strong> until your identity is approved.
  <a href="profile.php?tab=kyc" class="alert-banner-link">Verify Now →</a>
</div>
<?php elseif ($status === 'submitted'): ?>
<div class="alert-banner info">
  <i class="fa fa-clock"></i> Your KYC is under review. Withdrawals will be unlocked once approved.
</div>
<?php endif; ?>

<!-- ── Full Width Chart ─────────────────────────────────────────── -->
<div class="widget-card" style="padding: 0; overflow: hidden; margin-bottom: 24px;">
  <div class="widget-title" style="padding: 20px 24px 0;"><i class="fa fa-chart-candlestick"></i> Market Terminal</div>
  <div class="tradingview-widget-container" style="height:420px; width: 100%;">
    <div id="tradingview_chart" style="height:100%; width: 100%;"></div>
    <script type="text/javascript" src="https://s3.tradingview.com/tv.js"></script>
    <script>
      new TradingView.widget({
        autosize: true,
        symbol: "BINANCE:BTCUSDT",
        interval: "15",
        timezone: "Etc/UTC",
        theme: "dark",
        style: "1",
        locale: "en",
        toolbar_bg: "#111816",
        enable_publishing: false,
        allow_symbol_change: true,
        container_id: "tradingview_chart",
        hide_top_toolbar: false,
        hide_side_toolbar: true
      });
    </script>
  </div>
</div>

<!-- ── Main 2-column layout ─────────────────────────────────────── -->
<div class="dash-grid">

  <!-- LEFT column -->
  <div class="dash-col">

    <!-- AI Bot Card -->
    <div class="widget-card bot-card">
      <div class="widget-title"><i class="fa fa-robot"></i> AI Trading Bot</div>

      <?php if ($activeInv): ?>
        <div class="bot-header">
          <div class="bot-name"><?= htmlspecialchars($activeInv['bot_name'] ?? 'AI Bot') ?></div>
          <div class="bot-running-badge"><span class="pulse-dot"></span> RUNNING</div>
        </div>

        <div class="bot-plan"><?= htmlspecialchars($activeInv['pname']) ?>
          <span class="plan-badge"><?= strtoupper($activeInv['bot_type'] ?? 'AI') ?></span>
        </div>

        <div class="progress-wrap">
          <div class="progress-label">
            <span>Progress</span>
            <span><?= $botProgress ?>%</span>
          </div>
          <div class="progress-bar">
            <div class="progress-fill" style="width:<?= $botProgress ?>%"></div>
          </div>
          <div class="progress-dates">
            <span><?= date('d M Y', strtotime($activeInv['start_date'])) ?></span>
            <span><?= date('d M Y', strtotime($activeInv['end_date'])) ?></span>
          </div>
        </div>

        <div class="bot-stats-row">
          <div class="bot-stat">
            <div class="bot-stat-value"><?= number_format((float)($activeInv['win_rate'] ?? 87.5), 1) ?>%</div>
            <div class="bot-stat-label">Win Rate</div>
          </div>
          <div class="bot-stat">
            <div class="bot-stat-value"><?= number_format((float)($activeInv['accuracy'] ?? 92.3), 1) ?>%</div>
            <div class="bot-stat-label">Accuracy</div>
          </div>
          <div class="bot-stat">
            <div class="bot-stat-value"><?= number_format((float)($activeInv['uptime_pct'] ?? 99.7), 1) ?>%</div>
            <div class="bot-stat-label">Uptime</div>
          </div>
          <div class="bot-stat">
            <div class="bot-stat-value"><?= (int)($activeInv['trades_per_day'] ?? 45) ?></div>
            <div class="bot-stat-label">Trades/Day</div>
          </div>
        </div>

        <div class="bot-inv-detail">
          <span>Invested: <strong>$<?= number_format((float)$activeInv['invest_amt'], 2) ?></strong></span>
          <span>Expected ROI: <strong><?= $activeInv['increase'] ?>%</strong></span>
          <span>Profit: <strong style="color:var(--primary)">$<?= number_format((float)$activeInv['total_profit'], 2) ?></strong></span>
        </div>

        <?php if ((float)$profit > 0): ?>
          <button class="btn-primary btn-full" onclick="document.getElementById('trfModal').style.display='flex'">
            <i class="fa fa-arrow-right"></i> Transfer Profit to Balance
          </button>
        <?php endif; ?>

      <?php else: ?>
        <div class="bot-idle">
          <div class="bot-idle-icon"><i class="fa fa-robot"></i></div>
          <div class="bot-idle-text">No active AI bot</div>
          <a href="investment.php" class="btn-primary btn-full" style="margin-top:16px;">Activate AI Bot →</a>
        </div>
      <?php endif; ?>
    </div>

    <!-- AI Trade Feed -->
    <div class="widget-card">
      <div class="widget-title">
        <i class="fa fa-bolt"></i> Live AI Trade Feed
        <span class="feed-status" id="feedStatus">Loading…</span>
      </div>
      <div id="tradeFeed" class="trade-feed">
        <div class="feed-placeholder">Connecting to AI feed…</div>
      </div>
    </div>

  </div><!-- /LEFT -->

  <!-- RIGHT column -->
  <div class="dash-col">

    <!-- Pending transactions -->
    <div class="widget-card">
      <div class="widget-title"><i class="fa fa-clock"></i> Recent Transactions</div>

      <!-- Tab bar -->
      <div class="mini-tabs">
        <button class="mini-tab active" onclick="switchTab(this,'dep-tab')">Deposits <?php if ($pend_dep): ?><span class="mini-badge"><?= $pend_dep ?></span><?php endif; ?></button>
        <button class="mini-tab" onclick="switchTab(this,'with-tab')">Withdrawals <?php if ($pend_with): ?><span class="mini-badge"><?= $pend_with ?></span><?php endif; ?></button>
      </div>

      <div id="dep-tab" class="mini-tab-panel">
        <?php if (empty($deps)): ?>
          <p class="empty-msg">No deposit records yet.</p>
        <?php else: foreach ($deps as $d): ?>
          <div class="txn-row">
            <div class="txn-left">
              <span class="txn-icon dep"><i class="fa fa-arrow-down"></i></span>
              <div>
                <div class="txn-amount">+$<?= number_format((float)$d['amount'], 2) ?></div>
                <div class="txn-date"><?= date('d M Y, H:i', strtotime($d['date'])) ?></div>
              </div>
            </div>
            <span class="badge badge-<?= $d['status'] ?>"><?= ucfirst($d['status']) ?></span>
          </div>
        <?php endforeach; endif; ?>
        <a href="history.php?tab=deposits" class="view-all">View all →</a>
      </div>

      <div id="with-tab" class="mini-tab-panel" style="display:none">
        <?php if (empty($withs)): ?>
          <p class="empty-msg">No withdrawal records yet.</p>
        <?php else: foreach ($withs as $w): ?>
          <div class="txn-row">
            <div class="txn-left">
              <span class="txn-icon with"><i class="fa fa-arrow-up"></i></span>
              <div>
                <div class="txn-amount">-$<?= number_format((float)$w['amount'], 2) ?></div>
                <div class="txn-date"><?= date('d M Y, H:i', strtotime($w['date'])) ?></div>
              </div>
            </div>
            <span class="badge badge-<?= $w['status'] ?>"><?= ucfirst($w['status']) ?></span>
          </div>
        <?php endforeach; endif; ?>
        <a href="history.php?tab=withdrawals" class="view-all">View all →</a>
      </div>
    </div>

    <!-- Quick actions -->
    <div class="quick-actions">
      <a href="deposit.php"    class="qa-btn"><i class="fa fa-arrow-down"></i> Deposit</a>
      <a href="withdrawal.php" class="qa-btn"><i class="fa fa-arrow-up"></i> Withdraw</a>
      <a href="investment.php" class="qa-btn"><i class="fa fa-robot"></i> Invest</a>
      <a href="referral.php"   class="qa-btn"><i class="fa fa-users"></i> Refer</a>
    </div>

  </div><!-- /RIGHT -->
</div><!-- /dash-grid -->

<!-- ── Transfer Profit Modal ────────────────────────────────────── -->
<div id="trfModal" class="modal-overlay" style="display:none">
  <div class="modal-box">
    <div class="modal-title">Transfer Profit to Balance</div>
    <p style="font-size:0.88rem;color:var(--text-muted);margin-bottom:16px;">
      Available profit: <strong style="color:var(--primary)">$<?= number_format((float)$profit, 2) ?></strong>
    </p>
    <div id="trfMsg"></div>
    <input type="number" id="trfAmount" class="form-input" placeholder="Amount to transfer" step="0.01" min="1" max="<?= $profit ?>">
    <div class="modal-actions">
      <button class="btn-ghost" onclick="document.getElementById('trfModal').style.display='none'">Cancel</button>
      <button class="btn-primary" onclick="doTransfer()">Transfer</button>
    </div>
  </div>
</div>

<!-- ── JS ───────────────────────────────────────────────────────── -->
<script>
// Tab switcher
function switchTab(btn, panelId) {
  document.querySelectorAll('.mini-tab').forEach(b => b.classList.remove('active'));
  document.querySelectorAll('.mini-tab-panel').forEach(p => p.style.display = 'none');
  btn.classList.add('active');
  document.getElementById(panelId).style.display = 'block';
}

// AI Trade Feed
function loadFeed() {
  fetch('../api/ai_feed.php')
    .then(r => r.json())
    .then(data => {
      const el = document.getElementById('tradeFeed');
      const st = document.getElementById('feedStatus');
      if (!data.length) { 
        el.innerHTML = '<div class="feed-placeholder">No trades yet.</div>'; 
        st.textContent = 'Idle';
        st.style.color = 'var(--text-muted)';
        return; 
      }
      st.textContent = 'Live';
      st.style.color = '#10b981';
      el.innerHTML = data.slice(0, 10).map(t => {
        const sign  = t.action === 'BUY' ? '+' : '-';
        const cls   = t.action === 'BUY' ? 'feed-buy' : 'feed-sell';
        const pct   = t.profit_pct ? `${sign}${t.profit_pct}%` : '—';
        return `<div class="feed-row ${cls}">
          <span class="feed-action">${t.action}</span>
          <span class="feed-pair">${t.pair}</span>
          <span class="feed-entry">@ $${Number(t.entry_price).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})}</span>
          <span class="feed-pct">${pct}</span>
          <span class="feed-status-dot ${t.status === 'closed' ? 'closed' : 'open'}">${t.status.toUpperCase()}</span>
        </div>`;
      }).join('');
    })
    .catch(() => { document.getElementById('feedStatus').textContent = 'Offline'; });
}
loadFeed();
setInterval(loadFeed, 30000);

// Transfer Profit
function doTransfer() {
  const amt = parseFloat(document.getElementById('trfAmount').value);
  const msgEl = document.getElementById('trfMsg');
  if (!amt || amt <= 0) { msgEl.innerHTML = '<div class="alert-err">Enter a valid amount.</div>'; return; }

  fetch('trf_profit.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'amount=' + amt
  })
  .then(r => r.json())
  .then(d => {
    if (d.success) {
      msgEl.innerHTML = '<div class="alert-ok">Transferred! Reloading…</div>';
      setTimeout(() => location.reload(), 1200);
    } else {
      msgEl.innerHTML = '<div class="alert-err">' + (d.message || 'Transfer failed.') + '</div>';
    }
  })
  .catch(() => { msgEl.innerHTML = '<div class="alert-err">Network error.</div>'; });
}
</script>

<?php include 'footer.php'; ?>
