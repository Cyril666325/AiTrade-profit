<?php
/**
 * Cron Job — run every hour (or daily):
 * 1. Mature completed investments → credit profit + balance, send email
 * 2. Auto-generate AI trade entries (if ai_autogen = 1)
 * 3. Prune ai_trades to last 500 rows
 *
 * Crontab example:
 *   0 * * * * php /path/to/AiTrading/cron.php >> /dev/null 2>&1
 */

require_once __DIR__ . '/config/config.php';

$now = date('Y-m-d H:i:s');

// ── 1. Mature investments ──────────────────────────────────────────────────
$inv = $conn->query("SELECT * FROM investments WHERE status = 'running'");
while ($row = $inv->fetch_assoc()) {
    if (strtotime($now) < strtotime($row['end_date'])) continue;

    $userId      = (int)$row['userId'];
    $investAmt   = (float)$row['invest_amt'];
    $totalProfit = (float)$row['total_profit'];
    $returnAmt   = $investAmt + $totalProfit;
    $investId    = (int)$row['id'];

    // Credit profit wallet and return principal + profit to balance
    $upd = $conn->prepare(
        "UPDATE users SET profit = profit + ?, balance = balance + ? WHERE id = ?"
    );
    $upd->bind_param('ddi', $totalProfit, $returnAmt, $userId);
    $upd->execute();
    $upd->close();

    $done = $conn->prepare("UPDATE investments SET status = 'completed' WHERE id = ?");
    $done->bind_param('i', $investId);
    $done->execute();
    $done->close();

    // Get user email for notification
    $uq = $conn->prepare("SELECT fname, email FROM users WHERE id = ? LIMIT 1");
    $uq->bind_param('i', $userId);
    $uq->execute();
    $uq->bind_result($fname, $email);
    $uq->fetch();
    $uq->close();

    if ($email) {
        $body = "<p>Hi <strong>$fname</strong>,</p>
                 <p>Your investment has matured and your returns have been credited!</p>
                 <ul>
                   <li><strong>Plan:</strong> {$row['plan_name']}</li>
                   <li><strong>Amount Invested:</strong> \${$row['invest_amt']}</li>
                   <li><strong>Profit Earned:</strong> \$$totalProfit</li>
                   <li><strong>Total Returned:</strong> \$$returnAmt</li>
                 </ul>
                 <p>Log in to your dashboard to transfer your profits to your balance and withdraw.</p>";
        sendMail($email, "Investment Matured — $sitename", $body);
    }
}

// ── 2. Auto-generate AI trades (if enabled) ────────────────────────────────
$autogen = (int)($settingsRow['ai_autogen'] ?? 1);
if ($autogen) {
    $pairs    = ['BTC/USDT','ETH/USDT','SOL/USDT','BNB/USDT','XRP/USDT','ADA/USDT','MATIC/USDT','DOGE/USDT'];
    $botTypes = ['scalper','arbitrage','trend','quantum'];
    $count    = rand(2, 8);

    // Fetch live prices for realism
    $prices = [];
    $priceData = @file_get_contents('https://api.oxapay.com/v1/common/prices');
    if ($priceData) {
        $pJson = json_decode($priceData, true);
        if (isset($pJson['data'])) $prices = $pJson['data'];
    }

    $fallback = [
        'BTC' => 67000, 'ETH' => 3500, 'SOL' => 180,
        'BNB' => 600,   'XRP' => 0.60, 'ADA' => 0.45,
        'MATIC' => 0.80, 'DOGE' => 0.15
    ];

    for ($i = 0; $i < $count; $i++) {
        $pair     = $pairs[array_rand($pairs)];
        $coinCode = explode('/', $pair)[0];
        $action   = rand(0, 1) ? 'BUY' : 'SELL';
        $botType  = $botTypes[array_rand($botTypes)];

        $basePrice = (float)($prices[$coinCode] ?? $fallback[$coinCode] ?? 100);
        // Jitter ±1.5%
        $jitter     = $basePrice * (rand(-150, 150) / 10000);
        $entryPrice = round($basePrice + $jitter, 4);

        // 80% chance the trade is already closed with a profit
        if (rand(1, 10) <= 8) {
            $profitPct  = round(rand(30, 450) / 100, 2); // 0.30% – 4.50%
            $direction  = $action === 'BUY' ? 1 : -1;
            $exitPrice  = round($entryPrice * (1 + $direction * $profitPct / 100), 4);
            $status     = 'closed';
        } else {
            $exitPrice = null;
            $profitPct = null;
            $status    = 'open';
        }

        $ins = $conn->prepare(
            "INSERT INTO ai_trades (pair, action, entry_price, exit_price, profit_pct, status, bot_type)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $ins->bind_param('ssdddss', $pair, $action, $entryPrice, $exitPrice, $profitPct, $status, $botType);
        $ins->execute();
        $ins->close();
    }
}

// ── 3. Prune ai_trades (keep last 500) ────────────────────────────────────
$conn->query(
    "DELETE FROM ai_trades WHERE id NOT IN (
       SELECT id FROM (SELECT id FROM ai_trades ORDER BY created_at DESC LIMIT 500) t
     )"
);

echo "Cron completed at $now\n";
