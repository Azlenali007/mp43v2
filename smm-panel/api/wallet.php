<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';

ensure_installed();
$user = current_user();
if (!$user) {
    json_response(['ok' => false, 'message' => 'Authentication required.'], 401);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();
    $action = $_POST['action'] ?? '';
    if ($action === 'mark_notifications_read') {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?');
        $stmt->execute([(int) $user['id']]);
        redirect($_SERVER['HTTP_REFERER'] ?? '../dashboard.php');
    }
}

json_response([
    'ok'             => true,
    'wallet_balance' => (float) $user['wallet_balance'],
    'currency'       => get_setting('currency_code', 'INR'),
]);
