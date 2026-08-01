<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/config.php';

if (isset($_SESSION['USERLOGIN'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = text_input($_POST['email']    ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Email and password are required.';
    } else {
        $stmt = $conn->prepare(
            "SELECT id, refcode, password, status FROM users WHERE email = ? LIMIT 1"
        );
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row || !verifyPassword($password, $row['password'])) {
            $error = 'Invalid email or password.';
        } elseif ($row['status'] === 'blocked') {
            $error = 'Your account has been blocked. Contact support.';
        } else {
            loginSuccessRedirect($row['refcode'], (int)$row['id'], 'index.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($sitename) ?> — Sign In</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.4/gsap.min.js"></script>
  <style>
    :root {
      --bg:      #080b0a;
      --bg2:     #0c1210;
      --card:    #111816;
      --border:  rgba(255,255,255,0.05);
      --primary: #00ffaa;
      --primary-dark: #00cc88;
      --text:    #ffffff;
      --muted:   #8a9a95;
      --error:   #ff4d4d;
      --radius:  24px;
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html, body {
      margin: 0; padding: 0;
      background: var(--bg);
      color: var(--text);
      font-family: 'Inter', system-ui, sans-serif;
      height: 100vh;
      overflow: hidden; /* No scroll on desktop auth */
    }
    a { color: inherit; text-decoration: none; }
    
    .split-layout {
      display: flex;
      height: 100vh;
      width: 100vw;
    }
    
    /* ── Left Pane: Cinematic ── */
    .split-left {
      flex: 1.2;
      position: relative;
      background: radial-gradient(circle at 30% 50%, #0c1a15 0%, var(--bg) 80%);
      overflow: hidden;
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding: 60px;
      border-right: 1px solid var(--border);
    }
    .sl-bg-art {
      position: absolute; top: -50%; left: -50%; width: 200%; height: 200%;
      background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320" preserveAspectRatio="none"><path fill="none" stroke="%2300ffaa" stroke-width="2" stroke-opacity="0.1" d="M0,160L48,186.7C96,213,192,267,288,266.7C384,267,480,213,576,181.3C672,149,768,139,864,165.3C960,192,1056,256,1152,272C1248,288,1344,256,1392,240L1440,224"></path></svg>') no-repeat center center;
      background-size: cover; opacity: 0.5; z-index: 0; pointer-events: none;
    }
    .sl-content {
      position: relative; z-index: 2; max-width: 500px;
    }
    .sl-logo { font-size: 1.5rem; font-weight: 800; color: var(--primary); display: flex; align-items: center; gap: 10px; letter-spacing: 0.05em; margin-bottom: 60px; }
    .sl-headline { font-size: 3.5rem; font-weight: 300; line-height: 1.1; margin-bottom: 24px; letter-spacing: -1px; }
    .sl-headline strong { font-weight: 700; color: var(--primary); }
    .sl-sub { font-size: 1.1rem; color: var(--muted); line-height: 1.6; }
    
    .sl-float-card {
      position: absolute; right: 10%; top: 20%;
      background: rgba(17,24,22,0.6); backdrop-filter: blur(12px);
      border: 1px solid rgba(0,255,170,0.15); border-radius: 16px;
      padding: 20px; width: 220px; box-shadow: 0 20px 40px rgba(0,0,0,0.5);
      z-index: 2;
    }
    .fc-lbl { font-size: 0.75rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 4px; }
    .fc-val { font-size: 1.5rem; font-weight: 300; color: var(--primary); }
    .fc-val i { font-size: 1rem; }

    .sl-float-card.c2 { top: auto; bottom: 20%; right: 20%; }

    /* ── Right Pane: Auth Form ── */
    .split-right {
      flex: 1;
      background: var(--bg2);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 40px;
      position: relative;
    }
    .auth-wrapper { width: 100%; max-width: 400px; position: relative; z-index: 2; }
    .auth-header { margin-bottom: 40px; }
    .auth-header h2 { font-size: 2rem; font-weight: 300; margin-bottom: 8px; }
    .auth-header p { color: var(--muted); font-size: 0.95rem; }

    .alert { padding: 12px 16px; border-radius: 8px; font-size: 0.9rem; margin-bottom: 24px; }
    .alert-error { background: rgba(255,77,77,0.1); color: var(--error); border: 1px solid rgba(255,77,77,0.2); }

    .form-group { margin-bottom: 24px; }
    .form-group label { display: block; font-size: 0.8rem; font-weight: 600; color: var(--muted); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.05em; }
    .form-group input {
      width: 100%; background: var(--card); border: 1px solid var(--border); border-radius: 8px;
      color: var(--text); font-size: 1rem; padding: 14px 16px; outline: none; transition: border-color 0.2s, box-shadow 0.2s;
    }
    .form-group input:focus { border-color: var(--primary); box-shadow: 0 0 0 2px rgba(0,255,170,0.1); }
    
    .form-actions { display: flex; justify-content: flex-end; margin-top: -12px; margin-bottom: 24px; }
    .form-actions a { font-size: 0.85rem; color: var(--muted); transition: color 0.2s; }
    .form-actions a:hover { color: var(--primary); }

    .btn-submit {
      width: 100%; display: flex; align-items: center; justify-content: center; gap: 10px;
      padding: 16px; font-size: 1rem; font-weight: 600; background: var(--primary); color: #000;
      border: none; border-radius: 50px; cursor: pointer; transition: transform 0.2s, background 0.2s, box-shadow 0.2s;
    }
    .btn-submit:hover { transform: translateY(-2px); background: var(--primary-dark); box-shadow: 0 10px 20px rgba(0,255,170,0.2); }

    .auth-footer { text-align: center; margin-top: 32px; font-size: 0.9rem; color: var(--muted); }
    .auth-footer a { color: var(--primary); font-weight: 500; }
    
    .gsap-left-rev, .gsap-rev { opacity: 0; transform: translateY(20px); }

    @media (max-width: 992px) {
      .split-layout { flex-direction: column; overflow-y: auto; height: auto; min-height: 100vh; }
      body { overflow-y: auto; }
      .split-left { flex: none; padding: 40px 20px; border-right: none; border-bottom: 1px solid var(--border); min-height: 45vh; align-items: center; text-align: center; }
      .split-right { flex: none; padding: 40px 20px; min-height: 55vh; }
      .sl-headline { font-size: 2.2rem; }
      .sl-float-card { display: none; }
      .sl-content { display: flex; flex-direction: column; align-items: center; }
    }
  </style>
</head>
<body>

<div class="split-layout">
  <!-- Left Cinematic Pane -->
  <div class="split-left" id="slLeft">
    <div class="sl-bg-art" id="slBgArt"></div>
    <div class="sl-content">
      <div class="sl-logo gsap-left-rev"><i class="fa-solid fa-qrcode"></i> <?= htmlspecialchars($sitename) ?></div>
      <h1 class="sl-headline gsap-left-rev">Vanguard of<br><strong>Algorithmic Trading</strong></h1>
      <p class="sl-sub gsap-left-rev">Experience institutional-grade execution speeds and zero-latency arbitrage matrix directly from your terminal.</p>
    </div>
    
    <!-- Floating Data Cards -->
    <div class="sl-float-card gsap-left-rev" id="fCard1">
      <div class="fc-lbl">Network Latency</div>
      <div class="fc-val"><i class="fa fa-bolt"></i> 2.4 ms</div>
    </div>
    <div class="sl-float-card c2 gsap-left-rev" id="fCard2">
      <div class="fc-lbl">Active Matrix Pairs</div>
      <div class="fc-val">1,248</div>
    </div>
  </div>

  <!-- Right Auth Pane -->
  <div class="split-right">
    <div class="auth-wrapper">
      <div class="auth-header gsap-rev">
        <h2>Welcome Back</h2>
        <p>Sign in to your AI trading dashboard</p>
      </div>

      <?php if ($error): ?>
        <div class="alert alert-error gsap-rev"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" action="login.php" class="auth-form">
        <div class="form-group gsap-rev">
          <label>Email Address</label>
          <input type="email" name="email" required autofocus placeholder="you@example.com">
        </div>
        <div class="form-group gsap-rev">
          <label>Password</label>
          <input type="password" name="password" required placeholder="••••••••">
        </div>
        <div class="form-actions gsap-rev">
          <a href="forgot.php">Forgot password?</a>
        </div>
        <div class="gsap-rev">
          <button type="submit" class="btn-submit">Initialize Session <i class="fa fa-arrow-right"></i></button>
        </div>
      </form>

      <div class="auth-footer gsap-rev">
        New to <?= htmlspecialchars($sitename) ?>? <a href="signup.php">Deploy an Account</a>
      </div>
    </div>
  </div>
</div>

<script>
  document.addEventListener("DOMContentLoaded", () => {
    // Reveal Animations
    const tl = gsap.timeline({ defaults: { ease: "power3.out" } });
    
    // Left pane reveal
    tl.to(".gsap-left-rev", { y: 0, opacity: 1, duration: 1, stagger: 0.1, delay: 0.2 })
      // Right pane reveal
      .to(".gsap-rev", { y: 0, opacity: 1, duration: 0.8, stagger: 0.08 }, "-=0.6");

    // Mouse Parallax Effect for Left Pane
    const leftPane = document.getElementById('slLeft');
    const bgArt = document.getElementById('slBgArt');
    const card1 = document.getElementById('fCard1');
    const card2 = document.getElementById('fCard2');

    if (leftPane) {
      leftPane.addEventListener('mousemove', (e) => {
        if (window.innerWidth <= 992) return; // Disable parallax on mobile
        const rect = leftPane.getBoundingClientRect();
        // Calculate mouse position relative to the center of the left pane
        const x = (e.clientX - rect.left - rect.width / 2) / 25;
        const y = (e.clientY - rect.top - rect.height / 2) / 25;

        gsap.to(bgArt, { x: -x, y: -y, duration: 1, ease: "power2.out" });
        gsap.to(card1, { x: x * 1.5, y: y * 1.5, duration: 1, ease: "power2.out" });
        gsap.to(card2, { x: x * 0.8, y: y * 0.8, duration: 1, ease: "power2.out" });
      });
    }
  });
</script>
</body>
</html>
