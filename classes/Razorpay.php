<?php
declare(strict_types=1);

class Razorpay
{
    private const DEFAULT_API_BASE = 'https://api.razorpay.com/v1';

    private string $keyId;
    private string $keySecret;
    private string $webhookSecret;

    public function __construct(string $keyId, string $keySecret, string $webhookSecret = '')
    {
        $this->keyId = trim($keyId);
        $this->keySecret = trim($keySecret);
        $this->webhookSecret = trim($webhookSecret);
    }

    public static function apiBaseUrl(): string
    {
        if (defined('APP_RAZORPAY_API_BASE') && is_string(APP_RAZORPAY_API_BASE) && APP_RAZORPAY_API_BASE !== '') {
            return rtrim(APP_RAZORPAY_API_BASE, '/');
        }
        $envBase = getenv('APP_RAZORPAY_API_BASE');
        if (is_string($envBase) && $envBase !== '') {
            return rtrim($envBase, '/');
        }
        return self::DEFAULT_API_BASE;
    }

    /**
     * Exact integer-safe conversion from INR rupees to paise (1 INR = 100 paise).
     * Eliminates binary floating-point rounding drift.
     */
    public static function toPaise(float|string $amountInr): int
    {
        $clean = number_format((float) $amountInr, 2, '.', '');
        $negative = str_starts_with($clean, '-');
        if ($negative) {
            $clean = substr($clean, 1);
        }
        $parts = explode('.', $clean, 2);
        $rupees = (int) ($parts[0] ?? 0);
        $paise = (int) str_pad(substr((string) ($parts[1] ?? '00'), 0, 2), 2, '0');
        $total = ($rupees * 100) + $paise;
        return $negative ? -$total : $total;
    }

    public static function validateCredentialsFormat(string $keyId, string $keySecret, string $mode = 'test'): array
    {
        $keyId = trim($keyId);
        $keySecret = trim($keySecret);
        if ($keyId === '' || $keySecret === '') {
            return ['ok' => false, 'error' => 'Razorpay API Key ID and Key Secret are not configured yet.'];
        }
        if (!preg_match('/^rzp_(test|live)_[A-Za-z0-9_-]{4,}$/', $keyId)) {
            return ['ok' => false, 'error' => 'Razorpay Key ID format is invalid (expected rzp_test_... or rzp_live_...).'];
        }
        if ($mode === 'test' && str_starts_with($keyId, 'rzp_live_')) {
            return ['ok' => false, 'error' => 'Razorpay is in Test mode but a Live Key ID (rzp_live_) is configured.'];
        }
        if ($mode === 'live' && str_starts_with($keyId, 'rzp_test_')) {
            return ['ok' => false, 'error' => 'Razorpay is in Live mode but a Test Key ID (rzp_test_) is configured.'];
        }
        return ['ok' => true];
    }

    public static function fromDatabase(): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM payment_methods WHERE code = "razorpay" LIMIT 1');
        $stmt->execute();
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        $cfg = json_decode((string) ($row['config_json'] ?? '{}'), true) ?: [];
        $keyId = trim((string) ($cfg['key_id'] ?? ''));
        $keySecret = decrypt_secret((string) ($cfg['key_secret'] ?? ''));
        $webhookSecret = decrypt_secret((string) ($cfg['webhook_secret'] ?? ''));
        $mode = (($row['mode'] ?? 'test') === 'live') ? 'live' : 'test';
        $credCheck = self::validateCredentialsFormat($keyId, $keySecret, $mode);

