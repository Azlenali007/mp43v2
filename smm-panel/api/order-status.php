<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';

ensure_installed();
$user = current_user();
if (!$user) {
    json_response(['ok' => false, 'message' => 'Authentication required.'], 401);
}

$orderId = validate_positive_int($_GET['id'] ?? null);
if (!$orderId) {
    json_response(['ok' => false, 'message' => 'Order ID is required.'], 422);
}

$pdo = Database::getConnection();
$stmt = $pdo->prepare('SELECT id, order_code, status, start_count, remains, charge, refunded_amount, created_at, updated_at FROM orders WHERE id = ? AND user_id = ? LIMIT 1');
$stmt->execute([$orderId, (int) $user['id']]);
$order = $stmt->fetch();
if (!$order) {
    json_response(['ok' => false, 'message' => 'Order not found.'], 404);
}

json_response(['ok' => true, 'order' => $order]);
