<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/config.php';

$msg = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = text_input($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();

        // Always show success message to prevent email enumeration
        if ($exists) {
            $token   = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $upd = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?");
            $upd->bind_param('sss', $token, $expires, $email);
            $upd->execute();
            $upd->close();

            $resetLink = "$siteurl/user/reset.php?token=$token";
            $body = "<p>A password reset was requested for your account.</p>
                     <p><a href='$resetLink' style='color:#c9a84c'>Click here to reset your password</a></p>
                     <p>This link expires in 1 hour. If you didn't request this, ignore this email.</p>";
            sendMail($email, "Password Reset — $sitename", $body);
        }
        $msg = 'If that email exists, a reset link has been sent.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($sitename) ?> — Forgot Password</title>
  <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body class="auth-body">
<div class="auth-wrap">
  <div class="auth-card">
    <div class="auth-logo">
      <span class="logo-text"><?= htmlspecialchars($sitename) ?></span>
      <span class="logo-badge">AI</span>
    </div>
    <h2 class="auth-title">Reset Password</h2>
    <p class="auth-sub">Enter your email to receive a reset link</p>

    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($msg):   ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

    <form method="POST" class="auth-form">
      <div class="form-group">
        <label>Email Address</label>
        <input type="email" name="email" required placeholder="you@example.com">
      </div>
      <button type="submit" class="btn-auth">Send Reset Link</button>
    </form>
    <p class="auth-footer"><a href="login.php" class="auth-link">← Back to login</a></p>
  </div>
</div>
</body>
</html>
