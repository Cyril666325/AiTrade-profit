<?php
require_once __DIR__ . '/session.php';

$msg = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $wdId   = (int)($_POST['id'] ?? 0);

    if ($action === 'approve' && $wdId > 0) {
        $wq = $conn->prepare("SELECT * FROM withdrawals WHERE id = ? LIMIT 1");
        $wq->bind_param('i', $wdId);
        $wq->execute();
        $wd = $wq->get_result()->fetch_assoc();
        $wq->close();

        if (!$wd) {
            $error = 'Withdrawal not found.';
        } elseif ($wd['status'] !== 'pending') {
            $error = 'This withdrawal is already processed.';
        } else {
            $upd = $conn->prepare("UPDATE withdrawals SET status = 'approved' WHERE id = ?");
            $upd->bind_param('i', $wdId);
            $upd->execute();
            $upd->close();

            // Email user
            $uq = $conn->prepare("SELECT fname FROM users WHERE id = ? LIMIT 1");
            $uid_approve = (int)$wd['userId'];
            $uq->bind_param('i', $uid_approve);
            $uq->execute();
            $uq->bind_result($fname_r);
            $uq->fetch();
            $uq->close();

            $body = "<p>Hi <strong>$fname_r</strong>,</p>
                     <p>Your withdrawal of <strong>\${$wd['amount']}</strong> via <strong>{$wd['method']}</strong> has been approved.</p>
                     <p><strong>Transaction ID:</strong> {$wd['tranxId']}</p>
                     <p>Funds will be sent to your wallet shortly.</p>";
            sendMail($wd['email'], "Withdrawal Approved — $sitename", $body);

            $msg = "Withdrawal #$wdId approved.";
        }
    }

    elseif ($action === 'reject' && $wdId > 0) {
        // Get withdrawal to refund balance
        $wq = $conn->prepare("SELECT * FROM withdrawals WHERE id = ? AND status = 'pending' LIMIT 1");
        $wq->bind_param('i', $wdId);
        $wq->execute();
        $wd = $wq->get_result()->fetch_assoc();
        $wq->close();

        if ($wd) {
            // Refund the deducted balance
            if ($wd['type'] === 'refbonus') {
                $rb = $conn->prepare("UPDATE users SET ref_balance = ref_balance + ? WHERE id = ?");
            } else {
                $rb = $conn->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
            }
            $rb->bind_param('di', $wd['amount'], $wd['userId']);
            $rb->execute();
            $rb->close();

            $upd = $conn->prepare("UPDATE withdrawals SET status = 'rejected' WHERE id = ?");
            $upd->bind_param('i', $wdId);
            $upd->execute();
            $upd->close();

            // Email user about refund
            $uq = $conn->prepare("SELECT fname FROM users WHERE id = ? LIMIT 1");
            $uid_reject = (int)$wd['userId'];
            $uq->bind_param('i', $uid_reject);
            $uq->execute();
            $uq->bind_result($fname_r);
            $uq->fetch();
            $uq->close();

            $body = "<p>Hi <strong>$fname_r</strong>,</p>
                     <p>Your withdrawal request of <strong>\${$wd['amount']}</strong> has been rejected and the funds have been returned to your account.</p>
                     <p>Contact support if you have questions.</p>";
            sendMail($wd['email'], "Withdrawal Rejected — $sitename", $body);

            $msg = "Withdrawal #$wdId rejected and balance refunded.";
        }
    }

    elseif ($action === 'delete' && $wdId > 0) {
        $del = $conn->prepare("DELETE FROM withdrawals WHERE id = ?");
        $del->bind_param('i', $wdId);
        $del->execute();
        $del->close();
        $msg = "Withdrawal #$wdId deleted.";
    }
}

$filter = in_array($_GET['s'] ?? '', ['pending','approved','rejected']) ? $_GET['s'] : 'pending';
?>
<?php include 'header.php'; ?>

<div class="adm-page-header">
  <div class="adm-page-title">Withdrawals</div>
</div>

<?php if ($msg):   ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="tab-bar">
  <a href="?s=pending"  class="tab-link <?= $filter==='pending'  ? 'active':'' ?>">Pending</a>
  <a href="?s=approved" class="tab-link <?= $filter==='approved' ? 'active':'' ?>">Approved</a>
  <a href="?s=rejected" class="tab-link <?= $filter==='rejected' ? 'active':'' ?>">Rejected</a>
</div>

<div class="adm-card" style="margin-top:0;border-top-left-radius:0;border-top-right-radius:0;">
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>#</th><th>User</th><th>Type</th><th>Method</th><th>Amount</th><th>Wallet</th><th>Trans. ID</th><th>Date</th><th>Actions</th></tr></thead>
      <tbody>
      <?php
        $stmt = $conn->prepare(
            "SELECT w.*, u.fname, u.lname FROM withdrawals w JOIN users u ON u.id = w.userId
             WHERE w.status = ? ORDER BY w.date DESC"
        );
        $stmt->bind_param('s', $filter);
        $stmt->execute();
        $rows = $stmt->get_result();
        if ($rows->num_rows === 0): ?>
          <tr><td colspan="9" class="empty">No <?= $filter ?> withdrawals.</td></tr>
        <?php else: while ($r = $rows->fetch_assoc()): ?>
          <tr>
            <td><?= $r['id'] ?></td>
            <td><?= htmlspecialchars($r['fname'] . ' ' . $r['lname']) ?></td>
            <td><?= $r['type'] === 'refbonus' ? 'Ref Bonus' : 'Balance' ?></td>
            <td><?= htmlspecialchars($r['method']) ?></td>
            <td><strong>$<?= number_format((float)$r['amount'], 2) ?></strong></td>
            <td class="mono" style="font-size:0.72rem;max-width:120px;overflow:hidden;text-overflow:ellipsis">
              <span title="<?= htmlspecialchars($r['wallet_address']) ?>"><?= htmlspecialchars(substr($r['wallet_address'] ?? '', 0, 20)) ?>...</span>
            </td>
            <td class="mono" style="font-size:0.72rem"><?= htmlspecialchars($r['tranxId']) ?></td>
            <td><?= date('d M Y', strtotime($r['date'])) ?></td>
            <td>
              <?php if ($r['status'] === 'pending'): ?>
              <form method="POST" style="display:inline" onsubmit="return confirm('Approve withdrawal?')">
                <input type="hidden" name="action" value="approve">
                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                <button type="submit" class="btn-sm btn-success">Approve</button>
              </form>
              <form method="POST" style="display:inline" onsubmit="return confirm('Reject and refund?')">
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                <button type="submit" class="btn-sm btn-danger">Reject</button>
              </form>
              <?php endif; ?>
              <form method="POST" style="display:inline" onsubmit="return confirm('Delete record?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                <button type="submit" class="btn-sm">Del</button>
              </form>
            </td>
          </tr>
        <?php endwhile; endif; $stmt->close(); ?>
      </tbody>
    </table>
  </div>
</div>

<?php include 'footer.php'; ?>
