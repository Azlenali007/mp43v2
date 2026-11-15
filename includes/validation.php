<?php
declare(strict_types=1);

function sanitize_string(?string $value, int $maxLength = 500): string
{
    $value = trim((string) $value);
    $value = strip_tags($value);
    if (mb_strlen($value) > $maxLength) {
        $value = mb_substr($value, 0, $maxLength);
    }
    return $value;
}

function is_valid_email(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false && strlen($email) <= 190;
}

function is_valid_username(string $username): bool
{
    return (bool) preg_match('/^[a-zA-Z0-9_]{3,40}$/', $username);
}

function is_valid_url(string $url): bool
{
    if (strlen($url) > 500 || strlen($url) < 4) {
        return false;
    }
    if (filter_var($url, FILTER_VALIDATE_URL)) {
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        return in_array($scheme, ['http', 'https'], true);
    }
    // Allow social handles or links like @username when placing orders
    return (bool) preg_match('/^@[a-zA-Z0-9._-]{2,100}$/', $url);
}

function validate_positive_float($value, float $min = 0.01, float $max = 10000000.0): ?float
{
    if (!is_numeric($value)) {
        return null;
    }
    $num = round((float) $value, 4);
    if ($num < $min || $num > $max) {
        return null;
    }
    return $num;
}

function validate_positive_int($value, int $min = 1, int $max = 100000000): ?int
{
    if (filter_var($value, FILTER_VALIDATE_INT) === false) {
        return null;
    }
    $num = (int) $value;
    if ($num < $min || $num > $max) {
        return null;
    }
    return $num;
}

function check_rate_limit(string $bucket, int $maxAttempts = 10, int $windowSeconds = 300): bool
{
    if (function_exists('check_database_rate_limit')) {
        return check_database_rate_limit($bucket, $maxAttempts, $windowSeconds);
    }
    app_start_session();
    $key = '_rl_' . sha1($bucket . '|' . ($_SERVER['REMOTE_ADDR'] ?? 'cli'));
    $now = time();
    $attempts = $_SESSION[$key] ?? [];
    $attempts = array_values(array_filter($attempts, fn($ts) => ($now - (int) $ts) < $windowSeconds));
    if (count($attempts) >= $maxAttempts) {
        $_SESSION[$key] = $attempts;
        return false;
    }
    $attempts[] = $now;
    $_SESSION[$key] = $attempts;
    return true;
}

function validate_and_store_upload(array $file, string $subDir): array
{
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'File upload failed or no file was selected.'];
    }
    if (($file['size'] ?? 0) <= 0 || ($file['size'] ?? 0) > MAX_UPLOAD_BYTES) {
        return ['ok' => false, 'error' => 'File size must be under 3 MB.'];
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        return ['ok' => false, 'error' => 'Invalid upload source.'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!isset(ALLOWED_IMAGE_MIMES[$mime])) {
        return ['ok' => false, 'error' => 'Only JPG, PNG, and WEBP image files are permitted.'];
    }

    $ext = ALLOWED_IMAGE_MIMES[$mime];
    $targetDir = UPLOADS_PATH . '/' . trim($subDir, '/');
    if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true)) {
        return ['ok' => false, 'error' => 'Unable to prepare upload directory.'];
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
    $destination = $targetDir . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['ok' => false, 'error' => 'Failed to save uploaded file.'];
    }

    return [
        'ok'   => true,
        'path' => 'uploads/' . trim($subDir, '/') . '/' . $filename,
    ];
}
