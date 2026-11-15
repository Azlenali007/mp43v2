<?php
declare(strict_types=1);

class User
{
    public static function find(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function findByApiKey(string $apiKey): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE api_key = ? AND status = "active" LIMIT 1');
        $stmt->execute([$apiKey]);
        return $stmt->fetch() ?: null;
    }

    public static function regenerateApiKey(int $userId): string
    {
        $pdo = Database::getConnection();
        $newKey = bin2hex(random_bytes(24));
        $stmt = $pdo->prepare('UPDATE users SET api_key = ? WHERE id = ?');
        $stmt->execute([$newKey, $userId]);
        log_activity('user', $userId, 'api_key_regenerated', 'user', $userId);
        return $newKey;
    }

    /**
     * Save user profile details & preferred display currency safely.
     * Automatically ensures schema columns exist if running on an older database.
     */
    public static function updateProfile(
        int $userId,
        string $name,
        string $phone,
        string $timezone,
        ?string $preferredCurrency = null
    ): array {
        $pdo = Database::getConnection();
        $cleanTz = trim($timezone) !== '' ? trim($timezone) : 'Asia/Kolkata';
        $cleanPhone = trim($phone) !== '' ? trim($phone) : null;
        $normCur = ($preferredCurrency !== null && trim($preferredCurrency) !== '')
            ? strtoupper(trim($preferredCurrency))
            : null;

        if ($normCur !== null && $normCur !== '__DEFAULT__') {
            $curRow = Currency::find($normCur);
            if (!$curRow || (int) ($curRow['is_enabled'] ?? 0) !== 1) {
                return ['ok' => false, 'error' => 'Selected currency is not available or is disabled.'];
            }
            if ((float) ($curRow['exchange_rate'] ?? 0) <= 0) {
                return ['ok' => false, 'error' => "Currency {$normCur} does not have a valid exchange rate configured yet."];
            }
        } elseif ($normCur === '__DEFAULT__') {
            $normCur = null;
        }

        try {
            if ($preferredCurrency !== null) {
                $stmt = $pdo->prepare('UPDATE users SET name = ?, phone = ?, timezone = ?, preferred_currency = ? WHERE id = ?');
                $stmt->execute([$name, $cleanPhone, $cleanTz, $normCur, $userId]);
            } else {
                $stmt = $pdo->prepare('UPDATE users SET name = ?, phone = ?, timezone = ? WHERE id = ?');
                $stmt->execute([$name, $cleanPhone, $cleanTz, $userId]);
            }
            log_activity('user', $userId, 'profile_updated', 'user', $userId, $normCur ? ('currency=' . $normCur) : null);
            return ['ok' => true, 'currency' => $normCur];
        } catch (PDOException $e) {
            write_app_log('profile', 'User::updateProfile PDO error: ' . $e->getMessage());
            // Auto-heal missing column on pre-upgrade installations and retry once
            if (str_contains($e->getMessage(), 'preferred_currency') && class_exists('Migrator')) {
                Migrator::ensureUpgraded($pdo);
                try {
                    $stmt = $pdo->prepare('UPDATE users SET name = ?, phone = ?, timezone = ?, preferred_currency = ? WHERE id = ?');
                    $stmt->execute([$name, $cleanPhone, $cleanTz, $normCur, $userId]);
                    return ['ok' => true, 'currency' => $normCur];
                } catch (Throwable $retryErr) {
                    write_app_log('profile', 'User::updateProfile retry failed: ' . $retryErr->getMessage());
                }
            }
            return ['ok' => false, 'error' => 'Unable to save profile settings right now. Please try again.'];
        } catch (Throwable $e) {
            write_app_log('profile', 'User::updateProfile error: ' . $e->getMessage());
            return ['ok' => false, 'error' => 'Unable to save profile settings right now. Please try again.'];
        }
    }

    public static function updatePreferredCurrency(int $userId, string $currencyCode): array
    {
        $pdo = Database::getConnection();
        $code = strtoupper(trim($currencyCode));
        $curRow = Currency::find($code);
        if (!$curRow || (int) ($curRow['is_enabled'] ?? 0) !== 1) {
            return ['ok' => false, 'error' => 'Selected currency is not enabled.'];
        }
        if ((float) ($curRow['exchange_rate'] ?? 0) <= 0) {
            return ['ok' => false, 'error' => "Currency {$code} is enabled, but its exchange rate is not synced yet. Ask an administrator to sync exchange rates."];
        }

        try {
            $stmt = $pdo->prepare('UPDATE users SET preferred_currency = ? WHERE id = ?');
            $stmt->execute([$code, $userId]);
            log_activity('user', $userId, 'currency_preference_updated', 'user', $userId, $code);
            return ['ok' => true, 'currency' => $code];
        } catch (PDOException $e) {
            write_app_log('profile', 'User::updatePreferredCurrency PDO error: ' . $e->getMessage());
            if (str_contains($e->getMessage(), 'preferred_currency') && class_exists('Migrator')) {
                Migrator::ensureUpgraded($pdo);
                try {
                    $stmt = $pdo->prepare('UPDATE users SET preferred_currency = ? WHERE id = ?');
                    $stmt->execute([$code, $userId]);
                    return ['ok' => true, 'currency' => $code];
                } catch (Throwable $retryErr) {
                    write_app_log('profile', 'User::updatePreferredCurrency retry failed: ' . $retryErr->getMessage());
                }
            }
            return ['ok' => false, 'error' => 'Failed to persist currency preference.'];
        } catch (Throwable $e) {
            write_app_log('profile', 'User::updatePreferredCurrency error: ' . $e->getMessage());
            return ['ok' => false, 'error' => 'Failed to persist currency preference.'];
        }
    }

    public static function changePassword(int $userId, string $currentPassword, string $newPassword): array
    {
        $pdo = Database::getConnection();
        $user = self::find($userId);
        if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
            return ['ok' => false, 'error' => 'Current password is incorrect.'];
        }
        $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmt->execute([$hash, $userId]);
        log_activity('user', $userId, 'password_changed', 'user', $userId);
        return ['ok' => true];
    }
}
