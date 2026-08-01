<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// Load platform settings from DB
$settingsRow = null;
$sq = $conn->prepare("SELECT * FROM settings WHERE id = 1 LIMIT 1");
$sq->execute();
$settingsRow = $sq->get_result()->fetch_assoc();
$sq->close();

$sitename  = $settingsRow['sitename']  ?? 'AI Trade Profit';
$siteurl   = $settingsRow['siteurl']   ?? '';
$sitemail  = $settingsRow['sitemail']  ?? '';
$ref_bonus = (float)($settingsRow['ref_bonus'] ?? 10);
$whatsapp  = $settingsRow['whatsapp']  ?? '';
$livechat  = $settingsRow['livechat']  ?? '';

// Payment methods — wallet addresses loaded from DB (never hardcoded in JS)
$payMethods = [];
$walletMap = [
    'Bitcoin (BTC)'      => ['col' => 'btc_wallet',  'code' => 'BTC'],
    'Ethereum (ETH)'     => ['col' => 'eth_wallet',  'code' => 'ETH'],
    'USDT (TRC20)'       => ['col' => 'usdt_trc20',  'code' => 'USDT'],
    'USDT (ERC20)'       => ['col' => 'usdt_erc20',  'code' => 'USDT'],
    'BNB (BEP20)'        => ['col' => 'bnb_wallet',  'code' => 'BNB'],
    'Solana (SOL)'       => ['col' => 'sol_wallet',  'code' => 'SOL'],
];
foreach ($walletMap as $label => $info) {
    $addr = $settingsRow[$info['col']] ?? '';
    if ($addr !== '' && $addr !== null) {
        $payMethods[$label] = ['address' => $addr, 'code' => $info['code']];
    }
}
