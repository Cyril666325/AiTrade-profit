<?php
// ── Input sanitization ─────────────────────────────────────────────────────
function text_input(string $data): string {
    return htmlspecialchars(stripslashes(trim($data)), ENT_QUOTES, 'UTF-8');
}

// ── Password helpers ───────────────────────────────────────────────────────
function hashPassword(string $plain): string {
    return password_hash($plain, PASSWORD_BCRYPT, ['cost' => 12]);
}

function verifyPassword(string $plain, string $hash): bool {
    return password_verify($plain, $hash);
}

// ── Session auth ───────────────────────────────────────────────────────────
function loginSuccessRedirect(string $refcode, int $userId, string $route = 'index.php'): void {
    session_regenerate_id(true);
    $_SESSION['USERLOGIN'] = $refcode;
    $_SESSION['userid']    = $userId;
    header("Location: $route");
    exit;
}

// ── Random strings ─────────────────────────────────────────────────────────
function getRandomString(int $length = 30): string {
    $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    return substr(str_shuffle(str_repeat($chars, (int)ceil($length / strlen($chars)))), 0, $length);
}

function generateRefCode(): string {
    return strtoupper(substr(md5(uniqid('', true)), 0, 8));
}

function generateTranxId(string $prefix = 'DEP'): string {
    return $prefix . strtoupper(bin2hex(random_bytes(6)));
}

// ── Crypto price (server-side fetch via OxaPay) ────────────────────────────
function getCryptoPrice(string $code): float {
    $url = 'https://api.oxapay.com/v1/common/prices';
    $ctx = stream_context_create(['http' => ['timeout' => 5]]);
    $raw = @file_get_contents($url, false, $ctx);
    if (!$raw) return 0.0;
    $data = json_decode($raw, true);
    $key  = strtoupper($code);
    return (float)($data['data'][$key] ?? 0);
}

// Convert USD amount to BTC equivalent (for display)
function usdToBtc(float $usd): float {
    $price = getCryptoPrice('BTC');
    return $price > 0 ? $usd / $price : 0.0;
}

// Legacy alias used in old codebase
function getCryptoValueByShortCode(string $code, float $usd): float {
    $price = getCryptoPrice($code);
    return $price > 0 ? $usd / $price : 0.0;
}

// ── Email ──────────────────────────────────────────────────────────────────
function sendMail(string $to, string $subject, string $htmlBody): bool {
    global $sitename, $siteurl, $sitemail;

    require_once __DIR__ . '/../mailer/PHPMailer.php';
    require_once __DIR__ . '/../mailer/SMTP.php';
    require_once __DIR__ . '/../mailer/Exception.php';

    $mail = new PHPMailer\PHPMailer\PHPMailer();
    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->Port       = MAIL_PORT;
        $mail->SMTPSecure = MAIL_SECURE;
        $mail->isHTML(true);
        $mail->setFrom(MAIL_USERNAME, $sitename);
        $mail->addAddress($to);
        $mail->addReplyTo(MAIL_USERNAME, $sitename);
        $mail->Subject = $subject;
        $mail->MsgHTML(emailTemplate($subject, $htmlBody));
        return $mail->send();
    } catch (\Exception $e) {
        return false;
    }
}

function emailTemplate(string $subject, string $body): string {
    global $sitename, $siteurl;
    return '<!DOCTYPE html><html><head><meta charset="UTF-8">
    <style>
      body{font-family:Arial,sans-serif;background:#0d1117;margin:0;padding:0;}
      .wrap{max-width:600px;margin:20px auto;background:#161b22;border-radius:10px;overflow:hidden;}
      .hdr{background:linear-gradient(135deg,#0d1117,#1c2b45);padding:24px;text-align:center;color:#c9a84c;font-size:22px;font-weight:700;letter-spacing:1px;}
      .body{padding:28px;font-size:15px;color:#c9d1d9;line-height:1.7;}
      .btn{display:inline-block;background:linear-gradient(135deg,#c9a84c,#a07830);color:#0d1117;padding:12px 28px;border-radius:7px;text-decoration:none;font-weight:700;margin-top:18px;}
      .ftr{background:#0d1117;color:#484f58;text-align:center;padding:14px;font-size:13px;}
      .ftr a{color:#c9a84c;text-decoration:none;}
    </style></head><body>
    <div class="wrap">
      <div class="hdr">' . htmlspecialchars($sitename) . '</div>
      <div class="body">' . $body . '
        <p style="text-align:center"><a href="' . htmlspecialchars($siteurl) . '" class="btn">Visit Platform</a></p>
      </div>
      <div class="ftr">&copy; ' . date('Y') . ' <a href="' . htmlspecialchars($siteurl) . '">' . htmlspecialchars($sitename) . '</a></div>
    </div></body></html>';
}
