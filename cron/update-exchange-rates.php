<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    exit('403 Forbidden: Cron scripts can only be executed via PHP CLI.');
}

set_time_limit(300);

$lockFile = sys_get_temp_dir() . '/smm_cron_update_exchange_rates.lock';
$lockHandle = @fopen($lockFile, 'c+');
if ($lockHandle === false || !@flock($lockHandle, LOCK_EX | LOCK_NB)) {
    echo "Cron process update-exchange-rates is already running. Exiting.\n";
    exit(0);
}

require_once __DIR__ . '/../includes/auth.php';

ensure_installed();

$force = in_array('--force', $argv ?? [], true);

try {
    $autoEnabled = get_setting('exchange_auto_enabled', '1') === '1';
    if (!$autoEnabled && !$force) {
        echo "Auto exchange rate updates are disabled in settings.\n";
        exit(0);
    }

    $intervalHours = max(1, (int) get_setting('exchange_interval_hours', '12'));
    $lastSync = get_setting('exchange_last_sync_at', '');
    if (!$force && $lastSync !== '') {
        $elapsed = time() - (int) strtotime($lastSync);
        if ($elapsed < ($intervalHours * 3600) - 60) {
            echo "Skipped: last sync was {$lastSync} (interval {$intervalHours}h).\n";
            exit(0);
        }
    }

    $res = Currency::fetchAndUpdateRates('cron');
    $msg = $res['ok'] ? $res['message'] : ($res['error'] ?? 'Failed');
    write_app_log('cron', 'update-exchange-rates: ' . $msg);
    echo ($res['ok'] ? $res['message'] : ('Error: ' . $msg)) . "\n";
} catch (Throwable $e) {
    write_app_log('cron', 'update-exchange-rates fatal error: ' . $e->getMessage());
    echo 'Error: ' . $e->getMessage() . "\n";
} finally {
    @flock($lockHandle, LOCK_UN);
    @fclose($lockHandle);
}
