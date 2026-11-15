<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../classes/Service.php';
require_once __DIR__ . '/../classes/Provider.php';
require_once __DIR__ . '/../classes/Wallet.php';
require_once __DIR__ . '/../classes/Order.php';

ensure_installed();
$user = current_user();
if (!$user) {
    json_response(['ok' => false, 'message' => 'Authentication required.'], 401);
}
verify_csrf_or_abort(true);

$input = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

$serviceId = validate_positive_int($input['service_id'] ?? null);
$link = trim((string) ($input['link'] ?? ''));
$quantity = validate_positive_int($input['quantity'] ?? null);

if (!$serviceId || !$quantity || $link === '') {
    json_response(['ok' => false, 'message' => 'Service, valid link, and quantity are required.'], 422);
}

$res = Order::placeOrder((int) $user['id'], $serviceId, $link, $quantity, 'web');
if (!$res['ok']) {
    json_response(['ok' => false, 'message' => $res['error']], 400);
}

$updatedUser = current_user(true);
$displayCur = Currency::getUserDisplay($updatedUser);
$dispCode = (string) $displayCur['code'];
$baseBalance = (float) ($updatedUser['wallet_balance'] ?? 0);
$dispBalance = Currency::convertFromBase($baseBalance, $dispCode, 4);

json_response([
    'ok'                    => true,
    'order_id'              => $res['order_id'],
    'order_code'            => $res['order_code'],
    'charge'                => $res['charge'],
    'displayed_charge'      => $res['displayed_charge'],
    'display_currency'      => $res['display_currency'],
    'balance_after'         => $dispBalance,
    'balance_after_base'    => $baseBalance,
    'balance_after_display' => $dispBalance,
    'message'               => "Order #{$res['order_code']} placed successfully (" . Currency::formatExact($res['displayed_charge'], $res['display_currency']) . ").",
]);
