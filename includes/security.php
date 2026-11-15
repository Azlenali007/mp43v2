<?php
declare(strict_types=1);

function get_client_ip(): string
{
    $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $ip = trim((string) $ip);
    if (filter_var($ip, FILTER_VALIDATE_IP)) {
        return $ip;
    }
    return '0.0.0.0';
}

function is_ip_blocked(string $ip): bool
{
    try {
        $pdo = Database::getConnection();
        Migrator::ensureSchemaReady($pdo);
        $stmt = $pdo->prepare('SELECT id, blocked_until FROM blocked_ips WHERE ip_address = ? LIMIT 1');
        $stmt->execute([$ip]);
        $row = $stmt->fetch();
        if (!$row) {
            return false;
        }
        if ($row['blocked_until'] !== null && strtotime($row['blocked_until']) <= time()) {
            $pdo->prepare('DELETE FROM blocked_ips WHERE id = ?')->execute([(int) $row['id']]);
            return false;
        }
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function log_security_event(string $eventType, string $details = '', string $severity = 'warning', ?int $userId = null): void
{
    try {
        $pdo = Database::getConnection();
        Migrator::ensureSchemaReady($pdo);
        $stmt = $pdo->prepare(
            'INSERT INTO security_logs (event_type, ip_address, user_id, details, severity) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$eventType, get_client_ip(), $userId, substr($details, 0, 1000), $severity]);
    } catch (Throwable $e) {
    }
}

function check_database_rate_limit(string $bucket, int $maxAttempts = 15, int $windowSeconds = 300): bool
{
    $ip = get_client_ip();
    if (is_ip_blocked($ip)) {
        return false;
    }

    $user = function_exists('current_user') ? current_user() : null;
    $userId = $user ? (int) $user['id'] : null;

    try {
        $pdo = Database::getConnection();
        Migrator::ensureSchemaReady($pdo);
        $now = time();
        $expiry = date('Y-m-d H:i:s', $now + $windowSeconds);
        $nowSql = date('Y-m-d H:i:s', $now);

        // Cleanup expired
        $pdo->exec('DELETE FROM rate_limits WHERE expires_at < NOW()');

        $stmt = $pdo->prepare(
            'SELECT id, attempts, expires_at FROM rate_limits WHERE ip_address = ? AND (user_id <=> ?) AND bucket = ? LIMIT 1'
        );
        $stmt->execute([$ip, $userId, $bucket]);
        $row = $stmt->fetch();

        if ($row) {
            $attempts = (int) $row['attempts'];
            if (strtotime($row['expires_at']) > $now) {
                if ($attempts >= $maxAttempts) {
                    log_security_event('rate_limit_exceeded', "Bucket: {$bucket}, Attempts: {$attempts}", 'warning', $userId);
                    return false;
                }
                $pdo->prepare('UPDATE rate_limits SET attempts = attempts + 1 WHERE id = ?')->execute([(int) $row['id']]);
            } else {
                $pdo->prepare('UPDATE rate_limits SET attempts = 1, expires_at = ? WHERE id = ?')->execute([$expiry, (int) $row['id']]);
            }
        } else {
            $pdo->prepare(
                'INSERT INTO rate_limits (ip_address, user_id, bucket, attempts, expires_at) VALUES (?, ?, ?, 1, ?)'
            )->execute([$ip, $userId, $bucket, $expiry]);
        }
        return true;
    } catch (Throwable $e) {
        // Fallback to session check if DB error occurs
        if (function_exists('check_rate_limit')) {
            return check_rate_limit($bucket, $maxAttempts, $windowSeconds);
        }
        return true;
    }
}

function send_security_headers(): void
{
    if (headers_sent()) {
        return;
    }
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), camera=(), microphone=()');
    
    // Content Security Policy compatible with Tailwind, Alpine, Chart.js, Razorpay checkout, Google Fonts
    $csp = "default-src 'self' https:; "
         . "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdn.tailwindcss.com https://checkout.razorpay.com https://api.razorpay.com https://*.razorpay.com; "
         . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net https://cdn.tailwindcss.com https://*.razorpay.com; "
         . "font-src 'self' https://fonts.gstatic.com data:; "
         . "img-src 'self' data: https: https://*.razorpay.com; "
         . "connect-src 'self' https://api.razorpay.com https://*.razorpay.com https://open.er-api.com https://v6.exchangerate-api.com;";
    header("Content-Security-Policy: " . $csp);
}
