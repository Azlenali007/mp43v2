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
$skippedProviders = 0;

foreach ($providers as $provider) {
    // Strictly validate provider currency conversion before touching any service prices
    $curCheck = Provider::validateProviderCurrencyConversion($provider);
    if (!$curCheck['ok']) {
        write_app_log('cron', 'sync-services skipped provider #' . $provider['id'] . ': ' . $curCheck['error']);
        $skippedProviders++;
        continue;
    }
    $providerCurrency = $curCheck['provider_currency'];
    $baseCurrency = $curCheck['base_currency'];

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
        if (!isset($byExtId[$extId]) || !isset($byExtId[$extId]['rate'])) {
            continue;
        }
        $ext = $byExtId[$extId];
        $origProviderRate = max(0.0, (float) $ext['rate']);

        // Convert raw provider price from providerCurrency -> baseCurrency cleanly (never double convert!)
        $conv = Currency::convertStrict($origProviderRate, $providerCurrency, $baseCurrency, 4);
        if (!$conv['ok']) {
            continue;
        }
        $convertedBaseCost = (float) $conv['amount'];
        $usedRate = (float) $conv['cross_rate'];

        // Apply margin strictly AFTER conversion to Base Currency
        $newSelling = Service::calculateSellingPrice($convertedBaseCost, $svc['margin_type'], (float) $svc['margin_value']);
        $newMin = max(1, (int) ($ext['min'] ?? $svc['min_qty']));
        $newMax = max($newMin, (int) ($ext['max'] ?? $svc['max_qty']));

        $upd = $pdo->prepare(
            'UPDATE services SET
                original_currency = ?, original_provider_rate = ?, import_exchange_rate = ?,
                provider_rate = ?, price_per_1000 = ?, min_qty = ?, max_qty = ?
             WHERE id = ?'
        );
        $upd->execute([
            $providerCurrency,
            $origProviderRate,
            $usedRate,
            $convertedBaseCost,
            $newSelling,
            $newMin,
            $newMax,
            (int) $svc['id'],
        ]);

        $pdo->prepare(
            'UPDATE provider_service_mappings SET external_currency = ?, external_rate = ?, exchange_rate_used = ?, external_min = ?, external_max = ?, last_synced_at = NOW()
             WHERE provider_id = ? AND external_service_id = ?'
        )->execute([$providerCurrency, $origProviderRate, $usedRate, $newMin, $newMax, (int) $provider['id'], $extId]);

        $syncedServices++;
    }
}

write_app_log('cron', "sync-services: updated={$syncedServices}, skipped_providers={$skippedProviders}");
echo "Synced services: {$syncedServices} (skipped providers: {$skippedProviders})\n";
