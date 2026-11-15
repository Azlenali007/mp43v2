<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';

$user = current_user();
if (!$user) {
    json_response(['ok' => false, 'error' => 'Unauthorized'], 401);
}

$input = json_decode(file_get_contents('php://input'), true);
$serviceId = isset($input['service_id']) ? (int)$input['service_id'] : 0;

if ($serviceId <= 0) {
    json_response(['ok' => false, 'error' => 'Invalid service selected.'], 400);
}

$res = OtpProvider::buyNumber($serviceId);
if (!$res['ok']) {
    json_response(['ok' => false, 'error' => $res['error'] ?? 'Failed to buy number.'], 400);
}

json_response([
    'ok' => true,
    'order_id' => $res['order_id'],
    'order_code' => $res['order_code'],
    'phone_number' => $res['phone_number'],
    'activation_id' => $res['activation_id'],
    'message' => 'Number acquired successfully.',
]);
