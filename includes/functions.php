<?php
declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $path): never
{
    if (ob_get_level() > 0) {
        @ob_clean();
    }
    header('Location: ' . $path, true, 302);
    exit;
}

function json_response(array $data, int $statusCode = 200): never
{
    if (ob_get_level() > 0) {
        @ob_clean();
    }
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function is_json_request(): bool
{
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $xhr = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
    return str_contains($script, '/api/')
        || str_contains(strtolower($accept), 'application/json')
        || strtolower($xhr) === 'xmlhttprequest';
}

function register_app_error_handlers(): void
{
    static $registered = false;
    if ($registered || PHP_SAPI === 'cli') {
        return;
    }
    $registered = true;

    @ini_set('display_errors', '0');
    @ini_set('log_errors', '1');

    set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
        if (!(error_reporting() & $severity)) {
            return false;
        }
        write_app_log('app', sprintf('PHP [%d] %s in %s:%d', $severity, $message, $file, $line));
        return true;
    });

    set_exception_handler(static function (Throwable $e): void {
        write_app_log('app', sprintf('Uncaught %s: %s in %s:%d', get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()));
        if (headers_sent()) {
            exit;
        }
        if (is_json_request()) {
            json_response([
                'ok'      => false,
                'success' => false,
                'message' => 'An unexpected server error occurred. Please try again.',
            ], 500);
        }
        http_response_code(500);
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Service Notice</title>'
            . '<style>body{font-family:system-ui,-apple-system,sans-serif;background:#F8F9FC;color:#151515;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;padding:20px}'
            . '.box{max-width:460px;background:#fff;border:1px solid #E2E8F0;border-radius:16px;padding:28px;box-shadow:0 12px 30px -15px rgba(0,0,0,.1)}'
            . 'h1{margin:0 0 8px;font-size:18px}p{margin:0 0 18px;font-size:14px;color:#475569;line-height:1.5}'
            . 'a{display:inline-block;padding:10px 18px;border-radius:10px;background:#7138D8;color:#fff;text-decoration:none;font-size:13px;font-weight:600}</style></head>'
            . '<body><div class="box"><h1>Unable to Complete Request</h1><p>A temporary issue occurred while processing your request. Technical details have been recorded safely.</p>'
            . '<a href="javascript:history.back()">Go Back</a></div></body></html>';
        exit;
    });
}

function write_app_log(string $channel, string $message): void
{
    $dir = defined('LOGS_PATH') ? LOGS_PATH : (__DIR__ . '/../storage/logs');
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
    @file_put_contents($dir . '/' . preg_replace('/[^a-z0-9_-]/i', '', $channel) . '.log', $line, FILE_APPEND | LOCK_EX);
}

function set_flash(string $type, string $message): void
{
    app_start_session();
    $_SESSION['_flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array
{
    app_start_session();
    if (!empty($_SESSION['_flash'])) {
        $flash = $_SESSION['_flash'];
        unset($_SESSION['_flash']);
        return $flash;
    }
    return null;
}

function app_settings_store(?string $updateKey = null, ?string $updateValue = null): array
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->query('SELECT setting_key, setting_value FROM settings');
            foreach ($stmt->fetchAll() as $row) {
                $cache[$row['setting_key']] = (string) ($row['setting_value'] ?? '');
            }
        } catch (Throwable $e) {
            return [];
        }
    }
    if ($updateKey !== null) {
        $cache[$updateKey] = (string) $updateValue;
    }
    return $cache;
}

function get_setting(string $key, ?string $default = ''): string
{
    $cache = app_settings_store();
    return $cache[$key] ?? (string) $default;
}

function set_setting(string $key, string $value, string $group = 'general'): void
{
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare(
        'INSERT INTO settings (setting_key, setting_value, setting_group) VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), setting_group = VALUES(setting_group)'
    );
    $stmt->execute([$key, $value, $group]);
    app_settings_store($key, $value);
}

function is_admin_context(): bool
{
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    return str_contains($script, '/admin/');
}

function format_currency(float|string $amount, ?string $currencyCode = null, ?int $decimals = null): string
{
    if (class_exists('Currency')) {
        if ($currencyCode !== null) {
            return Currency::formatBase($amount, $currencyCode, $decimals);
        }
        $target = is_admin_context() ? Currency::getBaseCode() : (string) Currency::getUserDisplay()['code'];
        return Currency::formatBase($amount, $target, $decimals);
    }
    $sym = get_setting('currency_symbol', '₹');
    $dec = $decimals ?? 2;
    return $sym . number_format((float) $amount, $dec, '.', ',');
}

function format_base_currency(float|string $amount, ?int $decimals = null): string
{
    if (class_exists('Currency')) {
        return Currency::formatExact($amount, Currency::getBaseCode(), $decimals);
    }
    return get_setting('currency_symbol', '₹') . number_format((float) $amount, $decimals ?? 2, '.', ',');
}

function format_exact_currency(float|string $exactAmount, ?string $currencyCode = null, ?int $decimals = null): string
{
    if (class_exists('Currency')) {
        return Currency::formatExact($exactAmount, $currencyCode, $decimals);
    }
    return get_setting('currency_symbol', '₹') . number_format((float) $exactAmount, $decimals ?? 2, '.', ',');
}

