<?php
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['USERLOGIN'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthenticated']);
    exit;
}

$userLogin = $_SESSION['USERLOGIN'];

// Get user id from refcode
$us = $conn->prepare("SELECT id FROM users WHERE refcode = ? LIMIT 1");
$us->bind_param('s', $userLogin);
$us->execute();
$us->bind_result($uid);
$us->fetch();
$us->close();

if (!$uid) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

// Get active investment + bot settings
$stmt = $conn->prepare(
    "SELECT i.invest_amt, i.plan_name, i.bot_type, i.total_profit, i.start_date, i.end_date,
            b.bot_name, b.win_rate, b.accuracy, b.uptime_pct, b.trades_per_day
     FROM investments i
     LEFT JOIN bot_settings b ON b.plan_id = i.planId
     WHERE i.userId = ? AND i.status = 'running'
     ORDER BY i.id DESC LIMIT 1"
);
$stmt->bind_param('i', $uid);
$stmt->execute();
$inv = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$inv) {
    echo json_encode(['success' => true, 'active' => false]);
    exit;
}

// Calculate progress
$start   = strtotime($inv['start_date']);
$end     = strtotime($inv['end_date']);
$now     = time();
$total   = max(1, $end - $start);
$elapsed = max(0, $now - $start);
$progress = min(100, round(($elapsed / $total) * 100, 1));

echo json_encode([
    'success'        => true,
    'active'         => true,
    'bot_name'       => $inv['bot_name'] ?? 'AI Bot',
    'plan_name'      => $inv['plan_name'],
    'bot_type'       => $inv['bot_type'],
    'invest_amt'     => number_format((float)$inv['invest_amt'], 2),
    'total_profit'   => number_format((float)$inv['total_profit'], 2),
    'win_rate'       => $inv['win_rate'],
    'accuracy'       => $inv['accuracy'],
    'uptime_pct'     => $inv['uptime_pct'],
    'trades_per_day' => $inv['trades_per_day'],
    'progress'       => $progress,
    'end_date'       => date('d M Y', $end),
]);
