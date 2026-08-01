<?php
require_once __DIR__ . '/session.php';

$msg = $error = '';

if ($is_demo && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = 'This action is disabled in the demo account.';
}

if (!$is_demo && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $uid    = (int)($_POST['uid'] ?? 0);

    if ($action === 'delete' && $uid > 0) {
        $del = $conn->prepare("DELETE FROM users WHERE id = ?");
        $del->bind_param('i', $uid);
        $del->execute();
        $del->close();
        $msg = "User #$uid deleted.";
    }

    elseif (in_array($action, ['active','blocked','suspended']) && $uid > 0) {
        $upd = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
        $upd->bind_param('si', $action, $uid);
        $upd->execute();
        $upd->close();
        $msg = "User #$uid status set to $action.";
    }

    elseif ($action === 'credit' && $uid > 0) {
        $amount = floatval($_POST['credit_amount'] ?? 0);
        $type   = in_array($_POST['credit_type'] ?? '', ['balance','profit','bonus']) ? $_POST['credit_type'] : 'balance';
        if ($amount > 0) {
            $upd = $conn->prepare("UPDATE users SET $type = $type + ? WHERE id = ?");
            $upd->bind_param('di', $amount, $uid);
            $upd->execute();
            $upd->close();
            $msg = "\$$amount credited to user #$uid $type.";
        }
    }
}

$search = text_input($_GET['q'] ?? '');
if ($search) {
    $stmt = $conn->prepare(
        "SELECT * FROM users WHERE fname LIKE ? OR lname LIKE ? OR email LIKE ? ORDER BY date DESC LIMIT 50"
    );
    $like = "%$search%";
    $stmt->bind_param('sss', $like, $like, $like);
} else {
    $stmt = $conn->prepare("SELECT * FROM users ORDER BY date DESC LIMIT 50");
}
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<?php include 'header.php'; ?>

<div class="adm-page-header">
  <div class="adm-page-title">Users (<?= count($users) ?>)</div>
</div>

<?php if ($msg):   ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<form method="GET" style="margin-bottom:16px;display:flex;gap:10px;">
  <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search by name or email…" style="flex:1;padding:9px 12px;border:1px solid var(--border);border-radius:7px;background:var(--bg);color:var(--text);font-size:0.85rem;">
  <button type="submit" class="btn-primary" style="padding:9px 18px;">Search</button>
  <?php if ($search): ?><a href="users.php" class="btn-ghost" style="padding:9px 14px;">Clear</a><?php endif; ?>
</form>

<div class="adm-card">
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Country</th><th>Balance</th><th>Profit</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead>
      <tbody>
      <?php if (empty($users)): ?>
        <tr><td colspan="9" class="empty">No users found.</td></tr>
      <?php else: foreach ($users as $u): ?>
        <tr>
          <td><?= $u['id'] ?></td>
          <td><?= htmlspecialchars($u['fname'] . ' ' . $u['lname']) ?></td>
          <td style="font-size:0.8rem"><?= htmlspecialchars($u['email']) ?></td>
          <td><?= htmlspecialchars($u['country'] ?? '—') ?></td>
          <td>$<?= number_format((float)$u['balance'], 2) ?></td>
          <td>$<?= number_format((float)$u['profit'], 2) ?></td>
          <td><span class="badge badge-<?= strtolower(str_replace(' ', '-', $u['status'])) ?>"><?= htmlspecialchars($u['status']) ?></span></td>
          <td><?= date('d M Y', strtotime($u['date'])) ?></td>
          <td>
            <a href="user_edit.php?id=<?= $u['id'] ?>" class="btn-sm">Edit</a>
            <form method="POST" style="display:inline" onsubmit="return confirm('Delete user?')">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="uid" value="<?= $u['id'] ?>">
              <button type="submit" class="btn-sm btn-danger">Del</button>
            </form>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include 'footer.php'; ?>
