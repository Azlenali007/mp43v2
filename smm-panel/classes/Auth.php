<?php
declare(strict_types=1);

class Auth
{
    public static function registerUser(string $name, string $username, string $email, string $password, string $phone = ''): array
    {
        $pdo = Database::getConnection();

        $check = $pdo->prepare('SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1');
        $check->execute([$email, $username]);
        if ($check->fetch()) {
            return ['ok' => false, 'error' => 'An account with that email or username already exists.'];
        }

        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $apiKey = bin2hex(random_bytes(24));

        $stmt = $pdo->prepare(
            'INSERT INTO users (name, username, email, password_hash, phone, api_key, status)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$name, $username, $email, $hash, $phone ?: null, $apiKey, 'active']);
        $userId = (int) $pdo->lastInsertId();

        log_activity('user', $userId, 'user_registered', 'user', $userId, 'New user account created');
        create_notification($userId, 'Welcome to ' . get_setting('site_name', 'CrownSMM'), 'Your account is active. Add funds to launch your first campaign.', 'success', 'add-funds.php');

        return ['ok' => true, 'user_id' => $userId];
    }

    public static function loginUser(string $identifier, string $password): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? OR username = ? LIMIT 1');
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return ['ok' => false, 'error' => 'Invalid email/username or password.'];
        }

        if ($user['status'] === 'blocked') {
            return ['ok' => false, 'error' => 'Your account has been suspended. Please contact support.'];
        }
        if ($user['status'] !== 'active') {
            return ['ok' => false, 'error' => 'Your account is currently inactive.'];
        }

        app_start_session();
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];

        $upd = $pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?');
        $upd->execute([(int) $user['id']]);
        log_activity('user', (int) $user['id'], 'user_login', 'user', (int) $user['id']);

        return ['ok' => true, 'user' => $user];
    }

    public static function logoutUser(): void
    {
        app_start_session();
        unset($_SESSION['user_id']);
        session_regenerate_id(true);
    }

    public static function loginAdmin(string $email, string $password): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM admins WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if (!$admin || !password_verify($password, $admin['password_hash'])) {
            return ['ok' => false, 'error' => 'Invalid administrator credentials.'];
        }

        if ($admin['status'] !== 'active') {
            return ['ok' => false, 'error' => 'This administrator account is disabled.'];
        }

        app_start_session();
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int) $admin['id'];

        $upd = $pdo->prepare('UPDATE admins SET last_login_at = NOW() WHERE id = ?');
        $upd->execute([(int) $admin['id']]);
        log_activity('admin', (int) $admin['id'], 'admin_login', 'admin', (int) $admin['id']);

        return ['ok' => true, 'admin' => $admin];
    }

    public static function logoutAdmin(): void
    {
        app_start_session();
        unset($_SESSION['admin_id']);
        session_regenerate_id(true);
    }
}
