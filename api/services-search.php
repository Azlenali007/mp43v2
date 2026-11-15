<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../classes/Service.php';
require_once __DIR__ . '/../classes/Category.php';

ensure_installed();
$user = current_user();
if (!$user) {
    json_response(['ok' => false, 'message' => 'Authentication required.'], 401);
}

$q = sanitize_string($_GET['q'] ?? '', 120);
$categoryId = validate_positive_int($_GET['category_id'] ?? null);

$displayCur = Currency::getUserDisplay($user);
$displayCode = (string) $displayCur['code'];
$displaySymbol = (string) $displayCur['symbol'];
$displayDecimals = (int) ($displayCur['decimal_places'] ?? 2);
$baseCode = Currency::getBaseCode();

$rawServices = Service::all($categoryId, true, $q);
$results = [];
foreach (array_slice($rawServices, 0, 80) as $svc) {
    $baseRate = (float) $svc['price_per_1000'];
    $dispRate = Currency::convertFromBase($baseRate, $displayCode, 4);
    $results[] = [
        'id'                  => (int) $svc['id'],
        'category_id'         => (int) $svc['category_id'],
        'category_name'       => (string) $svc['category_name'],
        'name'                => (string) $svc['name'],
        'description'         => (string) ($svc['description'] ?? ''),
        'icon_key'            => (string) ($svc['icon_key'] ?: $svc['category_icon'] ?: 'sparkles'),
        'type'                => (string) ($svc['type'] ?? 'default'),
        'min_qty'             => (int) $svc['min_qty'],
        'max_qty'             => (int) $svc['max_qty'],
        'refill'              => (int) ($svc['refill'] ?? 0),
        'cancel'              => (int) ($svc['cancel'] ?? 0),
        'base_price_per_1000' => $baseRate,
        'price_per_1000'      => $dispRate,
        'formatted_rate'      => Currency::formatExact($dispRate, $displayCode, max(2, min(4, $displayDecimals === 0 ? 2 : 4))),
    ];
}

json_response([
    'ok'               => true,
    'display_currency' => $displayCode,
    'display_symbol'   => $displaySymbol,
    'base_currency'    => $baseCode,
    'services'         => $results,
]);
