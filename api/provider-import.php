<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../classes/Provider.php';
require_once __DIR__ . '/../classes/Category.php';
require_once __DIR__ . '/../classes/Service.php';

ensure_installed();
$admin = current_admin();
if (!$admin || !admin_has_permission($admin, 'manage_providers')) {
    json_response(['ok' => false, 'message' => 'Unauthorized administrator action.'], 403);
}
verify_csrf_or_abort(true);

$input = json_decode((string) file_get_contents('php://input'), true) ?: $_POST;
$action = $input['action'] ?? '';
$providerId = validate_positive_int($input['provider_id'] ?? null);

if (!$providerId) {
    json_response(['ok' => false, 'message' => 'Provider ID is required.'], 422);
}

$provider = Provider::find($providerId);
if (!$provider) {
    json_response(['ok' => false, 'message' => 'Provider not found.'], 404);
}

// Mandatory currency validation before ANY preview or import
$curCheck = Provider::validateProviderCurrencyConversion($provider);
if (!$curCheck['ok']) {
    json_response([
        'ok'      => false,
        'code'    => 'PROVIDER_CURRENCY_REQUIRED',
        'message' => $curCheck['error'],
    ], 422);
}

$providerCurrency = $curCheck['provider_currency'];
$baseCurrency = $curCheck['base_currency'];
$exchangeRate = $curCheck['exchange_rate'];
$baseCurRow = Currency::getBase();
$provCurRow = Currency::find($providerCurrency);

if ($action === 'fetch_preview') {
    $res = Provider::fetchServices($providerId);
    if (!$res['ok']) {
        json_response(['ok' => false, 'message' => $res['error']], 502);
    }

    $pdo = Database::getConnection();
    $mapStmt = $pdo->prepare('SELECT external_service_id, service_id FROM provider_service_mappings WHERE provider_id = ?');
    $mapStmt->execute([$providerId]);
    $existingMap = [];
    foreach ($mapStmt->fetchAll() as $m) {
        $existingMap[(string) $m['external_service_id']] = (int) $m['service_id'];
    }

    $enriched = [];
    foreach ($res['services'] as $svc) {
        $extId = (string) ($svc['service'] ?? '');
        if ($extId === '') {
            continue;
        }
        $origRate = max(0.0, (float) ($svc['rate'] ?? 0));
        $conv = Currency::convertStrict($origRate, $providerCurrency, $baseCurrency, 4);
        $convertedCost = $conv['ok'] ? (float) $conv['amount'] : 0.0;

        $enriched[] = [
            'service'           => $extId,
            'name'              => (string) ($svc['name'] ?? ''),
            'category'          => (string) ($svc['category'] ?? 'General'),
            'original_rate'     => $origRate,
            'provider_currency' => $providerCurrency,
            'exchange_rate'     => $exchangeRate,
            'converted_cost'    => $convertedCost,
            'rate'              => $convertedCost, // Converted Base Currency cost before margin
            'min'               => (int) ($svc['min'] ?? 10),
            'max'               => (int) ($svc['max'] ?? 10000),
            'type'              => strtolower((string) ($svc['type'] ?? 'default')),
            'dripfeed'          => !empty($svc['dripfeed']),
            'refill'            => !empty($svc['refill']),
            'cancel'            => !empty($svc['cancel']),
            'description'       => (string) ($svc['desc'] ?? ($svc['description'] ?? '')),
            'is_imported'       => isset($existingMap[$extId]),
            'local_id'          => $existingMap[$extId] ?? null,
        ];
    }

    json_response([
        'ok'                => true,
        'provider_currency' => $providerCurrency,
        'provider_symbol'   => $provCurRow['symbol'] ?? $providerCurrency,
        'base_currency'     => $baseCurrency,
        'base_symbol'       => $baseCurRow['symbol'] ?? '₹',
        'exchange_rate'     => $exchangeRate,
        'services'          => $enriched,
    ]);
}

