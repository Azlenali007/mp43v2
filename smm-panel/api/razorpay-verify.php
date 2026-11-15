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
if (!$rzpInfo || !$rzpInfo['configured'] || !$rzpInfo['client']) {
    json_response(['ok' => false, 'message' => 'Razorpay configuration missing.'], 400);
}

$input = json_decode((string) file_get_contents('php://input'), true) ?: $_POST;
$paymentDbId = validate_positive_int($input['payment_id'] ?? null);
$rzpOrderId = trim((string) ($input['razorpay_order_id'] ?? ''));
$rzpPaymentId = trim((string) ($input['razorpay_payment_id'] ?? ''));
$rzpSignature = trim((string) ($input['razorpay_signature'] ?? ''));

if (!$paymentDbId || $rzpOrderId === '' || $rzpPaymentId === '' || $rzpSignature === '') {
    json_response(['ok' => false, 'message' => 'Incomplete Razorpay verification payload.'], 422);
}

$pdo = Database::getConnection();
$stmt = $pdo->prepare('SELECT * FROM payments WHERE id = ? AND user_id = ? AND razorpay_order_id = ? LIMIT 1');
$stmt->execute([$paymentDbId, (int) $user['id'], $rzpOrderId]);
$payment = $stmt->fetch();

if (!$payment) {
    json_response(['ok' => false, 'message' => 'Matching payment record not found.'], 404);
}

// 1. Verify HMAC-SHA256 payment signature using Key Secret
if (!$rzpInfo['client']->verifyPaymentSignature($rzpOrderId, $rzpPaymentId, $rzpSignature)) {
    $pdo->prepare('UPDATE payments SET status = "failed", admin_note = "Signature verification failed" WHERE id = ? AND credited = 0')->execute([$paymentDbId]);
    json_response(['ok' => false, 'message' => 'Payment signature verification failed.'], 400);
}

// 2. Independently verify payment status & amount directly with Razorpay API
$apiPayRes = $rzpInfo['client']->fetchPayment($rzpPaymentId);
if (!$apiPayRes['ok']) {
    json_response(['ok' => false, 'message' => 'Unable to verify payment with Razorpay API: ' . $apiPayRes['error']], 502);
}

$apiPayment = $apiPayRes['data'];
$expectedPaise = (int) round((float) $payment['amount'] * 100);
$apiStatus = strtolower((string) ($apiPayment['status'] ?? ''));

if (($apiPayment['order_id'] ?? '') !== $rzpOrderId || (int) ($apiPayment['amount'] ?? 0) !== $expectedPaise) {
    json_response(['ok' => false, 'message' => 'Razorpay payment order or amount mismatch.'], 400);
}

if (!in_array($apiStatus, ['captured', 'authorized'], true)) {
    $pdo->prepare('UPDATE payments SET status = "failed", razorpay_payment_id = ? WHERE id = ? AND credited = 0')->execute([$rzpPaymentId, $paymentDbId]);
    json_response(['ok' => false, 'message' => 'Payment status on Razorpay is ' . $apiStatus . '. Wallet not credited.'], 400);
}

// 3. Atomically credit wallet with row locking & duplicate protection
$creditRes = Payment::creditVerifiedPayment($paymentDbId, $rzpPaymentId, $rzpSignature, null, 'Verified via Razorpay API (' . $apiStatus . ')');
if (!$creditRes['ok']) {
    json_response(['ok' => false, 'message' => $creditRes['error']], 400);
}

log_activity('user', (int) $user['id'], 'razorpay_payment_verified', 'payment', $paymentDbId, "Razorpay Payment: {$rzpPaymentId}");
json_response([
    'ok'      => true,
    'message' => 'Payment verified and ' . format_currency($payment['amount']) . ' credited to your wallet!',
]);
