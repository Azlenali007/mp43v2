<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../classes/Wallet.php';
require_once __DIR__ . '/../classes/Order.php';

ensure_installed();

if (PHP_SAPI !== 'cli') {
    $expected = get_setting('cron_token', '');
    $given = (string) ($_GET['token'] ?? '');
    if ($expected === '' || !hash_equals($expected, $given)) {
        http_response_code(403);
        exit('Forbidden');
    }
}

$pdo = Database::getConnection();
$stmt = $pdo->query(
    'SELECT * FROM orders
     WHERE status IN ("cancelled", "failed", "partial")
       AND refunded_amount = 0
     ORDER BY id ASC
     LIMIT 50'
);

$refunded = 0;
foreach ($stmt->fetchAll() as $ord) {
    $amount = 0.0;
    if (in_array($ord['status'], ['cancelled', 'failed'], true)) {
        $amount = (float) $ord['charge'];
    } elseif ($ord['status'] === 'partial' && (int) $ord['remains'] > 0 && (int) $ord['quantity'] > 0) {
        $ratio = min(1.0, (int) $ord['remains'] / (int) $ord['quantity']);
        $amount = round((float) $ord['charge'] * $ratio, 4);
    }
    if ($amount > 0) {
        $res = Order::refundOrder((int) $ord['id'], $amount, 'Automated refund sweep (' . $ord['status'] . ')');
        if ($res['ok']) {
            $refunded++;
        }
    }
}

write_app_log('cron', "process-refunds: refunded={$refunded}");
echo "Refunded orders: {$refunded}\n";
