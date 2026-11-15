<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../classes/Provider.php';
require_once __DIR__ . '/../classes/Wallet.php';
require_once __DIR__ . '/../classes/Order.php';

ensure_installed();
$admin = current_admin();
if (!$admin || !admin_has_permission($admin, 'manage_orders')) {
    json_response(['ok' => false, 'message' => 'Unauthorized.'], 403);
}
verify_csrf_or_abort(true);

$input = json_decode((string) file_get_contents('php://input'), true) ?: $_POST;
$orderId = validate_positive_int($input['order_id'] ?? null);
if (!$orderId) {
    json_response(['ok' => false, 'message' => 'Order ID required.'], 422);
}

$res = Order::syncOrderWithProvider($orderId);
if (!$res['ok']) {
    json_response(['ok' => false, 'message' => $res['error']], 400);
}

json_response([
    'ok'      => true,
    'status'  => $res['status'],
    'message' => 'Synced status: ' . ucfirst($res['status']) . " (Remains: {$res['remains']})",
]);
