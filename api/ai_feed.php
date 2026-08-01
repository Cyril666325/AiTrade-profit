<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache');

require_once __DIR__ . '/../config/db.php';

$limit = min((int)($_GET['limit'] ?? 20), 50);

$stmt = $conn->prepare(
    "SELECT pair, action, entry_price, exit_price, profit_pct, status, bot_type, created_at
     FROM ai_trades ORDER BY created_at DESC LIMIT ?"
);
$stmt->bind_param('i', $limit);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Format for display
$trades = array_map(function($r) {
    return [
        'pair'        => $r['pair'],
        'action'      => $r['action'],
        'entry_price' => number_format((float)$r['entry_price'], 2),
        'exit_price'  => $r['exit_price'] !== null ? number_format((float)$r['exit_price'], 2) : null,
        'profit_pct'  => $r['profit_pct'] !== null ? round((float)$r['profit_pct'], 2) : null,
        'status'      => $r['status'],
        'bot_type'    => $r['bot_type'],
        'time'        => date('H:i', strtotime($r['created_at'])),
        'date'        => date('d M', strtotime($r['created_at'])),
    ];
}, $rows);

echo json_encode(['success' => true, 'trades' => $trades]);
