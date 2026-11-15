<?php
declare(strict_types=1);
ob_start();
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../classes/User.php';

try {
    ensure_installed();
    $user = current_user();
    if (!$user) {
        json_response(['ok' => false, 'message' => 'Only logged-in users can change their display currency.'], 401);
    }
    verify_csrf_or_abort(true);

    $rawBody = (string) file_get_contents('php://input');
    $input = json_decode($rawBody, true);
    if (!is_array($input)) {
        $input = $_POST;
    }

    $code = strtoupper(trim((string) ($input['currency'] ?? '')));
    if ($code === '') {
        json_response(['ok' => false, 'message' => 'Please choose a currency code.'], 422);
    }

    $cur = Currency::find($code);
    if (!$cur || (int) $cur['is_enabled'] !== 1) {
        json_response(['ok' => false, 'message' => 'Selected currency is not enabled.'], 422);
    }
    if ((float) ($cur['exchange_rate'] ?? 0) <= 0) {
        json_response([
            'ok'      => false,
            'message' => "Currency {$cur['code']} is enabled, but its exchange rate has not been synced yet. Please ask the administrator to sync exchange rates.",
        ], 422);
    }

    $saveRes = User::updatePreferredCurrency((int) $user['id'], $code);
    if (!$saveRes['ok']) {
        json_response(['ok' => false, 'message' => $saveRes['error'] ?? 'Could not save currency preference.'], 500);
    }

    current_user(true);

    json_response([
        'ok'       => true,
        'currency' => $cur['code'],
        'symbol'   => $cur['symbol'],
        'name'     => $cur['name'],
        'message'  => "Display currency switched to {$cur['code']} ({$cur['symbol']}).",
    ]);
} catch (Throwable $e) {
    write_app_log('currency', 'api/currency.php error: ' . $e->getMessage());
    json_response(['ok' => false, 'message' => 'Unable to switch display currency right now.'], 500);
}
