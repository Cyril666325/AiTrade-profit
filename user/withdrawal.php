<?php
require_once __DIR__ . '/session.php';

$error = $msg = '';

// ── KYC GATE (server-side, enforced before form or processing) ─────────────
if ($status !== 'Verified') {
    $kycBlocked = true;
} else {
    $kycBlocked = false;
}

if (!$kycBlocked && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Re-check KYC status from DB (not just session) to prevent tampering
    $chkKyc = $conn->prepare("SELECT status FROM users WHERE id = ? LIMIT 1");
    $chkKyc->bind_param('i', $userId);
    $chkKyc->execute();
    $chkKyc->bind_result($dbStatus);
    $chkKyc->fetch();
    $chkKyc->close();

    if ($dbStatus !== 'Verified') {
        $error = 'KYC verification required before withdrawing.';
    } else {
        $amount        = floatval($_POST['amount']      ?? 0);
        $walletAddr    = text_input($_POST['address']   ?? '');
        $method        = text_input($_POST['method']    ?? '');
        $balanceType   = ($_POST['balance_type'] ?? 'balance') === 'refbonus' ? 'refbonus' : 'balance';

        $availableBalance = ($balanceType === 'refbonus') ? $ref_balance : $balance;

        if ($amount <= 0) {
            $error = 'Enter a valid amount.';
        } elseif (empty($walletAddr)) {
            $error = 'Wallet address is required.';
        } elseif (empty($method)) {
            $error = 'Payment method is required.';
        } elseif ($amount > $availableBalance) {
            $error = 'Insufficient ' . ($balanceType === 'refbonus' ? 'referral bonus' : 'account balance') . '.';
        } else {
            // Deduct balance first
            if ($balanceType === 'refbonus') {
                $upd = $conn->prepare("UPDATE users SET ref_balance = ref_balance - ? WHERE id = ?");
            } else {
                $upd = $conn->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
            }
            $upd->bind_param('di', $amount, $userId);
            $upd->execute();
            $upd->close();

            $tranxId = generateTranxId('WD');
            $ins = $conn->prepare(
                "INSERT INTO withdrawals (userId, email, type, method, amount, wallet_address, tranxId, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')"
            );
            $ins->bind_param('isssdss', $userId, $email, $balanceType, $method, $amount, $walletAddr, $tranxId);
            if ($ins->execute()) {
                $body = "<p>Hi <strong>$fname</strong>,</p>
                         <p>Your withdrawal request has been received.</p>
                         <ul>
                           <li><strong>Amount:</strong> \$$amount</li>
                           <li><strong>Method:</strong> $method</li>
                           <li><strong>Transaction ID:</strong> $tranxId</li>
                         </ul>
                         <p>We will process your request shortly.</p>";
                sendMail($email, "Withdrawal Request — $sitename", $body);
                $msg = "Withdrawal request submitted. Transaction ID: $tranxId";

                // Refresh balances
                if ($balanceType === 'refbonus') $ref_balance -= $amount;
                else $balance -= $amount;
            } else {
                // Rollback deduction
                if ($balanceType === 'refbonus') {
                    $rb = $conn->prepare("UPDATE users SET ref_balance = ref_balance + ? WHERE id = ?");
                } else {
                    $rb = $conn->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                }
                $rb->bind_param('di', $amount, $userId);
                $rb->execute();
                $rb->close();
                $error = 'Failed to submit withdrawal. Please try again.';
            }
            $ins->close();
        }
    }
}
?>
<?php include 'header.php'; ?>

<div class="page-header">
  <h2>Withdraw Funds</h2>
  <nav class="breadcrumb"><a href="index.php">Dashboard</a> / Withdrawal</nav>
</div>

<?php if ($kycBlocked): ?>
<!-- KYC gate UI -->
<div class="kyc-gate-card">
  <div class="kyc-gate-icon">🔒</div>
  <div class="kyc-gate-title">KYC Verification Required</div>
  <div class="kyc-gate-body">
    <p>You must complete identity verification before you can withdraw funds.</p>
    <p>Current status: <strong class="badge badge-<?= strtolower(str_replace(' ', '-', $status)) ?>"><?= htmlspecialchars($status) ?></strong></p>
    <?php if ($status === 'Not Verified'): ?>
      <p>Please upload your ID document to begin the process.</p>
      <a href="profile.php?tab=kyc" class="btn-primary">Complete KYC →</a>
    <?php elseif ($status === 'submitted'): ?>
      <p>Your documents are under review. Withdrawals will be enabled once approved.</p>
    <?php endif; ?>
  </div>
</div>

<?php else: ?>
<?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($msg):   ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<!-- Balance cards -->
<div class="wd-balances">

  <!-- Account Balance -->
  <div class="wd-balance-card wd-balance-primary active" id="card-balance" onclick="selectBalanceType('balance')" style="cursor: pointer;">
    <div class="wd-balance-label">Account Balance</div>
    <div class="wd-balance-amount">$<?= number_format($balance, 2) ?></div>
    <div style="font-size: 0.8rem; color: var(--primary); font-weight: 700; text-transform: uppercase;">Selected</div>
  </div>

  <!-- Referral Bonus -->
  <div class="wd-balance-card wd-balance-ref" id="card-refbonus" onclick="selectBalanceType('refbonus')" style="cursor: pointer; opacity: 0.6;">
    <div class="wd-balance-label">Referral Bonus</div>
    <div class="wd-balance-amount">$<?= number_format($ref_balance, 2) ?></div>
    <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Click to Select</div>
  </div>

</div>

