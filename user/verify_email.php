<?php
require_once __DIR__ . '/session.php';

$error = $msg = '';

// Already verified
if ($userData['email_verified']) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = trim($_POST['otp'] ?? '');

    if (empty($otp)) {
        $error = 'Please enter the verification code.';
    } else {
        $stmt = $conn->prepare(
            "SELECT email_otp, otp_expires FROM users WHERE id = ? LIMIT 1"
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row || $row['email_otp'] !== $otp) {
            $error = 'Invalid verification code.';
        } elseif (strtotime($row['otp_expires']) < time()) {
            $error = 'Code has expired. Please request a new one.';
        } else {
            $upd = $conn->prepare(
                "UPDATE users SET email_verified = 1, email_otp = NULL, otp_expires = NULL WHERE id = ?"
            );
            $upd->bind_param('i', $userId);
            $upd->execute();
            $upd->close();
            $msg = 'Email verified successfully!';
        }
    }
}

// Resend OTP
if (isset($_GET['resend'])) {
    $otp     = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expires = date('Y-m-d H:i:s', strtotime('+30 minutes'));
    $upd = $conn->prepare("UPDATE users SET email_otp = ?, otp_expires = ? WHERE id = ?");
    $upd->bind_param('ssi', $otp, $expires, $userId);
    $upd->execute();
    $upd->close();

    $body = "<p>Hi <strong>$fname</strong>,</p>
             <p>Your new email verification code is:</p>
             <p style='font-size:36px;font-weight:700;letter-spacing:8px;color:#c9a84c;text-align:center'>$otp</p>
             <p style='color:#888'>This code expires in 30 minutes.</p>";
    sendMail($email, "Email Verification Code — $sitename", $body);
    $msg = 'A new verification code has been sent to your email.';
}
?>
<?php include 'header.php'; ?>

<div class="dash-container" style="max-width:480px;margin:60px auto;padding:0 20px;">
  <div class="card" style="padding:40px;text-align:center;">
    <i class="fa fa-envelope-open-text" style="font-size:3rem;color:var(--primary,#c9a84c);margin-bottom:20px;display:block;"></i>
    <h2 style="margin-bottom:8px;">Verify Your Email</h2>
    <p style="color:#888;margin-bottom:30px;">
      We sent a 6-digit code to <strong><?= htmlspecialchars($email) ?></strong>
    </p>

    <?php if ($msg): ?>
      <div class="alert alert-success" style="margin-bottom:20px;"><?= htmlspecialchars($msg) ?></div>
      <?php if (strpos($msg, 'verified') !== false): ?>
        <a href="index.php" class="btn-primary" style="display:inline-block;margin-top:10px;">Go to Dashboard →</a>
      <?php endif; ?>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="alert alert-error" style="margin-bottom:20px;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!$msg || strpos($msg, 'sent') !== false): ?>
    <form method="POST">
      <div style="margin-bottom:20px;">
        <input type="text" name="otp" maxlength="6" placeholder="000000"
               style="width:100%;text-align:center;font-size:2rem;letter-spacing:12px;padding:16px;
                      background:#1a1a2e;border:1px solid rgba(255,255,255,0.1);border-radius:10px;
                      color:#fff;outline:none;">
      </div>
      <button type="submit" class="btn-primary" style="width:100%;padding:14px;font-size:1rem;">
        Verify Email
      </button>
    </form>
    <p style="margin-top:20px;color:#888;font-size:0.9rem;">
      Didn't receive it? <a href="?resend=1" style="color:var(--primary,#c9a84c);">Resend code</a>
    </p>
    <?php endif; ?>
  </div>
</div>

<?php include 'footer.php'; ?>
