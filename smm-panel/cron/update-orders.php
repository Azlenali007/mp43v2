<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../classes/Provider.php';
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
    'SELECT id FROM orders
     WHERE provider_id IS NOT NULL
       AND provider_order_id IS NOT NULL
       AND status IN ("pending", "processing", "in_progress")
     ORDER BY updated_at ASC
     LIMIT 50'
);

$updated = 0;
$failed = 0;
foreach ($stmt->fetchAll() as $row) {
    $res = Order::syncOrderWithProvider((int) $row['id']);
    if ($res['ok']) {
        $updated++;
    } else {
        $failed++;
    }
}

write_app_log('cron', "update-orders: synced={$updated}, errors={$failed}");
echo "Synced: {$updated}, Errors: {$failed}\n";
