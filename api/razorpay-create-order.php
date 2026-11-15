<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/payment-functions.php';

ensure_installed();
$user = current_user();
if (!$user) {
    json_response(['ok' => false, 'message' => 'Authentication required.'], 401);
}
verify_csrf_or_abort(true);

$rzpInfo = Razorpay::fromDatabase();
if (!$rzpInfo || !$rzpInfo['is_enabled'] || !$rzpInfo['configured'] || !$rzpInfo['client']) {
    json_response(['ok' => false, 'message' => 'Razorpay is not currently enabled or configured.'], 400);
}

$input = json_decode((string) file_get_contents('php://input'), true) ?: $_POST;
$displayCur = Currency::getUserDisplay($user);
$displayCode = strtoupper(trim((string) ($input['currency'] ?? $displayCur['code'])));
$curRow = Currency::find($displayCode);
if (!$curRow || (int) $curRow['is_enabled'] !== 1 || (float) ($curRow['exchange_rate'] ?? 0) <= 0) {
    $displayCode = (string) $displayCur['code'];
}

$rawAmount = validate_positive_float($input['amount'] ?? null, 0.01, 10000000.0);
if ($rawAmount === null) {
    json_response(['ok' => false, 'message' => 'Enter a valid positive deposit amount.'], 422);
}

$baseCode = Currency::getBaseCode();
$convBase = Currency::convertStrict($rawAmount, $displayCode, $baseCode, 4);
if (!$convBase['ok']) {
    json_response(['ok' => false, 'message' => $convBase['error']], 422);
}
$baseAmount = round((float) $convBase['amount'], 2);
$exRateToBase = (float) $convBase['cross_rate'];

$minBase = max(1.0, (float) $rzpInfo['method']['min_amount']);
$maxBase = (float) $rzpInfo['method']['max_amount'];
if ($baseAmount < $minBase || $baseAmount > $maxBase) {
    $minDisp = Currency::formatBase($minBase, $displayCode);
    $maxDisp = Currency::formatBase($maxBase, $displayCode);
    json_response(['ok' => false, 'message' => "Deposit amount must be between {$minDisp} and {$maxDisp}."], 422);
}

// Convert to Razorpay supported gateway currency (INR) cleanly
$gatewayCurrency = 'INR';
$convInr = Currency::convertStrict($baseAmount, $baseCode, $gatewayCurrency, 2);
if (!$convInr['ok']) {
    json_response(['ok' => false, 'message' => 'Unable to convert deposit into Razorpay INR: ' . $convInr['error']], 422);
}
$gatewayAmountInr = round((float) $convInr['amount'], 2);

$paymentCode = generate_code('PAY', 4);
$orderRes = $rzpInfo['client']->createOrder($gatewayAmountInr, $paymentCode, [
    'user_id'           => (string) $user['id'],
    'payment_code'      => $paymentCode,
    'original_currency' => $displayCode,
    'base_currency'     => $baseCode,
]);

if (!$orderRes['ok']) {
    json_response(['ok' => false, 'message' => 'Razorpay Order Error: ' . $orderRes['error']], 502);
}

$rzpOrder = $orderRes['data'];
$pdo = Database::getConnection();
$ins = $pdo->prepare(
    'INSERT INTO payments
     (payment_code, user_id, method_code, amount, currency,
      original_amount, original_currency, base_amount, base_currency,
      exchange_rate, display_currency, displayed_amount,
      gateway_amount, gateway_currency, status, razorpay_order_id)
     VALUES (?, ?, "razorpay", ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "created", ?)'
);
$ins->execute([
    $paymentCode,
    (int) $user['id'],
    $baseAmount,
    $baseCode,
    round($rawAmount, 4),
    $displayCode,
    $baseAmount,
    $baseCode,
    $exRateToBase,
    $displayCode,
    round($rawAmount, 4),
    $gatewayAmountInr,
    $gatewayCurrency,
    $rzpOrder['id'],
]);
$paymentDbId = (int) $pdo->lastInsertId();

if ($displayCode !== $baseCode) {
    Currency::logConversion('razorpay_checkout', $displayCode, $baseCode, $rawAmount, $baseAmount, $exRateToBase, $paymentDbId);
}

json_response([
    'ok'                 => true,
    'payment_db_id'      => $paymentDbId,
    'payment_code'       => $paymentCode,
    'razorpay_order_id'  => $rzpOrder['id'],
    'amount_paise'       => (int) $rzpOrder['amount'],
    'currency'           => 'INR',
    'gateway_amount_inr' => $gatewayAmountInr,
    'base_amount'        => $baseAmount,
    'base_currency'      => $baseCode,
    'key_id'             => $rzpInfo['key_id'],
]);
