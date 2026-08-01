<?php
require_once __DIR__ . '/session.php';

$error = $msg = '';
$step = 1;          // 1 = choose amount+method, 2 = show wallet address
$selectedMethod = null;
$walletAddress  = null;
$cryptoAmount   = null;
$tranxId        = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Step 1 → Step 2: show payment details
    if (isset($_POST['step']) && $_POST['step'] === '1') {
        $amount     = floatval($_POST['amount'] ?? 0);
        $methodName = text_input($_POST['method'] ?? '');

        if ($amount < 50) {
            $error = 'Minimum deposit is $50.00.';
        } elseif (empty($methodName) || !isset($payMethods[$methodName])) {
            $error = 'Invalid payment method.';
        } else {
            $selectedMethod = $methodName;
            $walletAddress  = $payMethods[$methodName]['address'];
            $code           = $payMethods[$methodName]['code'];
            $price          = getCryptoPrice($code);
            $cryptoAmount   = ($price > 0) ? round($amount / $price, 6) : null;
            $tranxId        = generateTranxId('DEP');
            $step = 2;

            // Store in session so step-3 POST can read them
            $_SESSION['dep_pending'] = [
                'amount'    => $amount,
                'method'    => $methodName,
                'tranxId'   => $tranxId,
            ];
        }
    }

    // Step 2 → Step 3: user clicked "I've Paid"
    elseif (isset($_POST['step']) && $_POST['step'] === '2') {
        $pending = $_SESSION['dep_pending'] ?? null;
        if (!$pending) {
            $error = 'Session expired. Please start again.';
        } else {
            $amount  = floatval($pending['amount']);
            $method  = $pending['method'];
            $tranxId = $pending['tranxId'];

            // Handle optional proof upload
            $proof = null;
            if (!empty($_FILES['proof']['name'])) {
                $ext     = strtolower(pathinfo($_FILES['proof']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg','jpeg','png','pdf'];
                if (!in_array($ext, $allowed)) {
                    $error = 'Proof file must be JPG, PNG, or PDF.';
                } elseif ($_FILES['proof']['size'] > 5 * 1024 * 1024) {
                    $error = 'Proof file must be under 5MB.';
                } else {
                    $proof = 'proof_' . $userId . '_' . time() . '.' . $ext;
                    move_uploaded_file($_FILES['proof']['tmp_name'], __DIR__ . '/../uploads/' . $proof);
                }
            }

            if (empty($error)) {
                $stmt = $conn->prepare(
                    "INSERT INTO deposits (userId, method, tranxId, amount, proof, status) VALUES (?, ?, ?, ?, ?, 'pending')"
                );
                $stmt->bind_param('issds', $userId, $method, $tranxId, $amount, $proof);
                if ($stmt->execute()) {
                    unset($_SESSION['dep_pending']);
                    $msg = "Deposit submitted! Transaction ID: $tranxId. Your balance will be credited after confirmation.";
                    $step = 1;

                    $body = "<p>Hi <strong>$fname</strong>,</p>
                             <p>Your deposit request of <strong>\$$amount</strong> via <strong>$method</strong> has been received.</p>
                             <p><strong>Transaction ID:</strong> $tranxId</p>
                             <p>It will be credited after admin confirmation.</p>";
                    sendMail($email, "Deposit Received — $sitename", $body);
                } else {
                    $error = 'Failed to record deposit. Please try again.';
                }
                $stmt->close();
            }
        }
    }
}

// Restore step-2 state if session still has pending data
if ($step === 1 && isset($_SESSION['dep_pending']) && empty($error) && empty($msg)) {
    // Don't auto-restore — just let user start fresh
}
?>
<?php include 'header.php'; ?>

<div class="page-header">
  <h2>Deposit Funds</h2>
  <nav class="breadcrumb"><a href="index.php">Dashboard</a> / Deposit</nav>
</div>

<?php if ($msg): ?>
  <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>
<?php if ($error): ?>
  <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($step === 1): 
$images = [
    'Bitcoin (BTC)'  => 'btc.png',
    'Ethereum (ETH)' => 'ethereum.png',
    'USDT (TRC20)'   => 'tether.png',
    'USDT (ERC20)'   => 'ethusdt.png',
    'BNB (BEP20)'    => 'bep.png',
    'Solana (SOL)'   => 'solana.jpeg'
];
?>
<!-- STEP 1: Choose amount and method -->
<div class="card" style="max-width: 800px; margin: 0 auto; background: transparent; border: none; box-shadow: none; padding: 0;">
  <form method="POST" action="deposit.php" id="depositForm">
    <input type="hidden" name="step" value="1">
    <input type="hidden" name="method" id="selectedMethod" required>
    
    <!-- Asset Selector -->
    <div class="asset-selector-wrap">
      <label class="asset-selector-label" style="display:flex; align-items:center;"><i class="fa fa-cubes" style="color: var(--primary); font-size: 1.1rem; margin-right: 8px;"></i> 1. Select Network / Asset</label>
      <div class="asset-grid">
        <?php foreach ($payMethods as $label => $info): 
          $imgFile = $images[$label] ?? 'btc.png';
          $icon = "<img src=\"../assets/img/crypto/{$imgFile}\" alt=\"{$label}\" style=\"width:32px; height:32px; border-radius:50%; object-fit:cover;\">";
        ?>
          <div class="asset-btn" data-method="<?= htmlspecialchars($label) ?>" onclick="selectAsset(this, '<?= htmlspecialchars($label) ?>')">
            <?= $icon ?>
            <div class="asset-btn-name"><?= htmlspecialchars($label) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Amount Input -->
    <div class="massive-amount-wrap">
      <label class="massive-amount-label">2. Enter Amount (USD)</label>
      <div class="massive-amount-inner">
        <span class="massive-amount-prefix">$</span>
        <input type="number" name="amount" min="50" step="0.01" class="massive-amount-input" placeholder="0.00" required>
      </div>
      <div style="font-size:0.85rem;color:var(--text-muted);margin-top:12px;">Minimum deposit: <strong style="color:var(--text)">$50.00</strong></div>
    </div>

    <button type="submit" class="btn-massive">Execute Deposit <i class="fa fa-arrow-right"></i></button>
  </form>
</div>

<script>
function selectAsset(btn, method) {
    document.querySelectorAll('.asset-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('selectedMethod').value = method;
}
document.getElementById('depositForm').addEventListener('submit', function(e) {
    if (!document.getElementById('selectedMethod').value) {
        e.preventDefault();
        alert('Please select a network/asset first.');
    }
});
</script>

<?php elseif ($step === 2): ?>
<!-- STEP 2: Show wallet address (loaded from DB server-side) -->
<div class="card card-sm">
  <div class="card-title">Send Payment</div>
  <p class="dep-label">Amount: <strong>$<?= number_format($_SESSION['dep_pending']['amount'], 2) ?></strong></p>
  <?php if ($cryptoAmount): ?>
    <p class="dep-label">≈ <strong><?= $cryptoAmount ?> <?= htmlspecialchars($payMethods[$selectedMethod]['code']) ?></strong></p>
  <?php endif; ?>
  <p class="dep-label">Method: <strong><?= htmlspecialchars($selectedMethod) ?></strong></p>

  <div class="wallet-box">
    <span class="wallet-addr" id="walletAddr"><?= htmlspecialchars($walletAddress) ?></span>
    <button type="button" class="btn-copy" onclick="copyWallet()">Copy</button>
  </div>
  <div class="qr-wrap">
    <img src="https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=<?= urlencode($walletAddress) ?>"
         alt="QR Code" class="qr-img">
    <p class="qr-hint">Scan QR or copy address above</p>
  </div>

  <form method="POST" action="deposit.php" enctype="multipart/form-data">
    <input type="hidden" name="step" value="2">
    <div class="form-group">
      <label>Upload Payment Proof <span style="color:var(--text-muted);font-weight:400">(optional — JPG/PNG/PDF, max 5MB)</span></label>
      <input type="file" name="proof" accept=".jpg,.jpeg,.png,.pdf">
    </div>
    <button type="submit" class="btn-primary">I've Paid — Submit</button>
  </form>
  <button type="button" class="btn-ghost" onclick="history.back()">← Change method</button>
</div>
<?php endif; ?>

<!-- Deposit history -->
<div class="card" style="margin-top:24px;">
  <div class="card-title">Deposit History</div>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr><th>Date</th><th>Trans. ID</th><th>Method</th><th>Amount</th><th>Status</th></tr>
      </thead>
      <tbody>
      <?php
        $dq = $conn->prepare("SELECT tranxId, method, amount, status, date FROM deposits WHERE userId = ? ORDER BY date DESC LIMIT 20");
        $dq->bind_param('i', $userId);
        $dq->execute();
        $drows = $dq->get_result();
        if ($drows->num_rows === 0): ?>
          <tr><td colspan="5" class="empty">No deposits yet.</td></tr>
        <?php else:
          while ($dr = $drows->fetch_assoc()): ?>
          <tr>
            <td><?= date('d M Y', strtotime($dr['date'])) ?></td>
            <td class="mono"><?= htmlspecialchars($dr['tranxId']) ?></td>
            <td><?= htmlspecialchars($dr['method']) ?></td>
            <td>$<?= number_format((float)$dr['amount'], 2) ?></td>
            <td><span class="badge badge-<?= $dr['status'] ?>"><?= ucfirst($dr['status']) ?></span></td>
          </tr>
        <?php endwhile; endif; $dq->close(); ?>
      </tbody>
    </table>
  </div>
</div>

<script>
function copyWallet() {
  const addr = document.getElementById('walletAddr').innerText;
  navigator.clipboard.writeText(addr).then(() => {
    const btn = document.querySelector('.btn-copy');
    btn.textContent = 'Copied!';
    setTimeout(() => btn.textContent = 'Copy', 2000);
  });
}
</script>

<?php include 'footer.php'; ?>
