<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../classes/OtpProvider.php';

$user = current_user();
if (!$user) {
    json_response(['ok' => false, 'error' => 'Unauthorized'], 401);
}

$input = json_decode(file_get_contents('php://input'), true);
$orderId = isset($input['order_id']) ? (int)$input['order_id'] : 0;
if ($orderId <= 0) {
    json_response(['ok' => false, 'error' => 'Invalid order ID.'], 400);
}

$res = OtpProvider::cancelOrder($orderId);
json_response($res, $res['ok'] ? 200 : 400);
