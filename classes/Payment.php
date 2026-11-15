<?php
declare(strict_types=1);

class Payment
{
    public static function getMethods(bool $onlyEnabled = false): array
    {
        $pdo = Database::getConnection();
        $where = $onlyEnabled ? 'WHERE is_enabled = 1' : '';
        return $pdo->query("SELECT * FROM payment_methods $where ORDER BY id ASC")->fetchAll();
    }

    public static function getMethod(string $code): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM payment_methods WHERE code = ? LIMIT 1');
        $stmt->execute([$code]);
        $row = $stmt->fetch();
        if ($row) {
            $row['config'] = json_decode((string) ($row['config_json'] ?? '{}'), true) ?: [];
        }
        return $row ?: null;
    }

    public static function creditVerifiedPayment(int $paymentId, ?string $razorpayPaymentId = null, ?string $razorpaySignature = null, ?int $adminId = null, string $note = ''): array
    {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            $lock = $pdo->prepare('SELECT * FROM payments WHERE id = ? FOR UPDATE');
            $lock->execute([$paymentId]);
            $payment = $lock->fetch();
            if (!$payment) {
                throw new RuntimeException('Payment record not found.');
            }

            if ((int) $payment['credited'] === 1) {
                $pdo->commit();
                return ['ok' => true, 'duplicate' => true, 'message' => 'Payment already credited.'];
            }

            if ($razorpayPaymentId !== null) {
                $dupRzp = $pdo->prepare('SELECT id FROM payments WHERE razorpay_payment_id = ? AND id != ? LIMIT 1');
                $dupRzp->execute([$razorpayPaymentId, $paymentId]);
                if ($dupRzp->fetch()) {
                    throw new RuntimeException('Duplicate Razorpay payment ID detected.');
                }
            }

            $baseAmount = (float) ($payment['base_amount'] > 0 ? $payment['base_amount'] : $payment['amount']);
            $baseCur = (string) ($payment['base_currency'] ?: Currency::getBaseCode());
            $origAmt = (float) ($payment['original_amount'] > 0 ? $payment['original_amount'] : $baseAmount);
            $origCur = (string) ($payment['original_currency'] ?: $baseCur);
            $dispAmt = (float) ($payment['displayed_amount'] > 0 ? $payment['displayed_amount'] : $origAmt);
            $dispCur = (string) ($payment['display_currency'] ?: $origCur);
            $exRate = (float) ($payment['exchange_rate'] > 0 ? $payment['exchange_rate'] : 1.0);

            $credit = Wallet::credit(
                (int) $payment['user_id'],
                $baseAmount,
                'payment',
                $paymentId,
                'Wallet top-up via ' . strtoupper($payment['method_code']) . ' (#' . $payment['payment_code'] . ' · ' . Currency::formatExact($origAmt, $origCur) . ')',
                'payment_credit_' . $paymentId,
                $adminId,
                'credit',
                [
                    'original_amount'   => $origAmt,
                    'original_currency' => $origCur,
                    'display_currency'  => $dispCur,
                    'exchange_rate'     => $exRate,
                    'displayed_amount'  => $dispAmt,
                ]
            );
            if (!$credit['ok']) {
                throw new RuntimeException($credit['error']);
            }

            $upd = $pdo->prepare(
                'UPDATE payments SET status = "paid", credited = 1, credited_at = NOW(),
                 razorpay_payment_id = COALESCE(?, razorpay_payment_id),
                 razorpay_signature = COALESCE(?, razorpay_signature),
                 reviewed_by = COALESCE(?, reviewed_by),
                 reviewed_at = CASE WHEN ? IS NOT NULL THEN NOW() ELSE reviewed_at END,
                 admin_note = CASE WHEN ? != "" THEN ? ELSE admin_note END
                 WHERE id = ?'
            );
            $upd->execute([
                $razorpayPaymentId,
                $razorpaySignature,
                $adminId,
                $adminId,
                $note,
                $note,
                $paymentId,
            ]);

            $pdo->commit();

            create_notification(
                (int) $payment['user_id'],
                'Funds Credited',
                Currency::formatExact($dispAmt, $dispCur) . ' (' . Currency::formatExact($baseAmount, $baseCur) . ') has been added to your wallet (' . $payment['payment_code'] . ').',
                'success',
                'transactions.php'
            );

            return ['ok' => true, 'duplicate' => false, 'balance_after' => $credit['balance_after']];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
}
