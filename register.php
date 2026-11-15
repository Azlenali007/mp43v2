<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
ensure_installed();

require_once __DIR__ . '/classes/Auth.php';

if (current_user()) {
    redirect('dashboard.php');
}

$registrationEnabled = get_setting('registration_enabled', '1') === '1';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();
    if (!$registrationEnabled) {
        $error = 'New user registrations are currently closed.';
    } elseif (!check_rate_limit('user_register', 8, 600)) {
        $error = 'Too many registration attempts. Please try again later.';
    } else {
        $name = sanitize_string($_POST['name'] ?? '', 120);
        $username = sanitize_string($_POST['username'] ?? '', 40);
        $email = sanitize_string($_POST['email'] ?? '', 190);
        $phone = sanitize_string($_POST['phone'] ?? '', 30);
        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['password_confirm'] ?? '');

        if ($name === '' || !is_valid_username($username) || !is_valid_email($email)) {
            $error = 'Provide a valid full name, alphanumeric username (3-40 chars), and email address.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters long.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            $res = Auth::registerUser($name, $username, $email, $password, $phone);
            if ($res['ok']) {
                Auth::loginUser($email, $password);
                set_flash('success', 'Welcome! Your account has been created.');
                redirect('dashboard.php');
            }
            $error = $res['error'];
        }
    }
}

Theme::renderPage('register.php', [
    'pageTitle'           => 'Create Account',
    'registrationEnabled' => $registrationEnabled,
    'error'               => $error,
]);
