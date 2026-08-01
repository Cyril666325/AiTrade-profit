<?php
require_once __DIR__ . '/session.php';

$error = $msg = '';
$tab   = in_array($_GET['tab'] ?? '', ['profile','kyc','security']) ? $_GET['tab'] : 'profile';

if ($is_demo && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = 'This action is disabled in the demo account.';
}

// ── Profile update ─────────────────────────────────────────────────────────
if (!$is_demo && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'update_profile') {
        $newPhone   = text_input($_POST['phone']   ?? '');
        $newAddress = text_input($_POST['address'] ?? '');

        if (!preg_match('/^\+?[0-9]{7,15}$/', $newPhone)) {
            $error = 'Invalid phone number.';
        } elseif (empty($newAddress)) {
            $error = 'Address is required.';
        } else {
            $upd = $conn->prepare("UPDATE users SET phone = ?, address = ? WHERE id = ?");
            $upd->bind_param('ssi', $newPhone, $newAddress, $userId);
            $upd->execute() ? $msg = 'Profile updated.' : $error = 'Update failed.';
            $upd->close();
            $phone = $newPhone; $address = $newAddress;
        }
    }

    elseif ($_POST['action'] === 'change_password') {
        $oldPw   = $_POST['old_password']     ?? '';
        $newPw   = $_POST['new_password']     ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        // Fetch current hash
        $phq = $conn->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
        $phq->bind_param('i', $userId);
        $phq->execute();
        $phq->bind_result($currentHash);
        $phq->fetch();
        $phq->close();

        if (!verifyPassword($oldPw, $currentHash)) {
            $error = 'Current password is incorrect.';
        } elseif (strlen($newPw) < 8) {
            $error = 'New password must be at least 8 characters.';
        } elseif ($newPw !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            $hashed = hashPassword($newPw);
            $upd = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $upd->bind_param('si', $hashed, $userId);
            $upd->execute() ? $msg = 'Password changed successfully.' : $error = 'Failed to change password.';
            $upd->close();
        }
    }

    elseif ($_POST['action'] === 'upload_kyc') {
        if (empty($_FILES['kyc_doc']['name'])) {
            $error = 'Please select a file to upload.';
        } else {
            $ext     = strtolower(pathinfo($_FILES['kyc_doc']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','pdf'];
            if (!in_array($ext, $allowed)) {
                $error = 'File must be JPG, PNG, or PDF.';
            } elseif ($_FILES['kyc_doc']['size'] > 5 * 1024 * 1024) {
                $error = 'File must be under 5MB.';
            } else {
                $filename = 'KYC_' . $userId . '_' . time() . '.' . $ext;
                $dest     = __DIR__ . '/../uploads/kyc/' . $filename;
                if (move_uploaded_file($_FILES['kyc_doc']['tmp_name'], $dest)) {
                    $upd = $conn->prepare("UPDATE users SET Id_photo = ?, status = 'submitted' WHERE id = ?");
                    $upd->bind_param('si', $filename, $userId);
                    $upd->execute();
                    $upd->close();
                    $status   = 'submitted';
                    $id_photo = $filename;
                    $msg = 'KYC document uploaded. Under review.';
                } else {
                    $error = 'File upload failed. Please try again.';
                }
            }
        }
    }
}
?>
<?php include 'header.php'; ?>

<div class="page-header">
  <h2>My Profile</h2>
  <nav class="breadcrumb"><a href="index.php">Dashboard</a> / Profile</nav>
</div>

<?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($msg):   ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<div class="tab-bar">
  <a href="?tab=profile"  class="tab-link <?= $tab==='profile'  ? 'active':'' ?>">Profile</a>
  <a href="?tab=kyc"      class="tab-link <?= $tab==='kyc'      ? 'active':'' ?>">
    KYC Verification
    <?php if ($status === 'Not Verified'): ?><span class="badge badge-pending" style="margin-left:6px;">!</span><?php endif; ?>
  </a>
  <a href="?tab=security" class="tab-link <?= $tab==='security' ? 'active':'' ?>">Security</a>
</div>

<div class="card" style="margin-top:0;border-top-left-radius:0;border-top-right-radius:0;">

<?php if ($tab === 'profile'): ?>
  <form method="POST">
    <input type="hidden" name="action" value="update_profile">
    <div class="form-row">
      <div class="form-group">
        <label>First Name</label>
        <input type="text" value="<?= htmlspecialchars($fname) ?>" disabled>
      </div>
      <div class="form-group">
        <label>Last Name</label>
        <input type="text" value="<?= htmlspecialchars($lname) ?>" disabled>
      </div>
    </div>
    <div class="form-group">
      <label>Email</label>
      <input type="email" value="<?= htmlspecialchars($email) ?>" disabled>
    </div>
    <div class="form-group">
      <label>Country</label>
      <input type="text" value="<?= htmlspecialchars($country) ?>" disabled>
    </div>
    <div class="form-group">
      <label>Phone</label>
      <input type="tel" name="phone" value="<?= htmlspecialchars($phone ?? '') ?>" required>
    </div>
    <div class="form-group">
      <label>Address</label>
      <input type="text" name="address" value="<?= htmlspecialchars($address) ?>" required>
    </div>
    <button type="submit" class="btn-primary">Save Changes</button>
  </form>

<?php elseif ($tab === 'kyc'): ?>
  <div style="margin-bottom:18px;">
    <span style="font-size:0.8rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.8px;">Verification Status</span>
    <div style="margin-top:6px;">
      <span class="badge badge-<?= strtolower(str_replace(' ', '-', $status)) ?>" style="font-size:0.9rem;padding:6px 16px;">
        <?= htmlspecialchars($status) ?>
      </span>
    </div>
  </div>

  <?php if ($status === 'Verified'): ?>
    <div class="alert alert-success">Your identity has been verified. You can now withdraw funds.</div>
  <?php elseif ($status === 'submitted'): ?>
    <div class="alert alert-info">Your documents are under review. We'll notify you once approved.</div>
  <?php else: ?>
    <p style="color:var(--text-muted);font-size:0.85rem;margin-bottom:16px;">
      Please upload a government-issued ID (passport, national ID, or driver's licence) to verify your identity.
      Supported formats: JPG, PNG, PDF — max 5MB.
    </p>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="upload_kyc">
      <div class="form-group">
        <label>ID Document</label>
        <input type="file" name="kyc_doc" accept=".jpg,.jpeg,.png,.pdf" required>
      </div>
      <button type="submit" class="btn-primary">Upload Document</button>
    </form>
  <?php endif; ?>

<?php elseif ($tab === 'security'): ?>
  <form method="POST">
    <input type="hidden" name="action" value="change_password">
    <div class="form-group">
      <label>Current Password</label>
      <input type="password" name="old_password" required>
    </div>
    <div class="form-group">
      <label>New Password</label>
      <input type="password" name="new_password" required placeholder="Min 8 characters">
    </div>
    <div class="form-group">
      <label>Confirm New Password</label>
      <input type="password" name="confirm_password" required>
    </div>
    <button type="submit" class="btn-primary">Update Password</button>
  </form>
<?php endif; ?>
</div>

<?php include 'footer.php'; ?>
