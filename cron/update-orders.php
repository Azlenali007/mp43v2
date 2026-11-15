<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    exit('403 Forbidden: Cron scripts can only be executed via PHP CLI.');
}

set_time_limit(300);

$lockFile = sys_get_temp_dir() . '/smm_cron_update_orders.lock';
$lockHandle = @fopen($lockFile, 'c+');
if ($lockHandle === false || !@flock($lockHandle, LOCK_EX | LOCK_NB)) {
    echo "Cron process update-orders is already running. Exiting.\n";
    exit(0);
}

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../classes/Provider.php';
require_once __DIR__ . '/../classes/Wallet.php';
require_once __DIR__ . '/../classes/Order.php';

ensure_installed();

try {
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
        try {
            $res = Order::syncOrderWithProvider((int) $row['id']);
            if ($res['ok']) {
                $updated++;
            } else {
                $failed++;
            }
        } catch (Throwable $e) {
            $failed++;
            write_app_log('cron', "update-orders order #" . $row['id'] . " exception: " . $e->getMessage());
        }
    }

    $msg = "update-orders completed: synced={$updated}, errors={$failed}";
    write_app_log('cron', $msg);
    echo "Synced: {$updated}, Errors: {$failed}\n";
} catch (Throwable $e) {
    write_app_log('cron', "update-orders fatal error: " . $e->getMessage());
    echo "Error: " . $e->getMessage() . "\n";
} finally {
    @flock($lockHandle, LOCK_UN);
    @fclose($lockHandle);
}
