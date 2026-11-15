<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';

ensure_installed();

$force = false;
if (PHP_SAPI !== 'cli') {
    $expected = get_setting('cron_token', '');
    $given = (string) ($_GET['token'] ?? '');
    if ($expected === '' || !hash_equals($expected, $given)) {
        http_response_code(403);
        exit('Forbidden');
    }
    $force = !empty($_GET['force']);
} else {
    $force = in_array('--force', $argv ?? [], true);
}

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
write_app_log('cron', 'update-exchange-rates: ' . ($res['ok'] ? $res['message'] : ($res['error'] ?? 'Failed')));
echo ($res['ok'] ? $res['message'] : ('Error: ' . ($res['error'] ?? 'Unknown'))) . "\n";
