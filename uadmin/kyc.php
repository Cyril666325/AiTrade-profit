<?php
require_once __DIR__ . '/session.php';

$msg = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $uid    = (int)($_POST['uid'] ?? 0);

    if ($action === 'approve' && $uid > 0) {
        $upd = $conn->prepare("UPDATE users SET status = 'Verified' WHERE id = ?");
        $upd->bind_param('i', $uid);
        $upd->execute();
        $upd->close();

        $uq = $conn->prepare("SELECT fname, email FROM users WHERE id = ? LIMIT 1");
        $uq->bind_param('i', $uid);
        $uq->execute();
        $uq->bind_result($fname_r, $email_r);
        $uq->fetch();
        $uq->close();

        $body = "<p>Hi <strong>$fname_r</strong>,</p>
                 <p>Your KYC verification has been <strong>approved</strong>. You can now withdraw funds.</p>";
        sendMail($email_r, "KYC Verified — $sitename", $body);
        $msg = "KYC approved for user #$uid.";
    }

    elseif ($action === 'reject' && $uid > 0) {
        $upd = $conn->prepare("UPDATE users SET status = 'Not Verified', Id_photo = NULL WHERE id = ?");
        $upd->bind_param('i', $uid);
        $upd->execute();
        $upd->close();

        $uq = $conn->prepare("SELECT fname, email FROM users WHERE id = ? LIMIT 1");
        $uq->bind_param('i', $uid);
        $uq->execute();
        $uq->bind_result($fname_r, $email_r);
        $uq->fetch();
        $uq->close();

        $body = "<p>Hi <strong>$fname_r</strong>,</p>
                 <p>Your KYC document was not accepted. Please re-upload a clear, valid government-issued ID.</p>";
        sendMail($email_r, "KYC Rejected — $sitename", $body);
        $msg = "KYC rejected for user #$uid. Document cleared.";
    }
}
?>
<?php include 'header.php'; ?>

<div class="adm-page-header">
  <div class="adm-page-title">KYC Verification</div>
</div>

<?php if ($msg):   ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="tab-bar">
  <?php $filter = in_array($_GET['s'] ?? '', ['submitted','Verified','Not Verified']) ? $_GET['s'] : 'submitted'; ?>
  <a href="?s=submitted"    class="tab-link <?= $filter==='submitted'    ? 'active':'' ?>">Pending Review</a>
  <a href="?s=Verified"     class="tab-link <?= $filter==='Verified'     ? 'active':'' ?>">Verified</a>
  <a href="?s=Not Verified" class="tab-link <?= $filter==='Not Verified' ? 'active':'' ?>">Not Verified</a>
</div>

<div class="adm-card" style="margin-top:0;border-top-left-radius:0;border-top-right-radius:0;">
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Country</th><th>Document</th><th>Joined</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
      <?php
        $stmt = $conn->prepare(
            "SELECT id, fname, lname, email, country, Id_photo, date, status
             FROM users WHERE status = ? ORDER BY date DESC"
        );
        $stmt->bind_param('s', $filter);
        $stmt->execute();
        $rows = $stmt->get_result();
        if ($rows->num_rows === 0): ?>
          <tr><td colspan="8" class="empty">No users in this category.</td></tr>
        <?php else: while ($r = $rows->fetch_assoc()): ?>
          <tr>
            <td><?= $r['id'] ?></td>
            <td><?= htmlspecialchars($r['fname'] . ' ' . $r['lname']) ?></td>
            <td><?= htmlspecialchars($r['email']) ?></td>
            <td><?= htmlspecialchars($r['country'] ?? '—') ?></td>
            <td>
              <?php if ($r['Id_photo']): ?>
                <a href="../uploads/kyc/<?= htmlspecialchars($r['Id_photo']) ?>" target="_blank" class="btn-sm">View Doc</a>
              <?php else: ?>
                <span style="color:var(--text-muted)">No doc</span>
              <?php endif; ?>
            </td>
            <td><?= date('d M Y', strtotime($r['date'])) ?></td>
            <td><span class="badge badge-<?= strtolower(str_replace(' ', '-', $r['status'])) ?>"><?= htmlspecialchars($r['status']) ?></span></td>
            <td>
              <?php if ($r['status'] === 'submitted'): ?>
              <form method="POST" style="display:inline" onsubmit="return confirm('Approve KYC?')">
                <input type="hidden" name="action" value="approve">
                <input type="hidden" name="uid"    value="<?= $r['id'] ?>">
                <button type="submit" class="btn-sm btn-success">Approve</button>
              </form>
              <form method="POST" style="display:inline" onsubmit="return confirm('Reject KYC?')">
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="uid"    value="<?= $r['id'] ?>">
                <button type="submit" class="btn-sm btn-danger">Reject</button>
              </form>
              <?php else: ?>—<?php endif; ?>
            </td>
          </tr>
        <?php endwhile; endif; $stmt->close(); ?>
      </tbody>
    </table>
  </div>
</div>

<?php include 'footer.php'; ?>
