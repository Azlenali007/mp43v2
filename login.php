<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
ensure_installed();
require_once __DIR__ . '/classes/Auth.php';

if (current_user()) {
    redirect(app_root_prefix() . 'dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    write_app_log('app', 'LOGIN_POST_RECEIVED: User login attempt');
    verify_csrf_or_abort();
    if (!check_rate_limit('user_login', 12, 300)) {
        $error = 'Too many login attempts. Please wait a few minutes before trying again.';
    } else {
        $identifier = sanitize_string($_POST['identifier'] ?? '', 190);
        $password = (string) ($_POST['password'] ?? '');
        if ($identifier === '' || $password === '') {
            $error = 'Please enter your email/username and password.';
        } else {
            write_app_log('app', 'LOGIN_VALIDATION_PASSED');
            $res = Auth::loginUser($identifier, $password);
            if ($res['ok']) {
                write_app_log('app', 'DATABASE_AUTH_SUCCESS & SESSION_CREATED for user #' . ($res['user']['id'] ?? 0));
                write_app_log('app', 'REDIRECT_ATTEMPTED to dashboard.php');
                redirect(app_root_prefix() . 'dashboard.php');
            }
            $error = $res['error'] ?? 'Invalid credentials.';
            write_app_log('app', 'LOGIN_FAILED: ' . $error);
        }
    }
}

Theme::renderPage('login.php', [
    'pageTitle'     => 'Sign In',
    'error'         => $error,
    'oldIdentifier' => $_POST['identifier'] ?? '',
]);