function time_ago(string $datetime): string
{
    $ts = strtotime($datetime);
    if ($ts === false) {
        return $datetime;
    }
    $diff = time() - $ts;
    if ($diff < 60) {
        return max(1, $diff) . 's ago';
    }
    if ($diff < 3600) {
        return floor($diff / 60) . 'm ago';
    }
    if ($diff < 86400) {
        return floor($diff / 3600) . 'h ago';
    }
    if ($diff < 604800) {
        return floor($diff / 86400) . 'd ago';
    }
    return date('d M Y', $ts);
}

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim((string) $text, '-') ?: 'category-' . bin2hex(random_bytes(3));
}

function generate_code(string $prefix, int $bytes = 4): string
{
    return strtoupper($prefix) . strtoupper(bin2hex(random_bytes($bytes)));
}

function encrypt_secret(string $plaintext): string
{
    if ($plaintext === '') {
        return '';
    }
    if (!function_exists('openssl_encrypt')) {
        return 'b64:' . base64_encode($plaintext);
    }
    $key = hash('sha256', app_secret_key(), true);
    $iv = random_bytes(12);
    $tag = '';
    $cipher = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    if ($cipher === false) {
        return 'b64:' . base64_encode($plaintext);
    }
    return 'gcm:' . base64_encode($iv . $tag . $cipher);
}

function decrypt_secret(?string $stored): string
{
    if ($stored === null || $stored === '') {
        return '';
    }
    if (str_starts_with($stored, 'b64:')) {
        return (string) base64_decode(substr($stored, 4));
    }
    if (str_starts_with($stored, 'gcm:') && function_exists('openssl_decrypt')) {
        $raw = base64_decode(substr($stored, 4));
        if ($raw === false || strlen($raw) < 29) {
            return '';
        }
        $iv = substr($raw, 0, 12);
        $tag = substr($raw, 12, 16);
        $cipher = substr($raw, 28);
        $key = hash('sha256', app_secret_key(), true);
        $plain = openssl_decrypt($cipher, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        return $plain !== false ? $plain : '';
    }
    return $stored;
}

function log_activity(string $actorType, ?int $actorId, string $action, ?string $entityType = null, ?int $entityId = null, ?string $details = null): void
{
    try {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO activity_logs (actor_type, actor_id, action, entity_type, entity_id, details, ip_address, user_agent)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $actorType,
            $actorId,
            $action,
            $entityType,
            $entityId,
            $details,
            substr((string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45),
            substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
        ]);
    } catch (Throwable $e) {
        // Silent fail on audit logging to avoid breaking primary workflow
    }
}

function create_notification(?int $userId, string $title, string $message, string $type = 'info', ?string $link = null): void
{
    try {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $title, $message, $type, $link]);
    } catch (Throwable $e) {
    }
}

function order_status_badge(string $status): string
{
    $map = [
        'pending'     => ['bg' => 'bg-amber-50 text-amber-700 border-amber-200', 'label' => 'Pending'],
        'processing'  => ['bg' => 'bg-purple-50 text-[#7138D8] border-purple-200', 'label' => 'Processing'],
        'in_progress' => ['bg' => 'bg-indigo-50 text-indigo-700 border-indigo-200', 'label' => 'In Progress'],
        'completed'   => ['bg' => 'bg-emerald-50 text-[#16A36A] border-emerald-200', 'label' => 'Completed'],
        'partial'     => ['bg' => 'bg-sky-50 text-sky-700 border-sky-200', 'label' => 'Partial'],
        'cancelled'   => ['bg' => 'bg-rose-50 text-rose-700 border-rose-200', 'label' => 'Cancelled'],
        'failed'      => ['bg' => 'bg-red-50 text-red-700 border-red-200', 'label' => 'Failed'],
        'refunded'    => ['bg' => 'bg-slate-100 text-slate-700 border-slate-200', 'label' => 'Refunded'],
    ];
    $cfg = $map[$status] ?? ['bg' => 'bg-slate-100 text-slate-700 border-slate-200', 'label' => ucfirst($status)];
    return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium border ' . $cfg['bg'] . '">' . e($cfg['label']) . '</span>';
}

function payment_status_badge(string $status): string
{
    $map = [
        'created'   => ['bg' => 'bg-slate-100 text-slate-700 border-slate-200', 'label' => 'Initiated'],
        'pending'   => ['bg' => 'bg-amber-50 text-amber-700 border-amber-200', 'label' => 'Pending Review'],
        'paid'      => ['bg' => 'bg-emerald-50 text-[#16A36A] border-emerald-200', 'label' => 'Paid'],
        'failed'    => ['bg' => 'bg-rose-50 text-rose-700 border-rose-200', 'label' => 'Failed'],
        'cancelled' => ['bg' => 'bg-slate-100 text-slate-600 border-slate-200', 'label' => 'Cancelled'],
        'rejected'  => ['bg' => 'bg-red-50 text-red-700 border-red-200', 'label' => 'Rejected'],
    ];
    $cfg = $map[$status] ?? ['bg' => 'bg-slate-100 text-slate-700 border-slate-200', 'label' => ucfirst($status)];
    return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium border ' . $cfg['bg'] . '">' . e($cfg['label']) . '</span>';
}



