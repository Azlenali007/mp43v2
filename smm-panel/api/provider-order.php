<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../classes/Provider.php';
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

$pdo = Database::getConnection();
$stmt = $pdo->prepare(
    'SELECT o.*, s.provider_id AS svc_provider_id, s.provider_service_id
     FROM orders o
     INNER JOIN services s ON s.id = o.service_id
     WHERE o.id = ? LIMIT 1'
);
$stmt->execute([$orderId]);
$order = $stmt->fetch();
if (!$order) {
    json_response(['ok' => false, 'message' => 'Order not found.'], 404);
}
if (!empty($order['provider_order_id'])) {
    json_response(['ok' => false, 'message' => 'Order already has provider order ID #' . $order['provider_order_id']], 400);
}

$providerId = (int) ($order['provider_id'] ?: $order['svc_provider_id']);
$extServiceId = (string) ($order['provider_service_id'] ?? '');
if (!$providerId || $extServiceId === '') {
    json_response(['ok' => false, 'message' => 'Service is not mapped to an external provider.'], 400);
}

$res = Provider::submitOrder($providerId, $extServiceId, $order['link'], (int) $order['quantity']);
if (!$res['ok']) {
    Order::addLog($orderId, $order['status'], 'Manual provider dispatch failed: ' . $res['error']);
    json_response(['ok' => false, 'message' => $res['error']], 502);
}

$pdo->prepare('UPDATE orders SET provider_id = ?, provider_order_id = ?, status = "processing" WHERE id = ?')
    ->execute([$providerId, $res['provider_order_id'], $orderId]);
Order::addLog($orderId, 'processing', 'Dispatched to provider by admin. Provider Order ID: ' . $res['provider_order_id']);

json_response(['ok' => true, 'provider_order_id' => $res['provider_order_id'], 'message' => 'Order submitted to provider.']);
