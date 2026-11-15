<?php
declare(strict_types=1);

require_once __DIR__ . '/constants.php';

if (!function_exists('app_base_url')) {
    function app_base_url(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
            ? 'https'
            : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost:3000';
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $dir = str_replace('\\', '/', dirname($scriptName));
        // Normalize if inside /admin, /user, /api, /install, /cron
        $dir = preg_replace('#/(admin|user|api|install|cron)(/.*)?$#', '', $dir);
        $dir = rtrim((string) $dir, '/');
        return $scheme . '://' . $host . $dir;
    }
}

if (!function_exists('app_root_prefix')) {
    function app_root_prefix(): string
    {
        if (defined('APP_SUBDIR_PREFIX')) {
            return (string) APP_SUBDIR_PREFIX;
        }
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        return preg_match('#/(admin|user|api|install|cron)/#', $script) ? '../' : '';
    }
}

if (!function_exists('app_is_installed')) {
    function app_is_installed(): bool
    {
        return file_exists(INSTALL_LOCK_FILE) && file_exists(CONFIG_PATH . '/database.local.php');
    }
}

if (!function_exists('app_start_session')) {
    function app_start_session(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_name('SMMPANELSESSID');
        session_start();
    }
}

if (!function_exists('app_secret_key')) {
    function app_secret_key(): string
    {
        $keyFile = CONFIG_PATH . '/app.secret.php';
        if (file_exists($keyFile)) {
            $key = require $keyFile;
            if (is_string($key) && strlen($key) >= 32) {
                return $key;
            }
        }
        return hash('sha256', __FILE__ . php_uname('n'));
    }
}
