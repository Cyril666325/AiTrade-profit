<?php
require_once __DIR__ . '/session.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

if ($is_demo) {
    echo json_encode(['success' => false, 'message' => 'This action is disabled in the demo account.']);
    exit;
}

$amount = floatval($_POST['amount'] ?? 0);

if ($amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Enter a valid amount.']);
    exit;
}

if ($amount > $profit) {
    echo json_encode(['success' => false, 'message' => 'Amount exceeds available profit.']);
    exit;
}

// Atomic transfer with transaction
$conn->begin_transaction();
try {
    $upd = $conn->prepare(
        "UPDATE users SET profit = profit - ?, balance = balance + ? WHERE id = ? AND profit >= ?"
    );
    $upd->bind_param('ddid', $amount, $amount, $userId, $amount);
    $upd->execute();
    if ($upd->affected_rows === 0) {
        throw new Exception('Insufficient profit balance.');
    }
    $upd->close();

    $log = $conn->prepare(
        "INSERT INTO transactions (user_id, amount, type, status) VALUES (?, ?, 'transfer', 'completed')"
    );
    $log->bind_param('id', $userId, $amount);
    $log->execute();
    $log->close();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => '$' . number_format($amount, 2) . ' transferred to your balance.']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
