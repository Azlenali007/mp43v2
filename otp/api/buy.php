<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../classes/OtpProvider.php';

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
json_response($res, $res['ok'] ? 200 : 400);
