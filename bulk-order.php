<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$user = require_user();

require_once __DIR__ . '/classes/Order.php';
require_once __DIR__ . '/classes/Service.php';
require_once __DIR__ . '/classes/Wallet.php';
require_once __DIR__ . '/classes/Provider.php';

$results = [];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();
    $rawLines = trim((string) ($_POST['bulk_lines'] ?? ''));
    if ($rawLines === '') {
        $error = 'Enter at least one order line in the format: service_id | link | quantity';
    } else {
        $lines = preg_split('/\R/', $rawLines);
        $lines = array_slice(array_filter(array_map('trim', $lines)), 0, 50);
        foreach ($lines as $idx => $line) {
            $parts = array_map('trim', explode('|', $line));
            if (count($parts) !== 3) {
                $results[] = ['line' => $idx + 1, 'ok' => false, 'message' => 'Invalid syntax. Expected service_id | link | quantity'];
                continue;
            }
            $svcId = validate_positive_int($parts[0]);
            $link = $parts[1];
            $qty = validate_positive_int($parts[2]);
            if (!$svcId || !$qty) {
                $results[] = ['line' => $idx + 1, 'ok' => false, 'message' => 'Invalid service ID or quantity.'];
                continue;
            }
            $res = Order::placeOrder((int) $user['id'], $svcId, $link, $qty, 'bulk');
            if ($res['ok']) {
                $dispFmt = isset($res['displayed_charge'], $res['display_currency'])
                    ? format_exact_currency((float) $res['displayed_charge'], (string) $res['display_currency'], 4) . ' ' . $res['display_currency']
                    : format_currency($res['charge']);
                $results[] = ['line' => $idx + 1, 'ok' => true, 'message' => "Order #{$res['order_code']} placed (" . $dispFmt . ")"];
            } else {
                $results[] = ['line' => $idx + 1, 'ok' => false, 'message' => $res['error']];
            }
        }
        $user = current_user() ?: $user;
    }
}

Theme::renderPage('bulk-order.php', [
    'pageTitle' => 'Bulk Orders',
    'user'      => $user,
    'results'   => $results,
    'error'     => $error,
]);
