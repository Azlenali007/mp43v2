<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
ensure_installed();

require_once __DIR__ . '/classes/Auth.php';

if (current_user()) {
    redirect('dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();
    if (!check_rate_limit('user_login', 12, 300)) {
        $error = 'Too many login attempts. Please wait a few minutes before trying again.';
    } else {
        $identifier = sanitize_string($_POST['identifier'] ?? '', 190);
        $password = (string) ($_POST['password'] ?? '');
        if ($identifier === '' || $password === '') {
            $error = 'Please enter your email/username and password.';
        } else {
            $res = Auth::loginUser($identifier, $password);
            if ($res['ok']) {
                redirect('dashboard.php');
            }
            $error = $res['error'];
        }
    }
}

Theme::renderPage('login.php', [
    'pageTitle'     => 'Sign In',
    'error'         => $error,
    'oldIdentifier' => $_POST['identifier'] ?? '',
]);
