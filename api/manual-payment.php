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

$method = Payment::getMethod('manual');
if (!$method || (int) $method['is_enabled'] !== 1) {
    json_response(['ok' => false, 'message' => 'Manual payment is currently disabled.'], 400);
}

$cfg = $method['config'] ?? [];
$acceptedCur = strtoupper(trim((string) ($cfg['accepted_currency'] ?? Currency::getBaseCode())));
$submittedCur = strtoupper(trim((string) ($_POST['currency'] ?? $acceptedCur)));
if ($submittedCur !== $acceptedCur) {
    $submittedCur = $acceptedCur;
}

$rawAmount = validate_positive_float($_POST['amount'] ?? null, 0.01, 10000000.0);
$utr = sanitize_string($_POST['utr_reference'] ?? '', 120);

if ($rawAmount === null) {
    json_response(['ok' => false, 'message' => 'Please enter a valid positive deposit amount.'], 422);
}

$baseCode = Currency::getBaseCode();
$convBase = Currency::convertStrict($rawAmount, $submittedCur, $baseCode, 4);
if (!$convBase['ok']) {
    json_response(['ok' => false, 'message' => $convBase['error']], 422);
}
$baseAmount = round((float) $convBase['amount'], 2);
$exRateToBase = (float) $convBase['cross_rate'];

$minBase = (float) $method['min_amount'];
$maxBase = (float) $method['max_amount'];
if ($baseAmount < $minBase || $baseAmount > $maxBase) {
    json_response(['ok' => false, 'message' => 'Amount must be equivalent to ' . Currency::formatExact($minBase, $baseCode) . ' – ' . Currency::formatExact($maxBase, $baseCode) . '.'], 422);
}

if ($utr === '' || strlen($utr) < 4) {
    json_response(['ok' => false, 'message' => 'Please enter a valid UTR or transaction reference number.'], 422);
}
if (empty($_FILES['screenshot']['name'])) {
    json_response(['ok' => false, 'message' => 'Payment screenshot upload is required.'], 422);
}

$upload = validate_and_store_upload($_FILES['screenshot'], 'payments');
if (!$upload['ok']) {
    json_response(['ok' => false, 'message' => $upload['error']], 422);
}

$displayCur = Currency::getUserDisplay($user);
$dispCode = (string) $displayCur['code'];
$convDisp = Currency::convertStrict($baseAmount, $baseCode, $dispCode, 4);
$displayedAmount = $convDisp['ok'] ? round((float) $convDisp['amount'], 2) : $baseAmount;

$pdo = Database::getConnection();
$paymentCode = generate_code('MAN', 4);
$ins = $pdo->prepare(
    'INSERT INTO payments
     (payment_code, user_id, method_code, amount, currency,
      original_amount, original_currency, base_amount, base_currency,
      exchange_rate, display_currency, displayed_amount,
      gateway_amount, gateway_currency, status, utr_reference, screenshot_path, credited)
     VALUES (?, ?, "manual", ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "pending", ?, ?, 0)'
);
$ins->execute([
    $paymentCode,
    (int) $user['id'],
    $baseAmount,
    $baseCode,
    round($rawAmount, 4),
    $submittedCur,
    $baseAmount,
    $baseCode,
    $exRateToBase,
    $dispCode,
    $displayedAmount,
    round($rawAmount, 2),
    $submittedCur,
    $utr,
    $upload['path'],
]);
$paymentId = (int) $pdo->lastInsertId();

if ($submittedCur !== $baseCode) {
    Currency::logConversion('manual_payment', $submittedCur, $baseCode, $rawAmount, $baseAmount, $exRateToBase, $paymentId);
}

log_activity('user', (int) $user['id'], 'manual_payment_submitted', 'payment', $paymentId, "UTR: {$utr}, Orig: {$rawAmount} {$submittedCur}, Base: {$baseAmount} {$baseCode}");
json_response([
    'ok'      => true,
    'message' => "Payment request #{$paymentCode} (" . Currency::formatExact($rawAmount, $submittedCur) . ") submitted as Pending. Wallet will be credited " . Currency::formatExact($baseAmount, $baseCode) . " after verification.",
]);
