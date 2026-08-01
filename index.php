<?php
require_once __DIR__ . '/config/config.php';

// Landing page stats from settings
$statManaged   = $settingsRow['stat_managed']   ?? '$28.4M+';
$statBots      = $settingsRow['stat_bots']      ?? '1,240+';
$statWinrate   = $settingsRow['stat_winrate']   ?? '89.4%';
$statCountries = $settingsRow['stat_countries'] ?? '72';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($sitename) ?> — AI-Powered Crypto Trading</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.4/gsap.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.4/ScrollTrigger.min.js"></script>
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
      --green:   #00ffaa;
      --red:     #ff4d4d;
      --radius:  24px;
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html, body {
      margin: 0; padding: 0;
      background: var(--bg);
      color: var(--text);
      font-family: 'Inter', system-ui, sans-serif;
      font-size: 15px;
      line-height: 1.6;
      height: 100vh;
      overflow: hidden; /* Disable body scroll, we scroll the container */
    }
    a { color: inherit; text-decoration: none; }

    /* Snap Scrolling Container */
    .scroll-container {
      height: 100vh;
      overflow-y: auto;
      scroll-snap-type: y mandatory;
      scroll-behavior: smooth;
    }
    .snap-section {
      height: 100vh;
      width: 100%;
      scroll-snap-align: start;
      scroll-snap-stop: always;
      position: relative;
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding: 80px 24px;
      overflow: hidden;
    }
    .container { max-width: 1140px; margin: 0 auto; width: 100%; position: relative; z-index: 2; }
    
    .section-title { font-size: clamp(2rem, 4vw, 3.5rem); font-weight: 300; margin-bottom: 16px; letter-spacing: -1px; }
    .section-title strong { font-weight: 700; color: var(--primary); }
    .section-sub { color: var(--muted); font-size: 1.1rem; margin-bottom: 50px; max-width: 600px; }

    /* Utilities */
    .primary-text { color: var(--primary); }
    .gsap-reveal { opacity: 0; transform: translateY(40px); }
    .btn-primary {
      display: inline-flex; align-items: center; justify-content: center; gap: 10px;
      padding: 14px 32px; font-size: 0.95rem; font-weight: 600;
      background: var(--primary); color: #000;
      border: none; border-radius: 50px; cursor: pointer; transition: transform 0.2s, background 0.2s;
    }
    .btn-primary:hover { transform: translateY(-2px); background: var(--primary-dark); }
    .btn-outline {
      display: inline-flex; align-items: center; justify-content: center; gap: 10px;
      padding: 12px 28px; font-size: 0.95rem; font-weight: 500;
      background: transparent; color: var(--text);
      border: 1px solid var(--border); border-radius: 50px; cursor: pointer; transition: all 0.2s;
    }
    .btn-outline:hover { border-color: var(--primary); color: var(--primary); }

    /* ── Nav ────────────────────────────────────────────────────── */
    nav {
      position: fixed; top: 0; left: 0; right: 0; z-index: 999;
      display: flex; align-items: center; justify-content: space-between;
      padding: 16px 32px; background: rgba(8,11,10,0.88); backdrop-filter: blur(12px); border-bottom: 1px solid var(--border);
    }
    .nav-logo { font-size: 1.15rem; font-weight: 800; color: var(--primary); display: flex; align-items: center; gap: 8px; letter-spacing: 0.05em; }
    .nav-actions { display: flex; gap: 12px; }
    .nav-login { font-size: 0.85rem; font-weight: 500; color: var(--text); padding: 8px 18px; border-radius: 50px; transition: color 0.2s; }
    .nav-login:hover { color: var(--primary); }

    /* ── Screen 1: Hero ─────────────────────────────────────────── */
    .s1-hero { background: radial-gradient(circle at top, #0c1a15 0%, var(--bg) 60%); text-align: center; }
    @keyframes pan-bg-alt { 0% { background-position: 0% bottom; } 50% { background-position: 100% bottom; } 100% { background-position: 0% bottom; } }
    .hero-bg {
      position: absolute; bottom: 0; left: 0; right: 0; height: 50%;
      background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320" preserveAspectRatio="none"><path fill="none" stroke="%2300ffaa" stroke-width="2" stroke-opacity="0.3" d="M0,160L48,186.7C96,213,192,267,288,266.7C384,267,480,213,576,181.3C672,149,768,139,864,165.3C960,192,1056,256,1152,272C1248,288,1344,256,1392,240L1440,224"></path><path fill="none" stroke="%2300ffaa" stroke-width="1.5" stroke-opacity="0.15" d="M0,224L48,213.3C96,203,192,181,288,197.3C384,213,480,267,576,282.7C672,299,768,277,864,250.7C960,224,1056,192,1152,192C1248,192,1344,224,1392,240L1440,256"></path></svg>') no-repeat bottom center;
      background-size: 200% auto; animation: pan-bg-alt 30s ease-in-out infinite; pointer-events: none; z-index: 1;
    }
    .hero-eyebrow {
      display: inline-flex; align-items: center; gap: 8px; font-size: 0.8rem; font-weight: 500; color: var(--muted);
      background: rgba(255,255,255,0.03); border: 1px solid var(--border); padding: 6px 16px; border-radius: 50px; margin-bottom: 24px;
    }
    .hero-h1 { font-size: clamp(2.5rem, 6vw, 4.2rem); font-weight: 300; line-height: 1.15; letter-spacing: -1px; margin-bottom: 24px; }
    .hero-h1 strong { font-weight: 600; }
    .hero-sub { font-size: 1.1rem; color: var(--muted); max-width: 600px; margin: 0 auto 36px; line-height: 1.7; }
    
    .floating-nav {
      position: fixed; top: 0; left: 50%; display: flex; align-items: center;
      background: var(--card); border: 1px solid var(--border); border-radius: 50px; padding: 8px; gap: 16px; z-index: 998; box-shadow: 0 20px 40px rgba(0,0,0,0.4);
    }
    .fnav-logo { width: 40px; height: 40px; background: var(--primary); color: #000; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
    .fnav-links { display: flex; gap: 20px; padding: 0 16px; }
    .fnav-links a { font-size: 0.85rem; color: var(--muted); font-weight: 500; transition: color 0.2s; }
    .fnav-links a:hover, .fnav-links a.active { color: var(--text); }
    .fnav-btn { width: 40px; height: 40px; background: var(--primary); color: #000; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1rem; border: none; cursor: pointer; transition: transform 0.2s; }

    /* ── Screen 2: Market Intel ─────────────────────────────────── */
    .s2-stats { background: var(--bg2); }
    .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 30px; margin-bottom: 60px; text-align: center; }
    .stat-val { font-size: 3rem; font-weight: 300; color: var(--primary); line-height: 1; margin-bottom: 8px; }
    .stat-lbl { font-size: 0.85rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.1em; }
    
    .ticker-wrapper { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); padding: 16px 0; overflow: hidden; position: relative; }
    .ticker-wrapper::before, .ticker-wrapper::after { content:''; position:absolute; top:0; bottom:0; width:100px; z-index:2; pointer-events:none; }
    .ticker-wrapper::before { left:0; background: linear-gradient(to right, var(--card), transparent); }
    .ticker-wrapper::after { right:0; background: linear-gradient(to left, var(--card), transparent); }
    .ticker-inner { display: flex; gap: 40px; white-space: nowrap; animation: ticker-scroll 35s linear infinite; }
    @keyframes ticker-scroll { from { transform: translateX(0); } to { transform: translateX(-50%); } }
    .ticker-item { display: inline-flex; align-items: center; gap: 10px; font-size: 0.9rem; font-weight: 500; }
    .ticker-item.buy { color: var(--green); }
    .ticker-item.sell { color: var(--red); }
    .ticker-item .pair { color: var(--text); font-weight: 600; }

    /* ── Screen 3: Engine ───────────────────────────────────────── */
    .s3-engine { background: var(--bg); }
    .engine-layout { display: grid; grid-template-columns: 1fr 1.2fr; gap: 60px; align-items: center; }
    .step-list { display: flex; flex-direction: column; gap: 30px; }
    .step-item { display: flex; gap: 20px; }
    .step-num { width: 40px; height: 40px; flex-shrink: 0; background: rgba(0,255,170,0.1); color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.9rem; border: 1px solid rgba(0,255,170,0.2); }
    .step-text h4 { font-size: 1.1rem; font-weight: 600; margin-bottom: 6px; }
    .step-text p { font-size: 0.9rem; color: var(--muted); }
    
    .features-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .feature-card { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); padding: 24px; transition: transform 0.3s, border-color 0.3s; }
    .feature-card:hover { transform: translateY(-5px); border-color: rgba(0,255,170,0.3); }
    .fc-icon { font-size: 1.5rem; color: var(--primary); margin-bottom: 16px; }
    .fc-title { font-size: 1rem; font-weight: 600; margin-bottom: 8px; }
    .fc-desc { font-size: 0.85rem; color: var(--muted); }

    /* ── Screen 4: Plans ────────────────────────────────────────── */
    .s4-plans { background: var(--bg2); }
    .plans-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; }
    .plan-card { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); padding: 32px 24px; display: flex; flex-direction: column; transition: transform 0.3s, box-shadow 0.3s; }
    .plan-card:hover { transform: translateY(-8px); box-shadow: 0 20px 40px rgba(0,255,170,0.05); border-color: var(--primary); }
    .plan-card.featured { border-color: rgba(0,255,170,0.4); background: linear-gradient(180deg, rgba(0,255,170,0.05) 0%, transparent 100%); }
    .plan-badge { display: inline-block; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; background: rgba(0,255,170,0.1); color: var(--primary); padding: 4px 12px; border-radius: 50px; margin-bottom: 16px; align-self: flex-start; }
    .plan-name { font-size: 1.2rem; font-weight: 600; margin-bottom: 8px; }
    .plan-roi { font-size: 2.5rem; font-weight: 300; color: var(--primary); margin-bottom: 4px; line-height: 1; }
    .plan-roi span { font-size: 0.9rem; color: var(--muted); font-weight: 500; }
    .plan-desc { font-size: 0.85rem; color: var(--muted); margin-bottom: 24px; padding-bottom: 24px; border-bottom: 1px solid var(--border); }
    .plan-features { list-style: none; margin-bottom: 32px; flex-grow: 1; }
    .plan-features li { font-size: 0.85rem; color: var(--muted); margin-bottom: 12px; display: flex; align-items: center; gap: 10px; }
    .plan-features li i { color: var(--primary); font-size: 0.8rem; }
    
    /* ── Screen 5: Live Feed ────────────────────────────────────── */
    .s5-live { background: radial-gradient(circle at center, #0c1a15 0%, var(--bg) 70%); }
    .live-feed-terminal { max-width: 800px; margin: 0 auto; background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; box-shadow: 0 30px 60px rgba(0,0,0,0.5); }
    .lft-header { display: flex; align-items: center; justify-content: space-between; padding: 16px 24px; background: rgba(255,255,255,0.02); border-bottom: 1px solid var(--border); }
    .lft-title { display: flex; align-items: center; gap: 10px; font-size: 0.9rem; font-weight: 600; color: var(--text); }
    .live-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--green); animation: pulse 1.5s infinite; }
    @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:0.4} }
    .lft-time { font-size: 0.8rem; color: var(--muted); font-family: monospace; }
    .lft-body { padding: 8px 0; min-height: 320px; font-family: monospace; font-size: 0.9rem; }
    .lft-row { display: grid; grid-template-columns: 60px 100px 1fr 80px 80px; align-items: center; gap: 16px; padding: 12px 24px; border-bottom: 1px solid var(--border); }
    .lft-row:last-child { border-bottom: none; }
    .lft-buy { color: var(--green); }
    .lft-sell { color: var(--red); }
    .lft-pair { color: var(--text); font-weight: 600; }
    .lft-price { color: var(--muted); }
    .lft-pct { text-align: right; }
    .lft-status { text-align: right; color: var(--muted); }

    /* ── Screen 6: Trust & Footer ───────────────────────────────── */
    .s6-trust { background: var(--bg2); justify-content: space-between; padding-bottom: 0; }
    .trust-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 60px; margin-bottom: 60px; }
    .testi-card { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); padding: 32px; margin-bottom: 24px; }
    .testi-quote { font-size: 1rem; color: var(--text); margin-bottom: 20px; line-height: 1.6; font-style: italic; }
    .testi-author { display: flex; align-items: center; gap: 16px; }
    .testi-avatar { width: 44px; height: 44px; border-radius: 50%; background: var(--primary); color: #000; display: flex; align-items: center; justify-content: center; font-weight: 700; }
    .testi-info h5 { font-size: 0.95rem; font-weight: 600; }
    .testi-info p { font-size: 0.8rem; color: var(--muted); }
    
    .faq-item { border-bottom: 1px solid var(--border); }
    .faq-q { width: 100%; text-align: left; background: none; border: none; color: var(--text); font-size: 1.05rem; font-weight: 500; padding: 24px 0; cursor: pointer; display: flex; justify-content: space-between; align-items: center; }
    .faq-q:hover { color: var(--primary); }
    .faq-a { font-size: 0.9rem; color: var(--muted); padding-bottom: 24px; line-height: 1.6; display: none; }
    
    .footer-cta { text-align: center; padding: 60px 0; border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); background: radial-gradient(circle at center, rgba(0,255,170,0.05) 0%, transparent 60%); }
    .footer-cta h2 { font-size: 2.5rem; font-weight: 300; margin-bottom: 24px; }
    
    footer { padding: 40px 24px; text-align: center; color: var(--muted); font-size: 0.85rem; }
    .footer-links { display: flex; justify-content: center; gap: 24px; margin-bottom: 16px; }
    .footer-links a:hover { color: var(--primary); }

    @media (max-width: 992px) {
      .snap-section { height: auto; min-height: 100vh; scroll-snap-align: start; padding: 100px 24px; }
      .engine-layout, .trust-layout { grid-template-columns: 1fr; }
      .stats-grid, .plans-grid { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 768px) {
      .stats-grid, .plans-grid, .features-grid { grid-template-columns: 1fr; }
      .floating-nav { width: 90%; flex-wrap: wrap; justify-content: center; border-radius: 24px; }
      .fnav-links { flex-wrap: wrap; justify-content: center; }
      .lft-row { grid-template-columns: 50px 1fr 80px; }
      .lft-price, .lft-status { display: none; }
    }
  </style>
</head>
<body>

<nav>
  <div class="nav-logo"><i class="fa-solid fa-qrcode"></i> <?= htmlspecialchars($sitename) ?></div>
  <div class="nav-actions">
    <a href="user/login.php" class="nav-login">Sign in</a>
    <a href="user/signup.php" class="btn-primary" style="padding: 8px 18px; font-size: 0.85rem;">Get Started</a>
  </div>
</nav>

<main class="scroll-container">

<div class="floating-nav">
  <div class="fnav-logo"><i class="fa-solid fa-qrcode"></i></div>
  <div class="fnav-links">
    <a href="#s2">Market</a>
    <a href="#s3">Features</a>
    <a href="#s4">Pricing</a>
    <a href="#s5">Live Trades</a>
    <a href="#s6">Reviews</a>
  </div>
  <button class="fnav-btn" onclick="document.getElementById('s2').scrollIntoView()"><i class="fa fa-arrow-down"></i></button>
</div>

  <!-- Screen 1: Hero -->
  <section class="snap-section s1-hero" id="s1">
    <div class="hero-bg"></div>
    <div class="container">
      <div class="hero-eyebrow gsap-reveal">⚡ Unrivaled Precision & Speed</div>
      <h1 class="hero-h1 gsap-reveal">The Fastest and Secure<br><strong>AI Trading Assistant.</strong></h1>
      <p class="hero-sub gsap-reveal">Execute trades at superhuman speeds. Maximize your portfolio with our institutional-grade algorithms, active 24/7.</p>
      <a href="user/signup.php" class="btn-primary gsap-reveal">Start Your Free Trial &rarr;</a>
    </div>
    
    </section>

  <!-- Screen 2: Market Intel -->
  <section class="snap-section s2-stats" id="s2">
    <div class="container">
      <div class="section-title gsap-s2">Unrivaled <strong>Market Intelligence</strong></div>
      <div class="section-sub gsap-s2" style="margin: 0 auto 60px;">Numbers that speak for themselves. Our bots process millions of data points per second.</div>
      
      <div class="stats-grid">
        <div class="gsap-s2">
          <div class="stat-val"><?= htmlspecialchars($statManaged) ?></div>
          <div class="stat-lbl">Assets Managed</div>
        </div>
        <div class="gsap-s2">
          <div class="stat-val"><?= htmlspecialchars($statBots) ?></div>
          <div class="stat-lbl">Active Bots</div>
        </div>
        <div class="gsap-s2">
          <div class="stat-val"><?= htmlspecialchars($statWinrate) ?></div>
          <div class="stat-lbl">Avg Win Rate</div>
        </div>
        <div class="gsap-s2">
          <div class="stat-val"><?= htmlspecialchars($statCountries) ?></div>
          <div class="stat-lbl">Countries</div>
        </div>
      </div>

      <div class="ticker-wrapper gsap-s2">
        <div class="ticker-inner" id="tickerInner">
          <!-- Populated by JS -->
          <span class="ticker-item buy"><i class="fa fa-arrow-up"></i><span class="pair">BTC/USDT</span> $64,230.12 <span class="primary-text">+1.2%</span></span>
          <span class="ticker-item sell"><i class="fa fa-arrow-down"></i><span class="pair">ETH/USDT</span> $3,450.88 <span class="primary-text">-0.4%</span></span>
          <span class="ticker-item buy"><i class="fa fa-arrow-up"></i><span class="pair">SOL/USDT</span> $142.50 <span class="primary-text">+5.1%</span></span>
          <span class="ticker-item buy"><i class="fa fa-arrow-up"></i><span class="pair">BNB/USDT</span> $580.20 <span class="primary-text">+0.8%</span></span>
          <span class="ticker-item sell"><i class="fa fa-arrow-down"></i><span class="pair">ADA/USDT</span> $0.45 <span class="primary-text">-1.1%</span></span>
          <!-- Duplicated for scrolling loop -->
          <span class="ticker-item buy"><i class="fa fa-arrow-up"></i><span class="pair">BTC/USDT</span> $64,230.12 <span class="primary-text">+1.2%</span></span>
          <span class="ticker-item sell"><i class="fa fa-arrow-down"></i><span class="pair">ETH/USDT</span> $3,450.88 <span class="primary-text">-0.4%</span></span>
          <span class="ticker-item buy"><i class="fa fa-arrow-up"></i><span class="pair">SOL/USDT</span> $142.50 <span class="primary-text">+5.1%</span></span>
          <span class="ticker-item buy"><i class="fa fa-arrow-up"></i><span class="pair">BNB/USDT</span> $580.20 <span class="primary-text">+0.8%</span></span>
          <span class="ticker-item sell"><i class="fa fa-arrow-down"></i><span class="pair">ADA/USDT</span> $0.45 <span class="primary-text">-1.1%</span></span>
        </div>
      </div>
    </div>
  </section>

  <!-- Screen 3: Engine -->
  <section class="snap-section s3-engine" id="s3">
    <div class="container">
      <div class="engine-layout">
        <div class="gsap-s3-left">
          <div class="section-title" style="text-align: left;">Automated Execution in <strong>4 Steps</strong></div>
          <div class="section-sub" style="text-align: left; margin-bottom: 40px;">From account creation to profit withdrawal, the entire process is streamlined and secure.</div>
          
          <div class="step-list">
            <div class="step-item">
              <div class="step-num">1</div>
              <div class="step-text">
                <h4>Create & Verify</h4>
                <p>Sign up securely. Complete instant KYC to unlock institutional limits.</p>
              </div>
            </div>
            <div class="step-item">
              <div class="step-num">2</div>
              <div class="step-text">
                <h4>Fund Wallet</h4>
                <p>Deposit crypto instantly. We support 8 major networks with zero fees.</p>
              </div>
            </div>
            <div class="step-item">
              <div class="step-num">3</div>
              <div class="step-text">
                <h4>Activate Strategy</h4>
                <p>Select your risk profile and launch an AI bot with one click.</p>
              </div>
            </div>
            <div class="step-item">
              <div class="step-num">4</div>
              <div class="step-text">
                <h4>Withdraw Profits</h4>
                <p>Watch your balance grow and withdraw earnings at any time.</p>
              </div>
            </div>
          </div>
        </div>
        
        <div class="features-grid gsap-s3-right">
          <div class="feature-card">
            <div class="fc-icon"><i class="fa fa-bolt"></i></div>
            <div class="fc-title">Scalper Engine</div>
            <div class="fc-desc">Executes micro-trades in milliseconds capturing tiny price movements.</div>
          </div>
          <div class="feature-card">
            <div class="fc-icon"><i class="fa fa-code-branch"></i></div>
            <div class="fc-title">Arbitrage Matrix</div>
            <div class="fc-desc">Exploits price discrepancies across 15+ integrated exchanges simultaneously.</div>
          </div>
          <div class="feature-card">
            <div class="fc-icon"><i class="fa fa-chart-line"></i></div>
            <div class="fc-title">Trend Follower</div>
            <div class="fc-desc">Rides major market momentum shifts using advanced ML indicators.</div>
          </div>
          <div class="feature-card">
            <div class="fc-icon"><i class="fa fa-microchip"></i></div>
            <div class="fc-title">Quantum Core</div>
            <div class="fc-desc">Our flagship fusion algorithm adapting dynamically to market volatility.</div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Screen 4: Plans -->
  <section class="snap-section s4-plans" id="s4">
    <div class="container">
      <div class="section-title gsap-s4">Choose Your <strong>AI Edge</strong></div>
      <div class="section-sub" style="margin: 0 auto 60px;">Transparent tiers designed for every portfolio size.</div>
      
      <div class="plans-grid">
        <div class="plan-card gsap-s4-card">
          <div class="plan-badge">Scalper</div>
          <div class="plan-name">Starter</div>
          <div class="plan-roi">5% <span>/ day</span></div>
          <div class="plan-desc">Min $50 — Max $500</div>
          <ul class="plan-features">
            <li><i class="fa fa-check"></i> 80+ daily trades</li>
            <li><i class="fa fa-check"></i> Standard priority</li>
            <li><i class="fa fa-check"></i> 1 Day lock</li>
          </ul>
          <a href="user/signup.php" class="btn-outline">Select Plan</a>
        </div>
        
        <div class="plan-card featured gsap-s4-card">
          <div class="plan-badge" style="background: var(--primary); color: #000;">Arbitrage</div>
          <div class="plan-name">Professional</div>
          <div class="plan-roi">8% <span>/ 2 days</span></div>
          <div class="plan-desc">Min $600 — Max $5,000</div>
          <ul class="plan-features">
            <li><i class="fa fa-check"></i> Cross-exchange sync</li>
            <li><i class="fa fa-check"></i> High priority execution</li>
            <li><i class="fa fa-check"></i> 2 Days lock</li>
          </ul>
          <a href="user/signup.php" class="btn-primary">Select Plan</a>
        </div>
        
        <div class="plan-card gsap-s4-card">
          <div class="plan-badge">Trend</div>
          <div class="plan-name">Advanced</div>
          <div class="plan-roi">15% <span>/ 5 days</span></div>
          <div class="plan-desc">Min $5,000 — Max $10k</div>
          <ul class="plan-features">
            <li><i class="fa fa-check"></i> ML momentum engine</li>
            <li><i class="fa fa-check"></i> Risk protection layer</li>
            <li><i class="fa fa-check"></i> 5 Days lock</li>
          </ul>
          <a href="user/signup.php" class="btn-outline">Select Plan</a>
        </div>
        
        <div class="plan-card gsap-s4-card">
          <div class="plan-badge">Quantum</div>
          <div class="plan-name">Institutional</div>
          <div class="plan-roi">25% <span>/ 7 days</span></div>
          <div class="plan-desc">Min $10,000+</div>
          <ul class="plan-features">
            <li><i class="fa fa-check"></i> Multi-strategy fusion</li>
            <li><i class="fa fa-check"></i> VIP account manager</li>
            <li><i class="fa fa-check"></i> 7 Days lock</li>
          </ul>
          <a href="user/signup.php" class="btn-outline">Select Plan</a>
        </div>
      </div>
    </div>
  </section>

  <!-- Screen 5: Live Feed -->
  <section class="snap-section s5-live" id="s5">
    <div class="container">
      <div class="section-title gsap-s5">Witness the <strong>Algorithm</strong></div>
      <div class="section-sub" style="margin: 0 auto 50px;">Real-time execution log from our global server cluster.</div>
      
      <div class="live-feed-terminal gsap-s5">
        <div class="lft-header">
          <div class="lft-title"><span class="live-dot"></span> Live Terminal Connection</div>
          <div class="lft-time" id="feedTs">Initializing...</div>
        </div>
        <div class="lft-body" id="landingFeed">
          <div style="padding: 40px; text-align: center; color: var(--muted);">Awaiting socket connection...</div>
        </div>
      </div>
    </div>
  </section>

  <!-- Screen 6: Trust & Footer -->
  <section class="snap-section s6-trust" id="s6">
    <div class="container" style="padding-top: 60px;">
      <div class="trust-layout">
        <div class="gsap-s6">
          <div class="section-title" style="text-align: left;">Trusted by <strong>Thousands</strong></div>
          <div class="section-sub" style="text-align: left; margin-bottom: 40px;">Don't just take our word for it.</div>
          
          <div class="testi-card">
            <div class="testi-quote">"The Arbitrage bot executes faster than any script I've written. I've been running the Professional plan for 2 months with zero downtime."</div>
            <div class="testi-author">
              <div class="testi-avatar">M</div>
              <div class="testi-info"><h5>Marcus K.</h5><p>Algorithmic Trader, Germany</p></div>
            </div>
          </div>
          <div class="testi-card">
            <div class="testi-quote">"Clean interface, fast withdrawals. The live terminal proves they are actually executing trades, unlike most platforms."</div>
            <div class="testi-author">
              <div class="testi-avatar">S</div>
              <div class="testi-info"><h5>Sarah A.</h5><p>Crypto Investor, UAE</p></div>
            </div>
          </div>
        </div>
        
        <div class="gsap-s6" style="padding-top: 20px;">
          <h3 style="font-size: 1.5rem; margin-bottom: 24px; font-weight: 300;">Frequently Asked <strong style="font-weight: 700; color: var(--primary);">Questions</strong></h3>
          <div class="faq-list">
            <div class="faq-item">
              <button class="faq-q" onclick="this.nextElementSibling.style.display = this.nextElementSibling.style.display === 'block' ? 'none' : 'block'">How are profits generated? <i class="fa fa-plus"></i></button>
              <div class="faq-a">Our bots exploit micro-inefficiencies in the market—such as price differences between exchanges (arbitrage) and rapid momentum shifts (scalping).</div>
            </div>
            <div class="faq-item">
              <button class="faq-q" onclick="this.nextElementSibling.style.display = this.nextElementSibling.style.display === 'block' ? 'none' : 'block'">Is my capital secure? <i class="fa fa-plus"></i></button>
              <div class="faq-a">Yes. We utilize institutional-grade cold storage for idle funds and only allocate required capital to exchange hot wallets via restricted API keys.</div>
            </div>
            <div class="faq-item">
              <button class="faq-q" onclick="this.nextElementSibling.style.display = this.nextElementSibling.style.display === 'block' ? 'none' : 'block'">When can I withdraw? <i class="fa fa-plus"></i></button>
              <div class="faq-a">Profits and capital are returned to your main balance upon plan completion (1 to 7 days). From there, withdrawals to your external wallet are instant.</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Screen 7: CTA & Footer -->
  <section class="snap-section s7-cta" id="s7" style="justify-content: flex-end; padding-bottom: 0;">
    <div class="container" style="display: flex; flex-direction: column; height: 100%; justify-content: center;">
      <div class="footer-cta gsap-s7">
        <h2 style="font-size: 3rem; margin-bottom: 30px; font-weight: 300;">Ready to Automate Your Wealth?</h2>
        <a href="user/signup.php" class="btn-primary" style="font-size: 1.1rem; padding: 18px 48px;">Create Free Account</a>
      </div>
    </div>
    
    <footer style="margin-top: auto; padding: 40px 24px; text-align: center; color: var(--muted); border-top: 1px solid var(--border); background: var(--bg2);">
      <div class="footer-links" style="display: flex; justify-content: center; gap: 30px; margin-bottom: 20px;">
        <a href="#" style="transition: color 0.2s;">Terms</a>
        <a href="#" style="transition: color 0.2s;">Privacy</a>
        <a href="#" style="transition: color 0.2s;">KYC Policy</a>
        <a href="#" style="transition: color 0.2s;">Support</a>
      </div>
      <div>&copy; <?= date('Y') ?> <?= htmlspecialchars($sitename) ?>. All rights reserved.</div>
    </footer>
  </section>

</main>

<script>
// Register ScrollTrigger
gsap.registerPlugin(ScrollTrigger);

// Make the scroll container the scroller for ScrollTrigger
const scroller = ".scroll-container";

document.addEventListener("DOMContentLoaded", () => {
  // ── Hero Initial Animation (Screen 1) ──────────────────────
  const fNav = document.querySelector(".floating-nav");
  // Calculate dynamic Y positions
  const bottomY = window.innerHeight - fNav.offsetHeight - 40;
  const topY = 80;

  gsap.set(fNav, { xPercent: -50, y: bottomY + 100, opacity: 0 });
  const tlHero = gsap.timeline({ defaults: { ease: "power3.out" } });
  tlHero.to(".gsap-reveal", { y: 0, opacity: 1, duration: 1, stagger: 0.15, delay: 0.2 })
        .to(fNav, { y: bottomY, opacity: 1, duration: 1.2, ease: "elastic.out(1, 0.7)" }, "-=0.5");
  gsap.to(".fnav-btn", { scale: 1.08, boxShadow: "0 0 15px rgba(0, 255, 170, 0.4)", duration: 1.5, repeat: -1, yoyo: true, ease: "sine.inOut" });

  // Mouse Parallax for Hero
  const heroSection = document.getElementById("s1");
  heroSection.addEventListener("mousemove", (e) => {
    const x = (window.innerWidth / 2 - e.pageX) / 40;
    const y = (window.innerHeight / 2 - e.pageY) / 40;
    gsap.to(".hero-bg", { x: -x, y: -y, duration: 1, ease: "power2.out" });
    // Update nav parallax based on its current animated y position
    const isSticky = fNav.classList.contains("is-sticky");
    gsap.to(fNav, { x: x / 2, duration: 1, ease: "power2.out" });
  });

  // ── ScrollTrigger Animations per Screen ────────────────────
  
  // Setup default starting states
  gsap.set(".gsap-s2, .gsap-s3-left, .gsap-s3-right .feature-card, .gsap-s4, .gsap-s4-card, .gsap-s5, .gsap-s6, .gsap-s7", { y: 40, opacity: 0 });

  // Mini Nav Sticky Logic & Scroll Spy
  ScrollTrigger.create({
    trigger: "#s2",
    scroller: scroller,
    start: "top 80%",
    onEnter: () => {
      fNav.classList.add("is-sticky");
      gsap.to(fNav, { y: topY, duration: 0.8, ease: "power3.out" });
    },
    onLeaveBack: () => {
      fNav.classList.remove("is-sticky");
      gsap.to(fNav, { y: bottomY, duration: 0.8, ease: "power3.out" });
    }
  });

  const navLinks = document.querySelectorAll('.fnav-links a');
  const sections = ['#s2', '#s3', '#s4', '#s5', '#s6'];
  sections.forEach((sec) => {
    ScrollTrigger.create({
      trigger: sec,
      scroller: scroller,
      start: "top 50%",
      end: "bottom 50%",
      onToggle: (self) => {
        if(self.isActive) {
          navLinks.forEach(l => l.classList.remove('active'));
          const activeLink = document.querySelector(`.fnav-links a[href="${sec}"]`);
          if(activeLink) activeLink.classList.add('active');
        }
      }
    });
  });

  // Screen 2: Stats
  ScrollTrigger.create({
    trigger: "#s2", scroller: scroller, start: "top 60%",
    onEnter: () => gsap.to(".gsap-s2", { y: 0, opacity: 1, duration: 0.8, stagger: 0.1, ease: "power3.out" })
  });

  // Screen 3: Engine
  ScrollTrigger.create({
    trigger: "#s3", scroller: scroller, start: "top 60%",
    onEnter: () => {
      gsap.to(".gsap-s3-left", { y: 0, opacity: 1, duration: 0.8, ease: "power3.out" });
      gsap.to(".gsap-s3-right .feature-card", { y: 0, opacity: 1, duration: 0.8, stagger: 0.1, delay: 0.2, ease: "power3.out" });
    }
  });

  // Screen 4: Plans
  ScrollTrigger.create({
    trigger: "#s4", scroller: scroller, start: "top 60%",
    onEnter: () => {
      gsap.to(".gsap-s4", { y: 0, opacity: 1, duration: 0.8, ease: "power3.out" });
      gsap.to(".gsap-s4-card", { y: 0, opacity: 1, duration: 0.8, stagger: 0.1, delay: 0.2, ease: "power3.out" });
    }
  });

  // Screen 5: Live Feed
  ScrollTrigger.create({
    trigger: "#s5", scroller: scroller, start: "top 60%",
    onEnter: () => gsap.to(".gsap-s5", { y: 0, opacity: 1, duration: 0.8, stagger: 0.2, ease: "power3.out" })
  });

  // Screen 6: Trust
  ScrollTrigger.create({
    trigger: "#s6", scroller: scroller, start: "top 60%",
    onEnter: () => gsap.to(".gsap-s6", { y: 0, opacity: 1, duration: 0.8, stagger: 0.2, ease: "power3.out" })
  });

  // Screen 7: CTA
  ScrollTrigger.create({
    trigger: "#s7", scroller: scroller, start: "top 60%",
    onEnter: () => gsap.to(".gsap-s7", { y: 0, opacity: 1, duration: 0.8, ease: "power3.out" })
  });
});

// ── Live Feed Logic ─────────────────────────────────────────────
function loadLandingFeed() {
  fetch('api/ai_feed.php')
    .then(r => r.json())
    .then(data => {
      const trades = data.trades || [];
      if (!trades.length) return;
      const el = document.getElementById('landingFeed');
      const ts = document.getElementById('feedTs');
      const now = new Date();
      ts.textContent = now.toISOString().replace('T', ' ').substring(0, 19) + ' UTC';
      
      el.innerHTML = trades.slice(0, 6).map(t => {
        const isBuy = t.action === 'BUY';
        const pct   = t.profit_pct ? (isBuy ? '+' : '-') + t.profit_pct + '%' : '—';
        const price = Number(t.entry_price).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        return `<div class="lft-row">
          <span class="${isBuy ? 'lft-buy' : 'lft-sell'}">[${t.action}]</span>
          <span class="lft-pair">${t.pair}</span>
          <span class="lft-price">EXEC @ $${price}</span>
          <span class="lft-pct" style="color:${isBuy ? 'var(--green)' : 'var(--red)'}">${pct}</span>
          <span class="lft-status">${t.status.toUpperCase()}</span>
        </div>`;
      }).join('');
    })
    .catch(() => {});
}

loadLandingFeed();
setInterval(loadLandingFeed, 10000); // Faster update for "terminal" feel
</script>
</body>
</html>
