<?php
/**
 * One-time setup script.
 * Creates the first admin account.
 * DELETE THIS FILE after running it.
 */

require_once __DIR__ . '/config/db.php';

$errors = [];
$done   = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['confirm']  ?? '');
    $secret   = trim($_POST['secret']   ?? '');

    // Hard-coded install secret — change this before uploading
    if ($secret !== 'SETUP2026') {
        $errors[] = 'Invalid setup secret.';
    }
    if (strlen($name) < 2)          $errors[] = 'Name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required.';
    if (strlen($password) < 8)      $errors[] = 'Password must be at least 8 characters.';
    if ($password !== $confirm)     $errors[] = 'Passwords do not match.';

    // Check if an admin already exists
    $chk = $conn->query("SELECT COUNT(*) FROM admin");
    if ($chk && $chk->fetch_row()[0] > 0) {
        $errors[] = 'An admin account already exists. Delete this file immediately.';
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("INSERT INTO admin (name, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param('sss', $name, $email, $hash);
        if ($stmt->execute()) {
            $done = true;
        } else {
            $errors[] = 'DB error: ' . $conn->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>First-Time Setup</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { background: #070710; color: #e0e0f0; font-family: 'Segoe UI', system-ui, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; }
    .card { background: #111120; border: 1px solid #1e1e35; border-radius: 12px; padding: 32px; width: 100%; max-width: 420px; }
    h2 { font-size: 1.15rem; font-weight: 800; color: #f0b429; margin-bottom: 6px; }
    p  { font-size: 0.82rem; color: #7070a0; margin-bottom: 22px; }
    .warn { background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.25); color: #f87171; padding: 10px 14px; border-radius: 7px; font-size: 0.82rem; margin-bottom: 16px; }
    .ok   { background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.25); color: #10b981; padding: 14px; border-radius: 7px; font-size: 0.88rem; }
    label { display: block; font-size: 0.74rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #7070a0; margin-bottom: 5px; margin-top: 12px; }
    input { width: 100%; background: #1a1a28; border: 1px solid #1e1e35; border-radius: 7px; color: #e0e0f0; font-size: 0.9rem; padding: 10px 12px; outline: none; }
    input:focus { border-color: #f0b429; }
    button { display: block; width: 100%; margin-top: 20px; padding: 12px; background: #f0b429; color: #070710; font-weight: 800; font-size: 0.95rem; border: none; border-radius: 8px; cursor: pointer; }
    button:hover { background: #d9a020; }
    .delete-note { margin-top: 18px; font-size: 0.76rem; color: #7070a0; background: rgba(240,180,41,0.06); border: 1px solid rgba(240,180,41,0.15); border-radius: 6px; padding: 10px 12px; }
    .delete-note strong { color: #f0b429; }
  </style>
</head>
<body>
<div class="card">
  <h2>First-Time Setup</h2>
  <p>Creates the initial admin account. Delete this file immediately after use.</p>

  <?php if ($done): ?>
    <div class="ok">
      <strong>Admin account created successfully!</strong><br><br>
      You can now log in at <a href="uadmin/login.php" style="color:#10b981">uadmin/login.php</a>.<br><br>
      <strong style="color:#f87171">Delete this file (setup.php) from your server immediately.</strong>
    </div>
  <?php else: ?>

    <?php foreach ($errors as $e): ?>
      <div class="warn"><?= htmlspecialchars($e) ?></div>
    <?php endforeach; ?>

    <form method="POST">
      <label>Admin Name</label>
      <input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" placeholder="Site Admin" required>

      <label>Admin Email</label>
      <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="admin@yourdomain.com" required>

      <label>Password (min 8 chars)</label>
      <input type="password" name="password" required>

      <label>Confirm Password</label>
      <input type="password" name="confirm" required>

      <label>Setup Secret</label>
      <input type="text" name="secret" placeholder="Enter the secret key" autocomplete="off" required>

      <button type="submit">Create Admin Account</button>
    </form>

    <div class="delete-note">
      The default setup secret is <strong>SETUP2026</strong>. Change it in this file before uploading, or restrict access via <code>.htaccess</code>.
    </div>

  <?php endif; ?>
</div>
</body>
</html>
