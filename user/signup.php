<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/config.php';

// Already logged in
if (isset($_SESSION['USERLOGIN'])) {
    header('Location: index.php');
    exit;
}

$ref   = text_input($_GET['ref'] ?? '');
$error = $msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = text_input($_POST['email']    ?? '');
    $fname    = text_input($_POST['fname']    ?? '');
    $lname    = text_input($_POST['lname']    ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm']  ?? '';
    $phone    = text_input($_POST['phone']    ?? '');
    $country  = text_input($_POST['country']  ?? '');
    $address  = text_input($_POST['address']  ?? '');
    $gender   = text_input($_POST['gender']   ?? '');
    $referral = text_input($_POST['referral'] ?? '');

    // Validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (!preg_match('/^[a-zA-Z\-\' ]+$/', $fname) || !preg_match('/^[a-zA-Z\-\' ]+$/', $lname)) {
        $error = 'Name must contain letters only.';
    } elseif (!preg_match('/^\+?[0-9]{7,15}$/', $phone)) {
        $error = 'Invalid phone number.';
    } elseif (empty($country) || empty($address) || empty($gender)) {
        $error = 'All fields are required.';
    } else {
        // Check duplicate email
        $chk = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $chk->bind_param('s', $email);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows > 0) {
            $error = 'Email is already registered.';
        }
        $chk->close();
    }

    if (empty($error)) {
        $refcode   = generateRefCode();
        $hashed    = hashPassword($password);
        $stmt = $conn->prepare(
            "INSERT INTO users (email, fname, lname, password, phone, country, address, gender, refcode, referral)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('ssssssssss',
            $email, $fname, $lname, $hashed, $phone,
            $country, $address, $gender, $refcode, $referral
        );
        if ($stmt->execute()) {
            $newUserId = $conn->insert_id;
            // Generate 6-digit OTP
            $otp     = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $expires = date('Y-m-d H:i:s', strtotime('+30 minutes'));
            $otpUpd  = $conn->prepare("UPDATE users SET email_otp = ?, otp_expires = ? WHERE id = ?");
            $otpUpd->bind_param('ssi', $otp, $expires, $newUserId);
            $otpUpd->execute();
            $otpUpd->close();

            $body = "<p>Hi <strong>$fname</strong>,</p>
                     <p>Welcome to <strong>$sitename</strong>! Your account has been created.</p>
                     <p>Please verify your email using the code below:</p>
                     <p style='font-size:36px;font-weight:700;letter-spacing:8px;color:#c9a84c;text-align:center'>$otp</p>
                     <p style='color:#888'>This code expires in 30 minutes.</p>
                     <p>Your referral code: <strong style='font-size:22px;letter-spacing:2px;color:#c9a84c'>$refcode</strong></p>
                     <p>Share it and earn " . number_format($ref_bonus, 0) . "% commission on every deposit your referrals make.</p>";
            sendMail($email, "Verify your email — $sitename", $body);
            $msg = 'Registration successful! Check your email for a verification code.';
        } else {
            $error = 'Registration failed. Please try again.';
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
  <title><?= htmlspecialchars($sitename) ?> — Sign Up</title>
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
      --success: #00ffaa;
      --radius:  24px;
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html, body {
      margin: 0; padding: 0;
      background: var(--bg);
      color: var(--text);
      font-family: 'Inter', system-ui, sans-serif;
      height: 100vh;
      overflow: hidden; /* Desktop body doesn't scroll, right pane does */
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
      align-items: flex-start; /* Good for scrolling */
      padding: 60px 40px;
      position: relative;
      overflow-y: auto;
    }
    .auth-wrapper { width: 100%; max-width: 480px; margin: auto; position: relative; z-index: 2; }
    .auth-header { margin-bottom: 40px; }
    .auth-header h2 { font-size: 2rem; font-weight: 300; margin-bottom: 8px; }
    .auth-header p { color: var(--muted); font-size: 0.95rem; }

    .alert { padding: 12px 16px; border-radius: 8px; font-size: 0.9rem; margin-bottom: 24px; }
    .alert-error { background: rgba(255,77,77,0.1); color: var(--error); border: 1px solid rgba(255,77,77,0.2); }
    .alert-success { background: rgba(0,255,170,0.1); color: var(--success); border: 1px solid rgba(0,255,170,0.2); }

    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-size: 0.75rem; font-weight: 600; color: var(--muted); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.05em; }
    .form-group input, .form-group select {
      width: 100%; background: var(--card); border: 1px solid var(--border); border-radius: 8px;
      color: var(--text); font-size: 0.95rem; padding: 14px 16px; outline: none; transition: border-color 0.2s, box-shadow 0.2s;
      appearance: none;
    }
    .form-group select { background-image: url('data:image/svg+xml;utf8,<svg fill="%238a9a95" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5z"/></svg>'); background-repeat: no-repeat; background-position: right 12px center; background-size: 20px; }
    .form-group input:focus, .form-group select:focus { border-color: var(--primary); box-shadow: 0 0 0 2px rgba(0,255,170,0.1); }
    
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

    .btn-submit {
      width: 100%; display: flex; align-items: center; justify-content: center; gap: 10px;
      padding: 16px; font-size: 1rem; font-weight: 600; background: var(--primary); color: #000;
      border: none; border-radius: 50px; cursor: pointer; transition: transform 0.2s, background 0.2s, box-shadow 0.2s;
      margin-top: 10px;
    }
    .btn-submit:hover { transform: translateY(-2px); background: var(--primary-dark); box-shadow: 0 10px 20px rgba(0,255,170,0.2); }

    .auth-footer { text-align: center; margin-top: 32px; font-size: 0.9rem; color: var(--muted); }
    .auth-footer a { color: var(--primary); font-weight: 500; }
    
    .gsap-left-rev, .gsap-rev { opacity: 0; transform: translateY(20px); }

    @media (max-width: 992px) {
      .split-layout { flex-direction: column; overflow-y: auto; height: auto; min-height: 100vh; }
      body { overflow-y: auto; }
      .split-left { flex: none; padding: 40px 20px; border-right: none; border-bottom: 1px solid var(--border); min-height: 45vh; align-items: center; text-align: center; }
      .split-right { flex: none; padding: 40px 20px; min-height: 55vh; overflow-y: visible; }
      .sl-headline { font-size: 2.2rem; }
      .sl-float-card { display: none; }
      .sl-content { display: flex; flex-direction: column; align-items: center; }
      .form-row { grid-template-columns: 1fr; gap: 0; }
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
      <h1 class="sl-headline gsap-left-rev">Deploy Your<br><strong>Trading Edge</strong></h1>
      <p class="sl-sub gsap-left-rev">Join the elite network of algorithmic traders utilizing our institutional-grade infrastructure.</p>
    </div>
    
    <!-- Floating Data Cards -->
    <div class="sl-float-card gsap-left-rev" id="fCard1">
      <div class="fc-lbl">Execution Speed</div>
      <div class="fc-val"><i class="fa fa-tachometer-alt"></i> Sub-ms</div>
    </div>
    <div class="sl-float-card c2 gsap-left-rev" id="fCard2">
      <div class="fc-lbl">Assets Protected</div>
      <div class="fc-val"><i class="fa fa-shield-alt"></i> Cold Storage</div>
    </div>
  </div>

  <!-- Right Auth Pane -->
  <div class="split-right">
    <div class="auth-wrapper">
      <div class="auth-header gsap-rev">
        <h2>Create Account</h2>
        <p>Join the AI-powered trading platform</p>
      </div>

      <?php if ($error): ?>
        <div class="alert alert-error gsap-rev"><?= htmlspecialchars($error) ?></div>
      <?php elseif ($msg): ?>
        <div class="alert alert-success gsap-rev"><?= htmlspecialchars($msg) ?>
          <br><br><a href="login.php" style="color:var(--bg);background:var(--primary);padding:8px 16px;border-radius:4px;font-weight:600;display:inline-block;">Login →</a>
        </div>
      <?php endif; ?>

      <?php if (!$msg): ?>
      <form method="POST" action="signup.php" class="auth-form">
        <input type="hidden" name="referral" value="<?= htmlspecialchars($ref) ?>">

        <div class="form-row">
          <div class="form-group gsap-rev">
            <label>First Name</label>
            <input type="text" name="fname" required placeholder="First name">
          </div>
          <div class="form-group gsap-rev">
            <label>Last Name</label>
            <input type="text" name="lname" required placeholder="Last name">
          </div>
        </div>

        <div class="form-group gsap-rev">
          <label>Email Address</label>
          <input type="email" name="email" required placeholder="you@example.com">
        </div>

        <div class="form-group gsap-rev">
          <label>Country</label>
          <select name="country" required>
            <option value="">Select country</option>
            <option>United States of America</option>
            <option>United Kingdom</option>
            <option>Canada</option>
            <option>Australia</option>
            <option>Nigeria</option>
            <option>Ghana</option>
            <option>South Africa</option>
            <option>Germany</option>
            <option>France</option>
            <option>India</option>
            <option>China</option>
            <option>Brazil</option>
            <option>Mexico</option>
            <option>Other</option>
          </select>
        </div>

        <div class="form-group gsap-rev">
          <label>Address</label>
          <input type="text" name="address" required placeholder="Your residential address">
        </div>

        <div class="form-row">
          <div class="form-group gsap-rev">
            <label>Phone</label>
            <input type="tel" name="phone" required placeholder="+1234567890">
          </div>
          <div class="form-group gsap-rev">
            <label>Gender</label>
            <select name="gender" required>
              <option value="">Select</option>
              <option value="male">Male</option>
              <option value="female">Female</option>
              <option value="other">Other</option>
            </select>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group gsap-rev">
            <label>Password</label>
            <input type="password" name="password" required placeholder="Min 8 characters">
          </div>
          <div class="form-group gsap-rev">
            <label>Confirm Password</label>
            <input type="password" name="confirm" required placeholder="Repeat password">
          </div>
        </div>

        <div class="gsap-rev">
          <button type="submit" class="btn-submit">Initialize Account <i class="fa fa-arrow-right"></i></button>
        </div>
      </form>
      <?php endif; ?>

      <div class="auth-footer gsap-rev">
        Already have an account? <a href="login.php">Sign in</a>
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
      .to(".gsap-rev", { y: 0, opacity: 1, duration: 0.6, stagger: 0.05 }, "-=0.6");

    // Mouse Parallax Effect for Left Pane
    const leftPane = document.getElementById('slLeft');
    const bgArt = document.getElementById('slBgArt');
    const card1 = document.getElementById('fCard1');
    const card2 = document.getElementById('fCard2');

    if (leftPane) {
      leftPane.addEventListener('mousemove', (e) => {
        if (window.innerWidth <= 992) return; // Disable parallax on mobile
        const rect = leftPane.getBoundingClientRect();
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
