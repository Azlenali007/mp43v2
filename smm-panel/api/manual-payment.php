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

$minAmt = (float) $method['min_amount'];
$maxAmt = (float) $method['max_amount'];
$amount = validate_positive_float($_POST['amount'] ?? null, $minAmt, $maxAmt);
$utr = sanitize_string($_POST['utr_reference'] ?? '', 120);

if ($amount === null) {
    json_response(['ok' => false, 'message' => "Amount must be between ₹{$minAmt} and ₹{$maxAmt}."], 422);
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

$pdo = Database::getConnection();
$paymentCode = generate_code('MAN', 4);
$ins = $pdo->prepare(
    'INSERT INTO payments (payment_code, user_id, method_code, amount, currency, status, utr_reference, screenshot_path, credited)
     VALUES (?, ?, "manual", ?, "INR", "pending", ?, ?, 0)'
);
$ins->execute([$paymentCode, (int) $user['id'], round($amount, 2), $utr, $upload['path']]);
$paymentId = (int) $pdo->lastInsertId();

log_activity('user', (int) $user['id'], 'manual_payment_submitted', 'payment', $paymentId, "UTR: {$utr}, Amount: {$amount}");
json_response([
    'ok'      => true,
    'message' => "Payment request #{$paymentCode} submitted as Pending. Your wallet will be credited after administrator verification.",
]);
