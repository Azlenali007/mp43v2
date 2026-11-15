<?php
declare(strict_types=1);

class Wallet
{
    private static function resolveSnapshot(int $userId, float $baseAmount, array $opts = []): array
    {
        $baseCode = Currency::getBaseCode();
        $origAmount = isset($opts['original_amount']) ? (float) $opts['original_amount'] : $baseAmount;
        $origCurrency = !empty($opts['original_currency']) ? strtoupper((string) $opts['original_currency']) : $baseCode;

        $displayCode = !empty($opts['display_currency']) ? strtoupper((string) $opts['display_currency']) : null;
        $exRate = isset($opts['exchange_rate']) ? (float) $opts['exchange_rate'] : null;
        $dispAmt = isset($opts['displayed_amount']) ? (float) $opts['displayed_amount'] : null;

        if ($displayCode === null) {
            $pdo = Database::getConnection();
            $uStmt = $pdo->prepare('SELECT preferred_currency FROM users WHERE id = ? LIMIT 1');
            $uStmt->execute([$userId]);
            $uRow = $uStmt->fetch() ?: [];
            $dispCur = Currency::getUserDisplay($uRow);
            $displayCode = (string) $dispCur['code'];
        }

        if ($exRate === null || $dispAmt === null) {
            $conv = Currency::convertStrict($baseAmount, $baseCode, $displayCode, 4);
            if ($conv['ok']) {
                $exRate = (float) $conv['cross_rate'];
                $dispAmt = (float) $conv['amount'];
            } else {
                $displayCode = $baseCode;
                $exRate = 1.0;
                $dispAmt = $baseAmount;
            }
        }

        return [
            'original_amount'   => round($origAmount, 4),
            'original_currency' => $origCurrency,
            'base_currency'     => $baseCode,
            'exchange_rate'     => $exRate,
            'display_currency'  => $displayCode,
            'displayed_amount'  => round($dispAmt, 4),
        ];
    }

