<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../classes/Provider.php';
require_once __DIR__ . '/../classes/Service.php';

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
$providers = Provider::all(true);
$syncedServices = 0;

foreach ($providers as $provider) {
    $res = Provider::fetchServices((int) $provider['id']);
    if (!$res['ok']) {
        continue;
    }
    $byExtId = [];
    foreach ($res['services'] as $ext) {
        $byExtId[(string) ($ext['service'] ?? '')] = $ext;
    }

    $localStmt = $pdo->prepare('SELECT * FROM services WHERE provider_id = ? AND sync_price = 1 AND provider_service_id IS NOT NULL');
    $localStmt->execute([(int) $provider['id']]);
    foreach ($localStmt->fetchAll() as $svc) {
        $extId = (string) $svc['provider_service_id'];
        if (!isset($byExtId[$extId])) {
            continue;
        }
        $ext = $byExtId[$extId];
        $newRate = (float) ($ext['rate'] ?? $svc['provider_rate']);
        $newSelling = Service::calculateSellingPrice($newRate, $svc['margin_type'], (float) $svc['margin_value']);
        $upd = $pdo->prepare('UPDATE services SET provider_rate = ?, price_per_1000 = ?, min_qty = ?, max_qty = ? WHERE id = ?');
        $upd->execute([
            $newRate,
            $newSelling,
            max(1, (int) ($ext['min'] ?? $svc['min_qty'])),
            max(1, (int) ($ext['max'] ?? $svc['max_qty'])),
            (int) $svc['id'],
        ]);
        $syncedServices++;
    }
}

write_app_log('cron', "sync-services: updated={$syncedServices}");
echo "Synced services: {$syncedServices}\n";
