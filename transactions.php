<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$user = require_user();

$pdo = Database::getConnection();
$stmt = $pdo->prepare('SELECT * FROM wallet_transactions WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT 150');
$stmt->execute([(int) $user['id']]);
$txs = $stmt->fetchAll();

Theme::renderPage('transactions.php', [
    'pageTitle' => 'Transactions',
    'user'      => $user,
    'txs'       => $txs,
]);
