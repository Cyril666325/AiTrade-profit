<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/config.php';

if (isset($_SESSION['ADMINLOGIN'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = text_input($_POST['email']    ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Both fields are required.';
    } else {
        $stmt = $conn->prepare("SELECT id, name, email, password FROM admin WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row || !verifyPassword($password, $row['password'])) {
            $error = 'Invalid credentials.';
        } else {
            session_regenerate_id(true);
            $_SESSION['ADMINLOGIN']  = $row['id'];
            $_SESSION['admin_email'] = $row['email'];
            $_SESSION['admin_name']  = $row['name'];
            header('Location: index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($sitename) ?> — Admin Login</title>
  <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body class="auth-body">
<div class="auth-wrap">
  <div class="auth-card">
    <div class="auth-logo">
      <span class="logo-text"><?= htmlspecialchars($sitename) ?></span>
      <span class="logo-badge">ADMIN</span>
    </div>
    <h2 class="auth-title">Admin Access</h2>

    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <form method="POST" class="auth-form">
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" required autofocus>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" required>
      </div>
      <button type="submit" class="btn-auth">Sign In</button>
    </form>
  </div>
</div>
</body>
</html>
