<?php
require_once __DIR__ . '/session.php';

$msg = $error = '';

if ($is_demo && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = 'This action is disabled in the demo account.';
}

if (!$is_demo && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $cur  = $_POST['current_password'] ?? '';
    $new  = $_POST['new_password']     ?? '';
    $conf = $_POST['confirm_password'] ?? '';

    $aq = $conn->prepare("SELECT password FROM admin WHERE id = ? LIMIT 1");
    $aq->bind_param('i', $adminId);
    $aq->execute();
    $aq->bind_result($curHash);
    $aq->fetch();
    $aq->close();

    if (!verifyPassword($cur, $curHash)) {
        $error = 'Current password is incorrect.';
    } elseif (strlen($new) < 8) {
        $error = 'New password must be at least 8 characters.';
    } elseif ($new !== $conf) {
        $error = 'New passwords do not match.';
    } else {
        $upd = $conn->prepare("UPDATE admin SET password = ? WHERE id = ?");
        $hash = hashPassword($new);
        $upd->bind_param('si', $hash, $adminId);
        $upd->execute() ? $msg = 'Password updated successfully.' : $error = 'Failed to update password.';
        $upd->close();
    }
}

if (!$is_demo && $_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['change_password'])) {
    $sitename_new  = text_input($_POST['sitename']  ?? '');
    $siteurl_new   = text_input($_POST['siteurl']   ?? '');
    $sitemail_new  = text_input($_POST['sitemail']  ?? '');
    $ref_bonus_new = floatval($_POST['ref_bonus']   ?? 10);
    $whatsapp_new  = text_input($_POST['whatsapp']  ?? '');

    // Wallet addresses
    $btc    = text_input($_POST['btc_wallet']  ?? '');
    $eth    = text_input($_POST['eth_wallet']  ?? '');
    $trc20  = text_input($_POST['usdt_trc20']  ?? '');
    $erc20  = text_input($_POST['usdt_erc20']  ?? '');
    $ltc    = text_input($_POST['ltc_wallet']  ?? '');
    $bnb    = text_input($_POST['bnb_wallet']  ?? '');
    $doge   = text_input($_POST['doge_wallet'] ?? '');
    $sol    = text_input($_POST['sol_wallet']  ?? '');

    // Landing page stats
    $stat_managed   = text_input($_POST['stat_managed']   ?? '');
    $stat_bots      = text_input($_POST['stat_bots']      ?? '');
    $stat_winrate   = text_input($_POST['stat_winrate']   ?? '');
    $stat_countries = text_input($_POST['stat_countries'] ?? '');

    $upd = $conn->prepare(
        "UPDATE settings SET sitename=?, siteurl=?, sitemail=?, ref_bonus=?, whatsapp=?,
         btc_wallet=?, eth_wallet=?, usdt_trc20=?, usdt_erc20=?, ltc_wallet=?,
         bnb_wallet=?, doge_wallet=?, sol_wallet=?,
         stat_managed=?, stat_bots=?, stat_winrate=?, stat_countries=?
         WHERE id = 1"
    );
    $upd->bind_param(
        'sssdssssssssssssss',
        $sitename_new, $siteurl_new, $sitemail_new, $ref_bonus_new, $whatsapp_new,
        $btc, $eth, $trc20, $erc20, $ltc, $bnb, $doge, $sol,
        $stat_managed, $stat_bots, $stat_winrate, $stat_countries
    );
    $upd->execute() ? $msg = 'Settings saved.' : $error = 'Failed to save settings.';
    $upd->close();

    // Refresh settings
    $sq = $conn->prepare("SELECT * FROM settings WHERE id = 1 LIMIT 1");
    $sq->execute();
    $settingsRow = $sq->get_result()->fetch_assoc();
    $sq->close();
}


$s = $settingsRow;
?>
<?php include 'header.php'; ?>

<div class="adm-page-header">
  <div class="adm-page-title">Platform Settings</div>
</div>

