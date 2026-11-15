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
$items = $input['orders'] ?? [];
if (!is_array($items) || empty($items)) {
    json_response(['ok' => false, 'message' => 'No orders provided.'], 422);
}

$results = [];
foreach (array_slice($items, 0, 50) as $row) {
    $svcId = validate_positive_int($row['service_id'] ?? null);
    $link = trim((string) ($row['link'] ?? ''));
    $qty = validate_positive_int($row['quantity'] ?? null);
    if (!$svcId || !$qty || $link === '') {
        $results[] = ['ok' => false, 'message' => 'Invalid order payload.'];
        continue;
    }
    $results[] = Order::placeOrder((int) $user['id'], $svcId, $link, $qty, 'bulk');
}

json_response(['ok' => true, 'results' => $results]);
