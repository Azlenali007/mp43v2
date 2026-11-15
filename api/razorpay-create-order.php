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
$minAmt = max(1.0, (float) $rzpInfo['method']['min_amount']);
$maxAmt = (float) $rzpInfo['method']['max_amount'];
$amount = validate_positive_float($input['amount'] ?? null, $minAmt, $maxAmt);

if ($amount === null) {
    json_response(['ok' => false, 'message' => "Deposit amount must be between ₹{$minAmt} and ₹{$maxAmt}."], 422);
}

$amount = round($amount, 2);
$paymentCode = generate_code('PAY', 4);

$orderRes = $rzpInfo['client']->createOrder($amount, $paymentCode, [
    'user_id'      => (string) $user['id'],
    'payment_code' => $paymentCode,
]);

if (!$orderRes['ok']) {
    json_response(['ok' => false, 'message' => 'Razorpay Order Error: ' . $orderRes['error']], 502);
}

$rzpOrder = $orderRes['data'];
$pdo = Database::getConnection();
$ins = $pdo->prepare(
    'INSERT INTO payments (payment_code, user_id, method_code, amount, currency, status, razorpay_order_id)
     VALUES (?, ?, "razorpay", ?, "INR", "created", ?)'
);
$ins->execute([$paymentCode, (int) $user['id'], $amount, $rzpOrder['id']]);
$paymentDbId = (int) $pdo->lastInsertId();

json_response([
    'ok'                => true,
    'payment_db_id'     => $paymentDbId,
    'payment_code'      => $paymentCode,
    'razorpay_order_id' => $rzpOrder['id'],
    'amount_paise'      => (int) $rzpOrder['amount'],
    'currency'          => 'INR',
    'key_id'            => $rzpInfo['key_id'], // Public Key ID only
]);
