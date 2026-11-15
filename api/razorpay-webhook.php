<?php
declare(strict_types=1);
ob_start();
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/payment-functions.php';

try {
    ensure_installed();

    $rawBody = (string) file_get_contents('php://input');
    $signature = (string) ($_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '');
    $eventId = (string) ($_SERVER['HTTP_X_RAZORPAY_EVENT_ID'] ?? '');

    $rzpInfo = Razorpay::fromDatabase();
    if (!$rzpInfo || $rzpInfo['webhook_secret'] === '') {
        json_response(['ok' => false, 'message' => 'Webhook secret is not configured.'], 400);
    }

    if (!Razorpay::verifyWebhookSignature($rawBody, $signature, $rzpInfo['webhook_secret'])) {
        write_app_log('razorpay-webhook', 'Rejected webhook: invalid signature.');
        json_response(['ok' => false, 'message' => 'Invalid webhook signature.'], 400);
    }

    $payload = json_decode($rawBody, true);
    if (!is_array($payload)) {
        json_response(['ok' => false, 'message' => 'Invalid JSON payload.'], 400);
    }

    $eventType = (string) ($payload['event'] ?? 'unknown');
    $entity = $payload['payload']['payment']['entity'] ?? ($payload['payload']['order']['entity'] ?? []);
    $rzpOrderId = (string) ($entity['order_id'] ?? ($payload['payload']['order']['entity']['id'] ?? ''));
    $rzpPaymentId = (string) ($payload['payload']['payment']['entity']['id'] ?? '');
    if ($eventId === '') {
        $eventId = sha1($rawBody);
    }

    $pdo = Database::getConnection();

    $check = $pdo->prepare('SELECT id, processed FROM payment_webhook_events WHERE event_id = ? LIMIT 1');
    $check->execute([$eventId]);
    $existing = $check->fetch();
    if ($existing && (int) $existing['processed'] === 1) {
        json_response(['ok' => true, 'duplicate' => true]);
    }

    if (!$existing) {
        $ins = $pdo->prepare(
            'INSERT INTO payment_webhook_events (event_id, event_type, razorpay_order_id, razorpay_payment_id, payload_json, signature, verified, processed)
             VALUES (?, ?, ?, ?, ?, ?, 1, 0)'
        );
        $ins->execute([$eventId, $eventType, $rzpOrderId ?: null, $rzpPaymentId ?: null, $rawBody, $signature]);
        $webhookDbId = (int) $pdo->lastInsertId();
    } else {
        $webhookDbId = (int) $existing['id'];
    }

    $note = 'No matching action';
    if (in_array($eventType, ['payment.captured', 'order.paid'], true) && $rzpOrderId !== '') {
        $pStmt = $pdo->prepare('SELECT * FROM payments WHERE razorpay_order_id = ? LIMIT 1');
        $pStmt->execute([$rzpOrderId]);
        $payment = $pStmt->fetch();

        if ($payment) {
            $creditRes = Payment::creditVerifiedPayment((int) $payment['id'], $rzpPaymentId ?: null, null, null, 'Verified via Razorpay webhook (' . $eventType . ')');
            $note = $creditRes['ok'] ? (!empty($creditRes['duplicate']) ? 'Already credited' : 'Credited via webhook') : $creditRes['error'];
        } else {
            $note = 'Payment record not found for order ' . $rzpOrderId;
        }
    } elseif ($eventType === 'payment.failed' && $rzpOrderId !== '') {
        $pdo->prepare('UPDATE payments SET status = "failed", razorpay_payment_id = COALESCE(?, razorpay_payment_id) WHERE razorpay_order_id = ? AND credited = 0')
            ->execute([$rzpPaymentId ?: null, $rzpOrderId]);
        $note = 'Marked failed via webhook';
    }

    $pdo->prepare('UPDATE payment_webhook_events SET processed = 1, processing_note = ? WHERE id = ?')->execute([substr($note, 0, 255), $webhookDbId]);

    json_response(['ok' => true, 'note' => $note]);
} catch (Throwable $e) {
    write_app_log('razorpay-webhook', 'Webhook exception: ' . $e->getMessage());
    json_response(['ok' => false, 'message' => 'Webhook processing error.'], 500);
}