        return [
            'method'         => $row,
            'is_enabled'     => (int) $row['is_enabled'] === 1,
            'mode'           => $mode,
            'key_id'         => $keyId,
            'key_secret'     => $keySecret,
            'webhook_secret' => $webhookSecret,
            'configured'     => ($keyId !== '' && $keySecret !== ''),
            'valid_format'   => $credCheck['ok'],
            'config_error'   => $credCheck['ok'] ? null : $credCheck['error'],
            'client'         => ($keyId !== '' && $keySecret !== '') ? new self($keyId, $keySecret, $webhookSecret) : null,
        ];
    }

    public function createOrder(float|string $amountInr, string $receipt, array $notes = []): array
    {
        $amountPaise = self::toPaise($amountInr);
        if ($amountPaise < 100) {
            return ['ok' => false, 'error' => 'Minimum Razorpay order amount is ₹1.00 (100 paise).'];
        }

        $res = $this->request('POST', '/orders', [
            'amount'          => $amountPaise,
            'currency'        => 'INR',
            'receipt'         => substr($receipt, 0, 40),
            'payment_capture' => 1,
            'notes'           => $notes,
        ]);

        if (!$res['ok']) {
            return $res;
        }

        $orderData = $res['data'] ?? [];
        if (empty($orderData['id']) || !isset($orderData['amount'])) {
            write_app_log('razorpay', 'Create order missing id/amount in Razorpay payload: ' . json_encode($orderData));
            return ['ok' => false, 'error' => 'Razorpay returned an incomplete order payload. Please try again.'];
        }

        return $res;
    }

    public function fetchPayment(string $paymentId): array
    {
        return $this->request('GET', '/payments/' . rawurlencode($paymentId));
    }

    public function fetchOrderPayments(string $orderId): array
    {
        return $this->request('GET', '/orders/' . rawurlencode($orderId) . '/payments');
    }

    public function testConnection(): array
    {
        return $this->request('GET', '/orders?count=1');
    }

    public function verifyPaymentSignature(string $razorpayOrderId, string $razorpayPaymentId, string $razorpaySignature): bool
    {
        if ($this->keySecret === '' || $razorpayOrderId === '' || $razorpayPaymentId === '' || $razorpaySignature === '') {
            return false;
        }
        $expected = hash_hmac('sha256', $razorpayOrderId . '|' . $razorpayPaymentId, $this->keySecret);
        return hash_equals($expected, $razorpaySignature);
    }

    public static function verifyWebhookSignature(string $rawBody, string $signatureHeader, string $webhookSecret): bool
    {
        if ($webhookSecret === '' || $signatureHeader === '' || $rawBody === '') {
            return false;
        }
        $expected = hash_hmac('sha256', $rawBody, $webhookSecret);
        return hash_equals($expected, $signatureHeader);
    }

    private function request(string $method, string $endpoint, ?array $payload = null): array
    {
        if ($this->keyId === '' || $this->keySecret === '') {
            return ['ok' => false, 'error' => 'Razorpay API credentials are not configured.'];
        }
        if (!function_exists('curl_init')) {
            write_app_log('razorpay', 'PHP cURL extension is not enabled.');
            return ['ok' => false, 'error' => 'Server PHP cURL extension is unavailable.'];
        }

        $url = self::apiBaseUrl() . $endpoint;
        $isHttps = str_starts_with(strtolower($url), 'https://');
        $ch = curl_init($url);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD        => $this->keyId . ':' . $this->keySecret,
            CURLOPT_TIMEOUT        => 25,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => $isHttps,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
        ];

        if ($method === 'POST') {
            $opts[CURLOPT_POST] = true;
            $opts[CURLOPT_POSTFIELDS] = json_encode($payload ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        curl_setopt_array($ch, $opts);
        $raw = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($raw === false || $err !== '') {
            write_app_log('razorpay', sprintf('cURL %s %s error: %s', $method, $endpoint, $err ?: 'Connection failed'));
            return ['ok' => false, 'error' => 'Unable to reach Razorpay servers: ' . ($err ?: 'Connection failed')];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            write_app_log('razorpay', sprintf('Invalid JSON from %s %s (HTTP %d): %s', $method, $endpoint, $httpCode, substr($raw, 0, 400)));
            return ['ok' => false, 'error' => 'Unexpected response from Razorpay gateway (HTTP ' . $httpCode . ').'];
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            $apiMsg = (string) ($decoded['error']['description'] ?? $decoded['error']['reason'] ?? ('HTTP ' . $httpCode));
            write_app_log('razorpay', sprintf('API error %s %s (HTTP %d): %s', $method, $endpoint, $httpCode, $apiMsg));
            if ($httpCode === 401) {
                return ['ok' => false, 'error' => 'Razorpay authentication failed: verify Key ID and Key Secret in Payment Methods.', 'http_code' => $httpCode];
            }
            return ['ok' => false, 'error' => $apiMsg, 'http_code' => $httpCode, 'data' => $decoded];
        }

        return ['ok' => true, 'data' => $decoded, 'http_code' => $httpCode];
    }
}