<?php if ($msg):   ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<form method="POST">
  <!-- General -->
  <div class="adm-card" style="margin-bottom:20px;">
    <div class="adm-card-title">General</div>
    <div class="form-row">
      <div class="form-group">
        <label>Site Name</label>
        <input type="text" name="sitename" value="<?= htmlspecialchars($s['sitename']) ?>" required>
      </div>
      <div class="form-group">
        <label>Site Email</label>
        <input type="email" name="sitemail" value="<?= htmlspecialchars($s['sitemail']) ?>" required>
      </div>
    </div>
    <div class="form-group">
      <label>Site URL (no trailing slash)</label>
      <input type="url" name="siteurl" value="<?= htmlspecialchars($s['siteurl']) ?>" required>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Referral Commission %</label>
        <input type="number" name="ref_bonus" value="<?= htmlspecialchars($s['ref_bonus']) ?>" step="0.01" min="0" max="100">
      </div>
      <div class="form-group">
        <label>WhatsApp Number</label>
        <input type="text" name="whatsapp" value="<?= htmlspecialchars($s['whatsapp'] ?? '') ?>" placeholder="+1234567890">
      </div>
    </div>
  </div>

  <!-- Wallet Addresses -->
  <div class="adm-card" style="margin-bottom:20px;">
    <div class="adm-card-title">Wallet Addresses <span style="font-size:0.78rem;color:var(--text-muted);font-weight:400;">(stored securely in DB — never exposed in client JS)</span></div>
    <div class="form-row">
      <div class="form-group"><label>Bitcoin (BTC)</label><input type="text" name="btc_wallet"  value="<?= htmlspecialchars($s['btc_wallet']  ?? '') ?>" placeholder="bc1q..."></div>
      <div class="form-group"><label>Ethereum (ETH)</label><input type="text" name="eth_wallet" value="<?= htmlspecialchars($s['eth_wallet']  ?? '') ?>" placeholder="0x..."></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>USDT TRC20</label><input type="text" name="usdt_trc20"  value="<?= htmlspecialchars($s['usdt_trc20']  ?? '') ?>" placeholder="T..."></div>
      <div class="form-group"><label>USDT ERC20</label><input type="text" name="usdt_erc20"  value="<?= htmlspecialchars($s['usdt_erc20']  ?? '') ?>" placeholder="0x..."></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Litecoin (LTC)</label><input type="text" name="ltc_wallet"  value="<?= htmlspecialchars($s['ltc_wallet']  ?? '') ?>" placeholder="ltc1..."></div>
      <div class="form-group"><label>BNB BEP20</label><input type="text" name="bnb_wallet"   value="<?= htmlspecialchars($s['bnb_wallet']   ?? '') ?>" placeholder="0x..."></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Dogecoin (DOGE)</label><input type="text" name="doge_wallet" value="<?= htmlspecialchars($s['doge_wallet'] ?? '') ?>" placeholder="D..."></div>
      <div class="form-group"><label>Solana (SOL)</label><input type="text" name="sol_wallet"  value="<?= htmlspecialchars($s['sol_wallet']  ?? '') ?>" placeholder="..."></div>
    </div>
  </div>

  <!-- Landing page stats -->
  <div class="adm-card" style="margin-bottom:20px;">
    <div class="adm-card-title">Landing Page Stats</div>
    <div class="form-row">
      <div class="form-group"><label>Total Managed</label><input type="text" name="stat_managed"   value="<?= htmlspecialchars($s['stat_managed']   ?? '$12M+') ?>" placeholder="$12M+"></div>
      <div class="form-group"><label>Active Bots</label><input type="text" name="stat_bots"       value="<?= htmlspecialchars($s['stat_bots']       ?? '48K+') ?>" placeholder="48K+"></div>
      <div class="form-group"><label>Win Rate</label><input type="text" name="stat_winrate"     value="<?= htmlspecialchars($s['stat_winrate']     ?? '87.5%') ?>" placeholder="87.5%"></div>
      <div class="form-group"><label>Countries</label><input type="text" name="stat_countries"  value="<?= htmlspecialchars($s['stat_countries']  ?? '80+') ?>" placeholder="80+"></div>
    </div>
  </div>

  <button type="submit" class="btn-primary">Save Settings</button>
</form>

<!-- Change Password -->
<div class="adm-card" style="margin-top:30px;">
  <div class="adm-card-title">Change Admin Password</div>
  <form method="POST" style="max-width:480px;">
    <input type="hidden" name="change_password" value="1">
    <div class="form-group">
      <label>Current Password</label>
      <input type="password" name="current_password" required placeholder="••••••••">
    </div>
    <div class="form-group">
      <label>New Password</label>
      <input type="password" name="new_password" required placeholder="Min 8 characters">
    </div>
    <div class="form-group">
      <label>Confirm New Password</label>
      <input type="password" name="confirm_password" required placeholder="Repeat new password">
    </div>
    <button type="submit" class="btn-primary">Update Password</button>
  </form>
</div>

<?php include 'footer.php'; ?>
