<?php
declare(strict_types=1);

function csrf_token(): string
{
    app_start_session();
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function verify_csrf(?string $token): bool
{
    app_start_session();
    if (empty($token) || empty($_SESSION['_csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['_csrf_token'], $token);
}

function verify_csrf_or_abort(bool $json = false): void
{
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (!verify_csrf(is_string($token) ? $token : null)) {
        if ($json) {
            if (ob_get_level() > 0) {
                @ob_clean();
            }
            http_response_code(419);
            header('Content-Type: application/json; charset=utf-8');
            header('X-Content-Type-Options: nosniff');
            echo json_encode([
                'ok'      => false,
                'success' => false,
                'message' => 'Invalid or expired security token. Please refresh the page and try again.',
            ]);
            exit;
        }
        http_response_code(419);
        exit('Invalid or expired security token. Please go back, refresh the page, and try again.');
    }
}
