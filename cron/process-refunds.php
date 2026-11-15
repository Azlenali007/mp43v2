<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    exit('403 Forbidden: Cron scripts can only be executed via PHP CLI.');
}

set_time_limit(300);

$lockFile = sys_get_temp_dir() . '/smm_cron_process_refunds.lock';
$lockHandle = @fopen($lockFile, 'c+');
if ($lockHandle === false || !@flock($lockHandle, LOCK_EX | LOCK_NB)) {
    echo "Cron process process-refunds is already running. Exiting.\n";
    exit(0);
}

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../classes/Wallet.php';
require_once __DIR__ . '/../classes/Order.php';

ensure_installed();

try {
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
            try {
                $res = Order::refundOrder((int) $ord['id'], $amount, 'Automated refund sweep (' . $ord['status'] . ')');
                if ($res['ok']) {
                    $refunded++;
                }
            } catch (Throwable $e) {
                write_app_log('cron', "process-refunds order #" . $ord['id'] . " exception: " . $e->getMessage());
            }
        }
    }

    $msg = "process-refunds completed: refunded={$refunded}";
    write_app_log('cron', $msg);
    echo "Refunded orders: {$refunded}\n";
} catch (Throwable $e) {
    write_app_log('cron', "process-refunds fatal error: " . $e->getMessage());
    echo "Error: " . $e->getMessage() . "\n";
} finally {
    @flock($lockHandle, LOCK_UN);
    @fclose($lockHandle);
}
