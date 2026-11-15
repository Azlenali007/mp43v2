<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$user = require_user();

require_once __DIR__ . '/classes/Category.php';
require_once __DIR__ . '/classes/Service.php';

$categories = Category::all(true);
$rawServices = Service::all(null, true, '');
$icons = icon_registry();

$displayCurrency = Currency::getUserDisplay($user);
$displayCode = (string) $displayCurrency['code'];
$displaySymbol = (string) $displayCurrency['symbol'];
$displayDecimals = (int) ($displayCurrency['decimal_places'] ?? 2);
$baseCurrency = Currency::getBase();
$baseCode = (string) $baseCurrency['code'];
$baseSymbol = (string) $baseCurrency['symbol'];

$services = [];
foreach ($rawServices as $svc) {
    $baseRate = (float) $svc['price_per_1000'];
    $dispRate = Currency::convertFromBase($baseRate, $displayCode, 4);
    $svc['base_price_per_1000'] = $baseRate;
    $svc['price_per_1000'] = $dispRate;
    if (empty($svc['icon_key'])) {
        $svc['icon_key'] = $svc['category_icon'] ?: detect_icon_key_from_text((string) $svc['name']);
    }
    $services[] = $svc;
}

$defaultServiceId = validate_positive_int($_GET['service'] ?? null);
$defaultCategoryId = validate_positive_int($_GET['category'] ?? null);
if ($defaultServiceId && !$defaultCategoryId) {
    foreach ($services as $s) {
        if ((int) $s['id'] === $defaultServiceId) {
            $defaultCategoryId = (int) $s['category_id'];
            break;
        }
    }
}

$walletDisplayBalance = Currency::convertFromBase((float) $user['wallet_balance'], $displayCode, $displayDecimals);

$newOrderPayload = [
    'categories'        => $categories,
    'services'          => $services,
    'icons'             => $icons,
    'displayCurrency'   => $displayCode,
    'currencySymbol'    => $displaySymbol,
    'displayDecimals'   => $displayDecimals,
    'baseCurrency'      => $baseCode,
    'baseSymbol'        => $baseSymbol,
    'walletBalance'     => $walletDisplayBalance,
    'baseWalletBalance' => (float) $user['wallet_balance'],
    'defaultCategoryId' => $defaultCategoryId,
    'defaultServiceId'  => $defaultServiceId,
    'apiBase'           => app_root_prefix(),
];

Theme::renderPage('new-order.php', [
    'pageTitle'       => 'New Order',
    'user'            => $user,
    'newOrderPayload' => $newOrderPayload,
]);
