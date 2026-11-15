<?php
declare(strict_types=1);

class Razorpay
{
    private const API_BASE = 'https://api.razorpay.com/v1';

    private string $keyId;
    private string $keySecret;
    private string $webhookSecret;

    public function __construct(string $keyId, string $keySecret, string $webhookSecret = '')
    {
        $this->keyId = trim($keyId);
        $this->keySecret = trim($keySecret);
        $this->webhookSecret = trim($webhookSecret);
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

        return [
            'method'         => $row,
            'is_enabled'     => (int) $row['is_enabled'] === 1,
            'mode'           => $row['mode'],
            'key_id'         => $keyId,
            'key_secret'     => $keySecret,
            'webhook_secret' => $webhookSecret,
            'configured'     => ($keyId !== '' && $keySecret !== ''),
            'client'         => ($keyId !== '' && $keySecret !== '') ? new self($keyId, $keySecret, $webhookSecret) : null,
        ];
    }

    public function createOrder(float $amountInr, string $receipt, array $notes = []): array
    {
        $amountPaise = (int) round($amountInr * 100);
        if ($amountPaise < 100) {
            return ['ok' => false, 'error' => 'Minimum Razorpay order amount is ₹1.00.'];
        }

        return $this->request('POST', '/orders', [
            'amount'          => $amountPaise,
            'currency'        => 'INR',
            'receipt'         => $receipt,
            'payment_capture' => 1,
            'notes'           => $notes,
        ]);
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

        $ch = curl_init(self::API_BASE . $endpoint);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD        => $this->keyId . ':' . $this->keySecret,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
        ];

        if ($method === 'POST') {
            $opts[CURLOPT_POST] = true;
            $opts[CURLOPT_POSTFIELDS] = json_encode($payload ?? []);
        }

        curl_setopt_array($ch, $opts);
        $raw = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($raw === false || $err !== '') {
            return ['ok' => false, 'error' => 'Razorpay network error: ' . ($err ?: 'Connection failed')];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return ['ok' => false, 'error' => 'Invalid response from Razorpay (HTTP ' . $httpCode . ').'];
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            $msg = $decoded['error']['description'] ?? $decoded['error']['reason'] ?? ('Razorpay HTTP ' . $httpCode);
            return ['ok' => false, 'error' => $msg, 'http_code' => $httpCode, 'data' => $decoded];
        }

        return ['ok' => true, 'data' => $decoded, 'http_code' => $httpCode];
    }
}
