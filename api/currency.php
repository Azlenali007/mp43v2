<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../classes/User.php';

ensure_installed();
$user = current_user();
if (!$user) {
    json_response(['ok' => false, 'message' => 'Only logged-in users can change their display currency.'], 401);
}
verify_csrf_or_abort(true);

$input = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

$code = strtoupper(trim((string) ($input['currency'] ?? '')));
$cur = Currency::find($code);
if (!$cur || (int) $cur['is_enabled'] !== 1 || (float) ($cur['exchange_rate'] ?? 0) <= 0) {
    json_response(['ok' => false, 'message' => 'Selected currency is not enabled or lacks a valid exchange rate.'], 422);
}

User::updatePreferredCurrency((int) $user['id'], $code);
current_user(true);

json_response([
    'ok'       => true,
    'currency' => $cur['code'],
    'symbol'   => $cur['symbol'],
    'message'  => "Display currency switched to {$cur['code']} ({$cur['symbol']}).",
]);
