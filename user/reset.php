<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/config.php';

$token = text_input($_GET['token'] ?? '');
$msg   = $error = '';
$validToken = false;
$userId = null;

if ($token) {
    $now  = date('Y-m-d H:i:s');
    $stmt = $conn->prepare(
        "SELECT id FROM users WHERE reset_token = ? AND reset_expires > ? LIMIT 1"
    );
    $stmt->bind_param('ss', $token, $now);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row) {
        $validToken = true;
        $userId     = (int)$row['id'];
    }
}

if (!$validToken) {
    $error = 'Invalid or expired reset link. Please request a new one.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm']  ?? '';

    if (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $hashed = hashPassword($password);
        $upd    = $conn->prepare(
            "UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?"
        );
        $upd->bind_param('si', $hashed, $userId);
        $upd->execute();
        $upd->close();
        $msg = 'Password updated successfully. You can now log in.';
        $validToken = false;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($sitename) ?> — Reset Password</title>
  <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body class="auth-body">
<div class="auth-wrap">
  <div class="auth-card">
    <div class="auth-logo">
      <span class="logo-text"><?= htmlspecialchars($sitename) ?></span>
      <span class="logo-badge">AI</span>
    </div>
    <h2 class="auth-title">New Password</h2>

    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($msg):   ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?>
      <a href="login.php" class="auth-link">Login →</a></div><?php endif; ?>

    <?php if ($validToken): ?>
    <form method="POST" class="auth-form">
      <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
      <div class="form-group">
        <label>New Password</label>
        <input type="password" name="password" required placeholder="Min 8 characters">
      </div>
      <div class="form-group">
        <label>Confirm Password</label>
        <input type="password" name="confirm" required placeholder="Repeat password">
      </div>
      <button type="submit" class="btn-auth">Update Password</button>
    </form>
    <?php elseif (!$msg): ?>
    <p class="auth-footer"><a href="forgot.php" class="auth-link">Request new reset link</a></p>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
