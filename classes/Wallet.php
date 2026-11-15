<?php
declare(strict_types=1);

class Wallet
{
    public static function credit(
        int $userId,
        float $amount,
        string $referenceType,
        ?int $referenceId,
        string $description,
        ?string $idempotencyKey = null,
        ?int $adminId = null,
        string $txType = 'credit'
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

            $upd = $pdo->prepare('UPDATE users SET wallet_balance = ? WHERE id = ?');
            $upd->execute([$after, $userId]);

            $ins = $pdo->prepare(
                'INSERT INTO wallet_transactions (user_id, type, amount, balance_before, balance_after, reference_type, reference_id, idempotency_key, description, created_by_admin)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $ins->execute([$userId, $txType, $amount, $before, $after, $referenceType, $referenceId, $idempotencyKey, $description, $adminId]);
            $txId = (int) $pdo->lastInsertId();

            if ($startedTx) {
                $pdo->commit();
            }

            return ['ok' => true, 'duplicate' => false, 'transaction_id' => $txId, 'balance_before' => $before, 'balance_after' => $after];
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
        bool $addToSpent = false
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

            if ($addToSpent) {
                $upd = $pdo->prepare('UPDATE users SET wallet_balance = ?, spent_total = spent_total + ? WHERE id = ?');
                $upd->execute([$after, $amount, $userId]);
            } else {
                $upd = $pdo->prepare('UPDATE users SET wallet_balance = ? WHERE id = ?');
                $upd->execute([$after, $userId]);
            }

            $ins = $pdo->prepare(
                'INSERT INTO wallet_transactions (user_id, type, amount, balance_before, balance_after, reference_type, reference_id, idempotency_key, description, created_by_admin)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $ins->execute([$userId, 'debit', $amount, $before, $after, $referenceType, $referenceId, $idempotencyKey, $description, $adminId]);
            $txId = (int) $pdo->lastInsertId();

            if ($startedTx) {
                $pdo->commit();
            }

            return ['ok' => true, 'duplicate' => false, 'transaction_id' => $txId, 'balance_before' => $before, 'balance_after' => $after];
        } catch (Throwable $e) {
            if ($startedTx && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
}
