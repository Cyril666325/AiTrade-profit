<?php
require_once __DIR__ . '/session.php';

$uid = (int)($_GET['id'] ?? 0);
if (!$uid) { header('Location: users.php'); exit; }

$msg = $error = '';

if ($is_demo && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = 'This action is disabled in the demo account.';
}

$uq = $conn->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$uq->bind_param('i', $uid);
$uq->execute();
$u = $uq->get_result()->fetch_assoc();
$uq->close();

if (!$u) { header('Location: users.php'); exit; }

if (!$is_demo && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $balance    = floatval($_POST['balance']    ?? 0);
    $profit     = floatval($_POST['profit']     ?? 0);
    $ref_bal    = floatval($_POST['ref_balance']?? 0);
    $bonus      = floatval($_POST['bonus']      ?? 0);
    $newStatus  = text_input($_POST['status']   ?? '');

    $allowed_statuses = ['Not Verified','submitted','Verified','active','blocked','suspended'];
    if (!in_array($newStatus, $allowed_statuses)) $newStatus = $u['status'];

    $upd = $conn->prepare(
        "UPDATE users SET balance=?, profit=?, ref_balance=?, bonus=?, status=? WHERE id=?"
    );
    $upd->bind_param('ddddssi', $balance, $profit, $ref_bal, $bonus, $newStatus, $uid);
    $upd->execute() ? $msg = 'User updated.' : $error = 'Update failed.';
    $upd->close();

    // Refresh
    $uq = $conn->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
    $uq->bind_param('i', $uid);
    $uq->execute();
    $u = $uq->get_result()->fetch_assoc();
    $uq->close();
}
?>
<?php include 'header.php'; ?>

<div class="adm-page-header">
  <div class="adm-page-title">Edit User #<?= $uid ?></div>
  <a href="users.php" class="btn-ghost">← Back</a>
</div>

<?php if ($msg):   ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="adm-card">
  <div style="margin-bottom:16px;">
    <strong><?= htmlspecialchars($u['fname'] . ' ' . $u['lname']) ?></strong>
    <span style="color:var(--text-muted);margin-left:8px;font-size:0.84rem"><?= htmlspecialchars($u['email']) ?></span>
  </div>

  <form method="POST">
    <div class="form-row">
      <div class="form-group">
        <label>Account Balance ($)</label>
        <input type="number" name="balance" value="<?= $u['balance'] ?>" step="0.01" min="0">
      </div>
      <div class="form-group">
        <label>Profit Wallet ($)</label>
        <input type="number" name="profit" value="<?= $u['profit'] ?>" step="0.01" min="0">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Referral Balance ($)</label>
        <input type="number" name="ref_balance" value="<?= $u['ref_balance'] ?>" step="0.01" min="0">
      </div>
      <div class="form-group">
        <label>Bonus ($)</label>
        <input type="number" name="bonus" value="<?= $u['bonus'] ?>" step="0.01" min="0">
      </div>
    </div>
    <div class="form-group">
      <label>Account Status</label>
      <select name="status">
        <?php foreach (['Not Verified','submitted','Verified','active','blocked','suspended'] as $st): ?>
          <option value="<?= $st ?>" <?= $u['status'] === $st ? 'selected' : '' ?>><?= $st ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button type="submit" class="btn-primary">Save Changes</button>
  </form>
</div>

<?php include 'footer.php'; ?>
