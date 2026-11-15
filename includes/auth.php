<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/validation.php';
require_once __DIR__ . '/icons.php';
require_once __DIR__ . '/../classes/Currency.php';
require_once __DIR__ . '/../classes/Theme.php';
require_once __DIR__ . '/../classes/Tools.php';

function ensure_installed(): void
{
    if (!app_is_installed()) {
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        if (!str_contains($script, '/install/')) {
            $prefix = app_root_prefix();
            redirect($prefix . 'install/');
        }
    }
}

function current_user(bool $refresh = false): ?array
{
    app_start_session();
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    static $cachedUser = null;
    if (!$refresh && $cachedUser !== null && (int) $cachedUser['id'] === (int) $_SESSION['user_id']) {
        return $cachedUser;
    }
    try {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([(int) $_SESSION['user_id']]);
        $user = $stmt->fetch();
        if (!$user || $user['status'] !== 'active') {
            unset($_SESSION['user_id']);
            return null;
        }
        $cachedUser = $user;
        return $cachedUser;
    } catch (Throwable $e) {
        return null;
    }
}

function require_user(): array
{
    ensure_installed();
    $user = current_user();
    if (!$user) {
        set_flash('warning', 'Please sign in to access your account.');
        redirect(app_root_prefix() . 'login.php');
    }
    return $user;
}

function current_admin(): ?array
{
    app_start_session();
    if (empty($_SESSION['admin_id'])) {
        return null;
    }
    static $cachedAdmin = null;
    if ($cachedAdmin !== null && (int) $cachedAdmin['id'] === (int) $_SESSION['admin_id']) {
        return $cachedAdmin;
    }
    try {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT a.*, r.name AS role_name, r.slug AS role_slug
             FROM admins a
             INNER JOIN admin_roles r ON r.id = a.role_id
             WHERE a.id = ? LIMIT 1'
        );
        $stmt->execute([(int) $_SESSION['admin_id']]);
        $admin = $stmt->fetch();
        if (!$admin || $admin['status'] !== 'active') {
            unset($_SESSION['admin_id']);
            return null;
        }
        $permStmt = $pdo->prepare('SELECT permission_key FROM admin_permissions WHERE role_id = ?');
        $permStmt->execute([(int) $admin['role_id']]);
        $admin['permissions'] = array_column($permStmt->fetchAll(), 'permission_key');
        $cachedAdmin = $admin;
        return $cachedAdmin;
    } catch (Throwable $e) {
        return null;
    }
}

function require_admin(?string $permission = null): array
{
    ensure_installed();
    $admin = current_admin();
    if (!$admin) {
        redirect('login.php');
    }
    if ($permission !== null && !admin_has_permission($admin, $permission)) {
        http_response_code(403);
        exit('Access denied: insufficient permissions.');
    }
    return $admin;
}

function admin_has_permission(array $admin, string $permission): bool
{
    $perms = $admin['permissions'] ?? [];
    return in_array('all', $perms, true) || in_array($permission, $perms, true);
}