<!-- INLINE WITHDRAWAL FORM -->
<?php
$images = [
    'Bitcoin (BTC)'  => 'btc.png',
    'Ethereum (ETH)' => 'ethereum.png',
    'USDT (TRC20)'   => 'tether.png',
    'USDT (ERC20)'   => 'ethusdt.png',
    'BNB (BEP20)'    => 'bep.png',
    'Solana (SOL)'   => 'solana.jpeg'
];
?>
<div class="card" style="max-width: 800px; margin: 0 auto 32px; background: transparent; border: none; box-shadow: none; padding: 0;">
  <form method="POST" action="withdrawal.php" id="withdrawForm">
    <input type="hidden" name="balance_type" id="wdBalanceType" value="balance">
    <input type="hidden" name="method" id="selectedWdMethod" required>
    
    <!-- Asset Selector -->
    <div class="asset-selector-wrap" style="margin-bottom: 32px;">
      <label class="asset-selector-label" style="display:flex; align-items:center;"><i class="fa fa-cubes" style="color: var(--primary); font-size: 1.1rem; margin-right: 8px;"></i> 1. Select Network / Asset</label>
      <div class="asset-grid">
        <?php foreach ($payMethods as $label => $info): 
          $imgFile = $images[$label] ?? 'btc.png';
          $icon = "<img src=\"../assets/img/crypto/{$imgFile}\" alt=\"{$label}\" style=\"width:32px; height:32px; border-radius:50%; object-fit:cover;\">";
        ?>
          <div class="asset-btn" data-method="<?= htmlspecialchars($label) ?>" onclick="selectWdAsset(this, '<?= htmlspecialchars($label) ?>')">
            <?= $icon ?>
            <div class="asset-btn-name"><?= htmlspecialchars($label) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Address Input -->
    <div class="form-group" style="margin-bottom: 32px;">
      <label class="asset-selector-label" style="margin-bottom: 12px; display:flex; align-items:center;"><i class="fa fa-wallet" style="color: var(--primary); font-size: 1.1rem; margin-right: 8px;"></i> 2. Receiving Wallet Address</label>
      <input type="text" name="address" required placeholder="Paste your wallet address here" style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-light); padding: 18px 24px; font-size: 1.1rem; border-radius: 16px; width: 100%; color: var(--text); outline: none; transition: border-color 0.3s;" onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='var(--border-light)'">
    </div>

    <!-- Massive Amount Input -->
    <div class="massive-amount-wrap" style="margin-bottom: 40px;">
      <label class="massive-amount-label">3. Enter Amount</label>
      <div class="massive-amount-inner">
        <span class="massive-amount-prefix">$</span>
        <input type="number" name="amount" id="wdAmount" step="0.01" min="1" class="massive-amount-input" placeholder="0.00" required>
      </div>
    </div>

    <button type="submit" class="btn-massive">Execute Withdrawal <i class="fa fa-arrow-right"></i></button>
  </form>
</div>

<!-- Withdrawal history -->
<div class="card" style="margin-top:24px;">
  <div class="card-title">Withdrawal History</div>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Date</th><th>Trans. ID</th><th>Type</th><th>Method</th><th>Amount</th><th>Status</th></tr></thead>
      <tbody>
      <?php
        $wq = $conn->prepare("SELECT * FROM withdrawals WHERE userId = ? ORDER BY date DESC LIMIT 20");
        $wq->bind_param('i', $userId);
        $wq->execute();
        $wrows = $wq->get_result();
        if ($wrows->num_rows === 0): ?>
          <tr><td colspan="6" class="empty">No withdrawals yet.</td></tr>
        <?php else:
          while ($wr = $wrows->fetch_assoc()): ?>
          <tr>
            <td><?= date('d M Y', strtotime($wr['date'])) ?></td>
            <td class="mono"><?= htmlspecialchars($wr['tranxId']) ?></td>
            <td><?= $wr['type'] === 'refbonus' ? 'Referral Bonus' : 'Account Balance' ?></td>
            <td><?= htmlspecialchars($wr['method']) ?></td>
            <td>$<?= number_format((float)$wr['amount'], 2) ?></td>
            <td><span class="badge badge-<?= $wr['status'] ?>"><?= ucfirst($wr['status']) ?></span></td>
          </tr>
        <?php endwhile; endif; $wq->close(); ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<script>
function selectBalanceType(type) {
  document.getElementById('wdBalanceType').value = type;
  
  const cardBal = document.getElementById('card-balance');
  const cardRef = document.getElementById('card-refbonus');
  
  if (type === 'balance') {
    cardBal.style.opacity = '1';
    cardBal.style.borderColor = 'var(--primary)';
    cardBal.querySelector('div:last-child').innerHTML = 'Selected';
    cardBal.querySelector('div:last-child').style.color = 'var(--primary)';
    
    cardRef.style.opacity = '0.6';
    cardRef.style.borderColor = 'transparent';
    cardRef.querySelector('div:last-child').innerHTML = 'Click to Select';
    cardRef.querySelector('div:last-child').style.color = 'var(--text-muted)';
  } else {
    cardRef.style.opacity = '1';
    cardRef.style.borderColor = 'var(--primary)';
    cardRef.querySelector('div:last-child').innerHTML = 'Selected';
    cardRef.querySelector('div:last-child').style.color = 'var(--primary)';
    
    cardBal.style.opacity = '0.6';
    cardBal.style.borderColor = 'transparent';
    cardBal.querySelector('div:last-child').innerHTML = 'Click to Select';
    cardBal.querySelector('div:last-child').style.color = 'var(--text-muted)';
  }
}

function selectWdAsset(btn, method) {
  document.querySelectorAll('.asset-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('selectedWdMethod').value = method;
}
document.getElementById('withdrawForm').addEventListener('submit', function(e) {
  if (!document.getElementById('selectedWdMethod').value) {
      e.preventDefault();
      alert('Please select a network/asset first.');
  }
});
</script>

<?php include 'footer.php'; ?>
