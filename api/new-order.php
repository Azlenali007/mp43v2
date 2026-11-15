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
json_response([
    'ok'            => true,
    'order_id'      => $res['order_id'],
    'order_code'    => $res['order_code'],
    'charge'        => $res['charge'],
    'balance_after' => (float) ($updatedUser['wallet_balance'] ?? 0),
    'message'       => "Order #{$res['order_code']} placed successfully (" . format_currency($res['charge']) . ").",
]);