if ($action === 'import_services') {
    $items = $input['services'] ?? [];
    $marginType = ($input['margin_type'] ?? 'percent') === 'fixed' ? 'fixed' : 'percent';
    $marginValue = (float) ($input['margin_value'] ?? 25);
    $updateExisting = !empty($input['update_existing']);
    $targetCategoryId = validate_positive_int($input['target_category_id'] ?? null);

    if (!is_array($items) || empty($items)) {
        json_response(['ok' => false, 'message' => 'No services selected for import.'], 422);
    }

    $pdo = Database::getConnection();
    $importedCount = 0;
    $updatedCount = 0;
    $skippedCount = 0;

    $catCache = [];
    foreach (Category::all(false) as $c) {
        $catCache[strtolower(trim($c['name']))] = (int) $c['id'];
    }

    foreach ($items as $item) {
        $extId = trim((string) ($item['service'] ?? ''));
        $extName = sanitize_string((string) ($item['name'] ?? ''), 255);
        $extCat = sanitize_string((string) ($item['category'] ?? 'General'), 120);
        // Always convert authoritative original_rate from provider currency into canonical base currency FIRST
        $origProviderRate = max(0.0, (float) ($item['original_rate'] ?? ($item['rate'] ?? 0)));
        $conv = Currency::convertStrict($origProviderRate, $providerCurrency, $baseCurrency, 4);
        if (!$conv['ok']) {
            json_response(['ok' => false, 'message' => $conv['error']], 422);
        }
        $convertedCostInBase = (float) $conv['amount'];
        $usedExRate = (float) $conv['cross_rate'];

        $extMin = max(1, (int) ($item['min'] ?? 10));
        $extMax = max($extMin, (int) ($item['max'] ?? 10000));
        $itemMarginType = in_array($item['margin_type'] ?? '', ['percent', 'fixed'], true) ? $item['margin_type'] : $marginType;
        $itemMarginVal = isset($item['margin_value']) && is_numeric($item['margin_value']) ? (float) $item['margin_value'] : $marginValue;

        // Calculate selling price in canonical base currency AFTER converting provider cost
        $sellingPrice = Service::calculateSellingPrice($convertedCostInBase, $itemMarginType, $itemMarginVal);

        if ($extId === '' || $extName === '') {
            continue;
        }

        $catId = $targetCategoryId;
        if (!$catId) {
            $catKey = strtolower($extCat);
            if (!isset($catCache[$catKey])) {
                $catCache[$catKey] = Category::create([
                    'name'        => $extCat,
                    'icon_key'    => detect_icon_key_from_text($extCat),
                    'platform'    => detect_icon_key_from_text($extCat),
                    'description' => null,
                    'sort_order'  => 0,
                    'status'      => 'active',
                ]);
            }
            $catId = $catCache[$catKey];
        }

        $mapCheck = $pdo->prepare('SELECT id, service_id FROM provider_service_mappings WHERE provider_id = ? AND external_service_id = ? LIMIT 1');
        $mapCheck->execute([$providerId, $extId]);
        $existingMapping = $mapCheck->fetch();

        $iconKey = detect_icon_key_from_text($extCat . ' ' . $extName);
        $svcType = in_array(strtolower((string) ($item['type'] ?? 'default')), ['default', 'custom_comments', 'package', 'subscriptions'], true)
            ? strtolower((string) $item['type'])
            : 'default';

        if ($existingMapping) {
            if ($updateExisting) {
                $upd = $pdo->prepare(
                    'UPDATE services SET
                        original_currency = ?, original_provider_rate = ?, import_exchange_rate = ?,
                        provider_rate = ?, margin_type = ?, margin_value = ?, price_per_1000 = ?,
                        min_qty = ?, max_qty = ?
                     WHERE id = ?'
                );
                $upd->execute([
                    $providerCurrency,
                    $origProviderRate,
                    $usedExRate,
                    $convertedCostInBase,
                    $itemMarginType,
                    $itemMarginVal,
                    $sellingPrice,
                    $extMin,
                    $extMax,
                    (int) $existingMapping['service_id'],
                ]);
                $pdo->prepare(
                    'UPDATE provider_service_mappings SET external_currency = ?, external_rate = ?, exchange_rate_used = ?, external_min = ?, external_max = ?, last_synced_at = NOW() WHERE id = ?'
                )->execute([$providerCurrency, $origProviderRate, $usedExRate, $extMin, $extMax, (int) $existingMapping['id']]);
                $updatedCount++;
            } else {
                $skippedCount++;
            }
            continue;
        }

        $localServiceId = Service::create([
            'category_id'            => $catId,
            'provider_id'            => $providerId,
            'provider_service_id'    => $extId,
            'name'                   => $extName,
            'description'            => sanitize_string((string) ($item['description'] ?? ''), 2000),
            'icon_key'               => $iconKey,
            'platform'               => $iconKey,
            'type'                   => $svcType,
            'original_currency'      => $providerCurrency,
            'original_provider_rate' => $origProviderRate,
            'import_exchange_rate'   => $usedExRate,
            'provider_rate'          => $convertedCostInBase,
            'margin_type'            => $itemMarginType,
            'margin_value'           => $itemMarginVal,
            'price_per_1000'         => $sellingPrice,
            'min_qty'                => $extMin,
            'max_qty'                => $extMax,
            'dripfeed'               => !empty($item['dripfeed']),
            'refill'                 => !empty($item['refill']),
            'cancel'                 => !empty($item['cancel']),
            'sync_price'             => 1,
            'status'                 => 'active',
        ]);

        $insMap = $pdo->prepare(
            'INSERT INTO provider_service_mappings
             (provider_id, service_id, external_service_id, external_category, external_name, external_currency, external_rate, exchange_rate_used, external_min, external_max, last_synced_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
        );
        $insMap->execute([$providerId, $localServiceId, $extId, $extCat, $extName, $providerCurrency, $origProviderRate, $usedExRate, $extMin, $extMax]);
        $importedCount++;
    }

    if ($providerCurrency !== $baseCurrency && ($importedCount > 0 || $updatedCount > 0)) {
        Currency::logConversion('provider_import', $providerCurrency, $baseCurrency, 1.0, $exchangeRate, $exchangeRate, $providerId);
    }

    log_activity('admin', (int) $admin['id'], 'provider_services_imported', 'provider', $providerId, "Cur: {$providerCurrency}->{$baseCurrency} @ {$exchangeRate}, Imported: {$importedCount}, Updated: {$updatedCount}, Skipped: {$skippedCount}");
    json_response([
        'ok'      => true,
        'message' => "Import completed ({$providerCurrency} → {$baseCurrency} @ {$exchangeRate}): {$importedCount} added, {$updatedCount} updated, {$skippedCount} skipped.",
    ]);
}

json_response(['ok' => false, 'message' => 'Unknown action.'], 400);
