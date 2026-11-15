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

    public static function updateProfile(int $userId, string $name, string $phone, string $timezone, ?string $preferredCurrency = null): void
    {
        $pdo = Database::getConnection();
        if ($preferredCurrency !== null && $preferredCurrency !== '') {
            $stmt = $pdo->prepare('UPDATE users SET name = ?, phone = ?, timezone = ?, preferred_currency = ? WHERE id = ?');
            $stmt->execute([$name, $phone ?: null, $timezone, strtoupper($preferredCurrency), $userId]);
        } else {
            $stmt = $pdo->prepare('UPDATE users SET name = ?, phone = ?, timezone = ? WHERE id = ?');
            $stmt->execute([$name, $phone ?: null, $timezone, $userId]);
        }
        log_activity('user', $userId, 'profile_updated', 'user', $userId);
    }

    public static function updatePreferredCurrency(int $userId, string $currencyCode): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE users SET preferred_currency = ? WHERE id = ?');
        $stmt->execute([strtoupper(trim($currencyCode)), $userId]);
        log_activity('user', $userId, 'currency_preference_updated', 'user', $userId, $currencyCode);
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
