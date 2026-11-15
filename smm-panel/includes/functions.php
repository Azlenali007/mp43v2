<?php
declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function json_response(array $data, int $statusCode = 200): never
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
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

function get_setting(string $key, ?string $default = ''): string
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
            return (string) $default;
        }
    }
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
}

function format_currency(float|string $amount, ?string $symbol = null, int $decimals = 2): string
{
    $sym = $symbol ?? get_setting('currency_symbol', '₹');
    $val = (float) $amount;
    $formatted = number_format($val, $decimals, '.', ',');
    return $sym . $formatted;
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

function write_app_log(string $channel, string $message): void
{
    if (!is_dir(LOGS_PATH)) {
        @mkdir(LOGS_PATH, 0755, true);
    }
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
    @file_put_contents(LOGS_PATH . '/' . preg_replace('/[^a-z0-9_-]/i', '', $channel) . '.log', $line, FILE_APPEND | LOCK_EX);
}
