<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Service.php';
require_once __DIR__ . '/../classes/Provider.php';
require_once __DIR__ . '/../classes/Wallet.php';
require_once __DIR__ . '/../classes/Order.php';

ensure_installed();

$input = $_POST;
if (empty($input)) {
    $json = json_decode((string) file_get_contents('php://input'), true);
    if (is_array($json)) {
        $input = $json;
    }
}

$apiKey = trim((string) ($input['key'] ?? ''));
$action = strtolower(trim((string) ($input['action'] ?? '')));

if ($apiKey === '') {
    json_response(['error' => 'API key is required.'], 401);
}

$user = User::findByApiKey($apiKey);
if (!$user) {
    json_response(['error' => 'Invalid or inactive API key.'], 401);
}

switch ($action) {
    case 'balance':
        json_response([
            'balance'  => number_format((float) $user['wallet_balance'], 4, '.', ''),
            'currency' => get_setting('currency_code', 'INR'),
        ]);

    case 'services':
        $services = Service::all(null, true, '');
        $out = [];
        foreach ($services as $s) {
            $out[] = [
                'service'  => (int) $s['id'],
                'name'     => $s['name'],
                'type'     => ucfirst($s['type']),
                'category' => $s['category_name'],
                'rate'     => number_format((float) $s['price_per_1000'], 4, '.', ''),
                'min'      => (int) $s['min_qty'],
                'max'      => (int) $s['max_qty'],
                'dripfeed' => (bool) $s['dripfeed'],
                'refill'   => (bool) $s['refill'],
                'cancel'   => (bool) $s['cancel'],
            ];
        }
        json_response($out);

    case 'add':
        $serviceId = validate_positive_int($input['service'] ?? null);
        $link = trim((string) ($input['link'] ?? ''));
        $quantity = validate_positive_int($input['quantity'] ?? null);
        if (!$serviceId || !$quantity || $link === '') {
            json_response(['error' => 'Missing required parameters: service, link, quantity.'], 422);
        }
        $res = Order::placeOrder((int) $user['id'], $serviceId, $link, $quantity, 'api');
        if (!$res['ok']) {
            json_response(['error' => $res['error']], 400);
        }
        json_response(['order' => $res['order_id']]);

    case 'status':
        $orderId = validate_positive_int($input['order'] ?? null);
        if (!$orderId) {
            json_response(['error' => 'Order ID is required.'], 422);
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT charge, start_count, status, remains FROM orders WHERE id = ? AND user_id = ? LIMIT 1');
        $stmt->execute([$orderId, (int) $user['id']]);
        $ord = $stmt->fetch();
        if (!$ord) {
            json_response(['error' => 'Order not found.'], 404);
        }
        json_response([
            'charge'      => number_format((float) $ord['charge'], 4, '.', ''),
            'start_count' => (string) $ord['start_count'],
            'status'      => ucfirst(str_replace('_', ' ', $ord['status'])),
            'remains'     => (string) $ord['remains'],
            'currency'    => get_setting('currency_code', 'INR'),
        ]);

    default:
        json_response(['error' => 'Unsupported API action.'], 400);
}