    public static function credit(
        int $userId,
        float $amount,
        string $referenceType,
        ?int $referenceId,
        string $description,
        ?string $idempotencyKey = null,
        ?int $adminId = null,
        string $txType = 'credit',
        array $currencySnapshot = []
    ): array {
        if ($amount <= 0) {
            return ['ok' => false, 'error' => 'Credit amount must be positive.'];
        }

        $pdo = Database::getConnection();
        $startedTx = !$pdo->inTransaction();
        if ($startedTx) {
            $pdo->beginTransaction();
        }

        try {
            if ($idempotencyKey !== null) {
                $dup = $pdo->prepare('SELECT id, balance_after FROM wallet_transactions WHERE idempotency_key = ? LIMIT 1');
                $dup->execute([$idempotencyKey]);
                $existing = $dup->fetch();
                if ($existing) {
                    if ($startedTx) {
                        $pdo->commit();
                    }
                    return ['ok' => true, 'duplicate' => true, 'transaction_id' => (int) $existing['id'], 'balance_after' => (float) $existing['balance_after']];
                }
            }

            $lockStmt = $pdo->prepare('SELECT id, wallet_balance FROM users WHERE id = ? FOR UPDATE');
            $lockStmt->execute([$userId]);
            $user = $lockStmt->fetch();
            if (!$user) {
                throw new RuntimeException('User account not found.');
            }

            $before = (float) $user['wallet_balance'];
            $after = round($before + $amount, 4);
            $snap = self::resolveSnapshot($userId, $amount, $currencySnapshot);

            $upd = $pdo->prepare('UPDATE users SET wallet_balance = ? WHERE id = ?');
            $upd->execute([$after, $userId]);

            $ins = $pdo->prepare(
                'INSERT INTO wallet_transactions
                 (user_id, type, amount, balance_before, balance_after,
                  original_amount, original_currency, base_currency, exchange_rate, display_currency, displayed_amount,
                  reference_type, reference_id, idempotency_key, description, created_by_admin)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $ins->execute([
                $userId,
                $txType,
                $amount,
                $before,
                $after,
                $snap['original_amount'],
                $snap['original_currency'],
                $snap['base_currency'],
                $snap['exchange_rate'],
                $snap['display_currency'],
                $snap['displayed_amount'],
                $referenceType,
                $referenceId,
                $idempotencyKey,
                $description,
                $adminId,
            ]);
            $txId = (int) $pdo->lastInsertId();

            if ($startedTx) {
                $pdo->commit();
            }

            return [
                'ok'             => true,
                'duplicate'      => false,
                'transaction_id' => $txId,
                'balance_before' => $before,
                'balance_after'  => $after,
                'snapshot'       => $snap,
            ];
        } catch (Throwable $e) {
            if ($startedTx && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    public static function debit(
        int $userId,
        float $amount,
        string $referenceType,
        ?int $referenceId,
        string $description,
        ?string $idempotencyKey = null,
        ?int $adminId = null,
        bool $addToSpent = false,
        array $currencySnapshot = []
    ): array {
        if ($amount <= 0) {
            return ['ok' => false, 'error' => 'Debit amount must be positive.'];
        }

        $pdo = Database::getConnection();
        $startedTx = !$pdo->inTransaction();
        if ($startedTx) {
            $pdo->beginTransaction();
        }

        try {
            if ($idempotencyKey !== null) {
                $dup = $pdo->prepare('SELECT id, balance_after FROM wallet_transactions WHERE idempotency_key = ? LIMIT 1');
                $dup->execute([$idempotencyKey]);
                $existing = $dup->fetch();
                if ($existing) {
                    if ($startedTx) {
                        $pdo->commit();
                    }
                    return ['ok' => true, 'duplicate' => true, 'transaction_id' => (int) $existing['id'], 'balance_after' => (float) $existing['balance_after']];
                }
            }

            $lockStmt = $pdo->prepare('SELECT id, wallet_balance FROM users WHERE id = ? FOR UPDATE');
            $lockStmt->execute([$userId]);
            $user = $lockStmt->fetch();
            if (!$user) {
                throw new RuntimeException('User account not found.');
            }

            $before = (float) $user['wallet_balance'];
            if ($before + 0.00001 < $amount) {
                throw new RuntimeException('Insufficient wallet balance.');
            }
            $after = max(0.0, round($before - $amount, 4));
            $snap = self::resolveSnapshot($userId, $amount, $currencySnapshot);

            if ($addToSpent) {
                $upd = $pdo->prepare('UPDATE users SET wallet_balance = ?, spent_total = spent_total + ? WHERE id = ?');
                $upd->execute([$after, $amount, $userId]);
            } else {
                $upd = $pdo->prepare('UPDATE users SET wallet_balance = ? WHERE id = ?');
                $upd->execute([$after, $userId]);
            }

            $ins = $pdo->prepare(
                'INSERT INTO wallet_transactions
                 (user_id, type, amount, balance_before, balance_after,
                  original_amount, original_currency, base_currency, exchange_rate, display_currency, displayed_amount,
                  reference_type, reference_id, idempotency_key, description, created_by_admin)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $ins->execute([
                $userId,
                'debit',
                $amount,
                $before,
                $after,
                $snap['original_amount'],
                $snap['original_currency'],
                $snap['base_currency'],
                $snap['exchange_rate'],
                $snap['display_currency'],
                $snap['displayed_amount'],
                $referenceType,
                $referenceId,
                $idempotencyKey,
                $description,
                $adminId,
            ]);
            $txId = (int) $pdo->lastInsertId();

            if ($startedTx) {
                $pdo->commit();
            }

            return [
                'ok'             => true,
                'duplicate'      => false,
                'transaction_id' => $txId,
                'balance_before' => $before,
                'balance_after'  => $after,
                'snapshot'       => $snap,
            ];
        } catch (Throwable $e) {
            if ($startedTx && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
}
