<?php
require_once __DIR__ . '/session.php';

$msg = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action    = $_POST['action']    ?? '';
    $depositId = (int)($_POST['id'] ?? 0);

    if ($action === 'approve' && $depositId > 0) {
        // Get deposit info
        $dq = $conn->prepare("SELECT * FROM deposits WHERE id = ? LIMIT 1");
        $dq->bind_param('i', $depositId);
        $dq->execute();
        $dep = $dq->get_result()->fetch_assoc();
        $dq->close();

        if (!$dep) {
            $error = 'Deposit not found.';
        } elseif ($dep['status'] === 'completed') {
            $error = 'Deposit already approved.';
        } else {
            $amount = (float)$dep['amount'];
            $uid    = (int)$dep['userId'];

            // Credit user balance
            $upd = $conn->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
            $upd->bind_param('di', $amount, $uid);
            $upd->execute();
            $upd->close();

            // Mark deposit completed
            $upd2 = $conn->prepare("UPDATE deposits SET status = 'completed' WHERE id = ?");
            $upd2->bind_param('i', $depositId);
            $upd2->execute();
            $upd2->close();

            // Referral commission
            $ref_q = $conn->prepare("SELECT referral, email, fname FROM users WHERE id = ? LIMIT 1");
            $ref_q->bind_param('i', $uid);
            $ref_q->execute();
            $ref_q->bind_result($referral, $userEmail, $userFname);
            $ref_q->fetch();
            $ref_q->close();

            if ($referral) {
                $commission = round(($ref_bonus / 100) * $amount, 2);
                $rc = $conn->prepare("UPDATE users SET ref_balance = ref_balance + ? WHERE refcode = ?");
                $rc->bind_param('ds', $commission, $referral);
                $rc->execute();
                $rc->close();
            }

            // Email user
            $body = "<p>Hi <strong>$userFname</strong>,</p>
                     <p>Your deposit of <strong>\$$amount</strong> via <strong>{$dep['method']}</strong> has been approved.</p>
                     <p>Your account balance has been updated.</p>";
            sendMail($userEmail, "Deposit Approved — $sitename", $body);

            $msg = "Deposit #$depositId approved. \$$amount credited to user.";
        }
    }

    elseif ($action === 'reject' && $depositId > 0) {
        $upd = $conn->prepare("UPDATE deposits SET status = 'rejected' WHERE id = ?");
        $upd->bind_param('i', $depositId);
        $upd->execute();
        $upd->close();
        $msg = "Deposit #$depositId rejected.";
    }

    elseif ($action === 'delete' && $depositId > 0) {
        $del = $conn->prepare("DELETE FROM deposits WHERE id = ?");
        $del->bind_param('i', $depositId);
        $del->execute();
        $del->close();
        $msg = "Deposit #$depositId deleted.";
    }
}
?>
<?php include 'header.php'; ?>

<div class="adm-page-header">
  <div class="adm-page-title">Deposits</div>
</div>

<?php if ($msg):   ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<!-- Filter tabs -->
<?php
$filter = in_array($_GET['s'] ?? '', ['pending','completed','rejected']) ? $_GET['s'] : 'pending';
?>
<div class="tab-bar">
  <a href="?s=pending"   class="tab-link <?= $filter==='pending'   ? 'active':'' ?>">Pending</a>
  <a href="?s=completed" class="tab-link <?= $filter==='completed' ? 'active':'' ?>">Completed</a>
  <a href="?s=rejected"  class="tab-link <?= $filter==='rejected'  ? 'active':'' ?>">Rejected</a>
</div>

<div class="adm-card" style="margin-top:0;border-top-left-radius:0;border-top-right-radius:0;">
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>#</th><th>User</th><th>Method</th><th>Amount</th><th>Trans. ID</th><th>Date</th><th>Proof</th><th>Actions</th></tr></thead>
      <tbody>
      <?php
        $stmt = $conn->prepare(
            "SELECT d.*, u.fname, u.lname, u.email
             FROM deposits d JOIN users u ON u.id = d.userId
             WHERE d.status = ? ORDER BY d.date DESC"
        );
        $stmt->bind_param('s', $filter);
        $stmt->execute();
        $rows = $stmt->get_result();
        if ($rows->num_rows === 0): ?>
          <tr><td colspan="8" class="empty">No <?= $filter ?> deposits.</td></tr>
        <?php else: while ($r = $rows->fetch_assoc()): ?>
          <tr>
            <td><?= $r['id'] ?></td>
            <td>
              <?= htmlspecialchars($r['fname'] . ' ' . $r['lname']) ?>
              <div style="font-size:0.75rem;color:var(--text-muted)"><?= htmlspecialchars($r['email']) ?></div>
            </td>
            <td><?= htmlspecialchars($r['method']) ?></td>
            <td><strong>$<?= number_format((float)$r['amount'], 2) ?></strong></td>
            <td class="mono" style="font-size:0.75rem"><?= htmlspecialchars($r['tranxId']) ?></td>
            <td><?= date('d M Y', strtotime($r['date'])) ?></td>
            <td>
              <?php if ($r['proof']): ?>
                <a href="../uploads/<?= htmlspecialchars($r['proof']) ?>" target="_blank" class="btn-sm">View</a>
              <?php else: ?>
                <span style="color:var(--text-muted)">—</span>
              <?php endif; ?>
            </td>
            <td>
              <form method="POST" style="display:inline" onsubmit="return confirm('Approve this deposit?')">
                <input type="hidden" name="action" value="approve">
                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                <?php if ($r['status'] === 'pending'): ?>
                  <button type="submit" class="btn-sm btn-success">Approve</button>
                <?php endif; ?>
              </form>
              <form method="POST" style="display:inline" onsubmit="return confirm('Reject this deposit?')">
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                <?php if ($r['status'] === 'pending'): ?>
                  <button type="submit" class="btn-sm btn-danger">Reject</button>
                <?php endif; ?>
              </form>
              <form method="POST" style="display:inline" onsubmit="return confirm('Delete this record?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                <button type="submit" class="btn-sm">Delete</button>
              </form>
            </td>
          </tr>
        <?php endwhile; endif; $stmt->close(); ?>
      </tbody>
    </table>
  </div>
</div>

<?php include 'footer.php'; ?>
