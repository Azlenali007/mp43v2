<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';

ensure_installed();
$user = current_user();
if (!$user) {
    json_response(['ok' => false, 'message' => 'Authentication required.'], 401);
}
verify_csrf_or_abort(true);

$input = json_decode((string) file_get_contents('php://input'), true) ?: $_POST;
$action = $input['action'] ?? '';
$paymentId = validate_positive_int($input['payment_id'] ?? null);
if (!$paymentId) {
    json_response(['ok' => false, 'message' => 'Invalid payment reference.'], 422);
}

$pdo = Database::getConnection();
if ($action === 'cancel_rzp') {
    $pdo->prepare('UPDATE payments SET status = "cancelled" WHERE id = ? AND user_id = ? AND status = "created" AND credited = 0')
        ->execute([$paymentId, (int) $user['id']]);
    json_response(['ok' => true]);
} elseif ($action === 'fail_rzp') {
    $reason = sanitize_string($input['reason'] ?? 'Checkout failure', 250);
    $pdo->prepare('UPDATE payments SET status = "failed", admin_note = ? WHERE id = ? AND user_id = ? AND status = "created" AND credited = 0')
        ->execute([$reason, $paymentId, (int) $user['id']]);
    json_response(['ok' => true]);
}

json_response(['ok' => false, 'message' => 'Unsupported action.'], 400);
