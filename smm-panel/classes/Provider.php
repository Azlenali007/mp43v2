<?php
declare(strict_types=1);

class Provider
{
    public static function all(bool $onlyActive = false): array
    {
        $pdo = Database::getConnection();
        $where = $onlyActive ? 'WHERE status = "active"' : '';
        return $pdo->query("SELECT * FROM providers $where ORDER BY id DESC")->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM providers WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row) {
            $row['decrypted_api_key'] = decrypt_secret($row['api_key']);
        }
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO providers (name, api_url, api_key, api_type, currency, status, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['name'],
            $data['api_url'],
            encrypt_secret($data['api_key']),
            $data['api_type'] ?: 'standard',
            $data['currency'] ?: 'INR',
            $data['status'] === 'disabled' ? 'disabled' : 'active',
            $data['notes'] ?? null,
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $pdo = Database::getConnection();
        $existing = self::find($id);
        $apiKeyEncrypted = !empty($data['api_key'])
            ? encrypt_secret($data['api_key'])
            : ($existing['api_key'] ?? '');

        $stmt = $pdo->prepare(
            'UPDATE providers SET name = ?, api_url = ?, api_key = ?, api_type = ?, currency = ?, status = ?, notes = ? WHERE id = ?'
        );
        $stmt->execute([
            $data['name'],
            $data['api_url'],
            $apiKeyEncrypted,
            $data['api_type'] ?: 'standard',
            $data['currency'] ?: 'INR',
            $data['status'] === 'disabled' ? 'disabled' : 'active',
            $data['notes'] ?? null,
            $id,
        ]);
    }

    public static function request(array $provider, array $postParams): array
    {
        $apiKey = $provider['decrypted_api_key'] ?? decrypt_secret($provider['api_key'] ?? '');
        $url = trim((string) ($provider['api_url'] ?? ''));

        if ($url === '' || $apiKey === '') {
            return ['ok' => false, 'error' => 'Provider API URL or API Key is not configured.'];
        }

        $payload = array_merge(['key' => $apiKey], $postParams);
        $safeLogPayload = $payload;
        $safeLogPayload['key'] = '***REDACTED***';

        $start = microtime(true);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($payload),
            CURLOPT_TIMEOUT        => 25,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);

        $rawBody = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);
        $durationMs = (int) round((microtime(true) - $start) * 1000);

        self::logApiCall(
            isset($provider['id']) ? (int) $provider['id'] : null,
            $url,
            'POST',
            json_encode($safeLogPayload),
            $httpCode,
            is_string($rawBody) ? substr($rawBody, 0, 50000) : $curlErr,
            $durationMs
        );

        if ($rawBody === false || $curlErr !== '') {
            return ['ok' => false, 'error' => 'cURL connection error: ' . ($curlErr ?: 'Unable to reach provider API')];
        }

        $decoded = json_decode($rawBody, true);
        if (!is_array($decoded)) {
            return ['ok' => false, 'error' => 'Provider returned non-JSON response (HTTP ' . $httpCode . ').'];
        }

        if (isset($decoded['error'])) {
            return ['ok' => false, 'error' => (string) $decoded['error'], 'data' => $decoded];
        }

        return ['ok' => true, 'data' => $decoded, 'http_code' => $httpCode];
    }

    public static function fetchBalance(int $providerId): array
    {
        $provider = self::find($providerId);
        if (!$provider) {
            return ['ok' => false, 'error' => 'Provider not found.'];
        }
        $res = self::request($provider, ['action' => 'balance']);
        if (!$res['ok']) {
            return $res;
        }
        if (!isset($res['data']['balance'])) {
            return ['ok' => false, 'error' => 'Provider response did not include balance field.'];
        }
        $balance = (float) $res['data']['balance'];
        $currency = (string) ($res['data']['currency'] ?? $provider['currency']);
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE providers SET balance = ?, currency = ?, last_sync_at = NOW() WHERE id = ?');
        $stmt->execute([$balance, $currency, $providerId]);

        return ['ok' => true, 'balance' => $balance, 'currency' => $currency];
    }

    public static function fetchServices(int $providerId): array
    {
        $provider = self::find($providerId);
        if (!$provider) {
            return ['ok' => false, 'error' => 'Provider not found.'];
        }
        $res = self::request($provider, ['action' => 'services']);
        if (!$res['ok']) {
            return $res;
        }
        if (!is_array($res['data']) || !array_is_list($res['data'])) {
            return ['ok' => false, 'error' => 'Provider returned unexpected services list structure.'];
        }
        return ['ok' => true, 'services' => $res['data'], 'provider' => $provider];
    }

    public static function submitOrder(int $providerId, string $externalServiceId, string $link, int $quantity): array
    {
        $provider = self::find($providerId);
        if (!$provider || $provider['status'] !== 'active') {
            return ['ok' => false, 'error' => 'Provider is unavailable or disabled.'];
        }
        $res = self::request($provider, [
            'action'   => 'add',
            'service'  => $externalServiceId,
            'link'     => $link,
            'quantity' => $quantity,
        ]);
        if (!$res['ok']) {
            return $res;
        }
        if (empty($res['data']['order'])) {
            return ['ok' => false, 'error' => 'Provider did not return an order reference ID.', 'raw' => $res['data']];
        }
        return ['ok' => true, 'provider_order_id' => (string) $res['data']['order'], 'raw' => $res['data']];
    }

    public static function fetchOrderStatus(int $providerId, string $providerOrderId): array
    {
        $provider = self::find($providerId);
        if (!$provider) {
            return ['ok' => false, 'error' => 'Provider not found.'];
        }
        return self::request($provider, [
            'action' => 'status',
            'order'  => $providerOrderId,
        ]);
    }

    private static function logApiCall(?int $providerId, string $endpoint, string $method, ?string $req, int $code, ?string $res, int $ms): void
    {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare(
                'INSERT INTO api_logs (provider_id, endpoint, method, request_payload, response_code, response_body, duration_ms)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$providerId, substr($endpoint, 0, 255), $method, $req, $code, $res, $ms]);
        } catch (Throwable $e) {
        }
    }
}
