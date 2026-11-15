<?php
declare(strict_types=1);

class OtpProvider
{
    public static function getActive(): ?array
    {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->query('SELECT * FROM otp_providers WHERE status = "active" ORDER BY id ASC LIMIT 1');
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (Throwable $e) {
            return null;
        }
    }

    public static function callApi(string $action, array $params = []): array
    {
        $provider = self::getActive();
        if (!$provider || empty($provider['api_key'])) {
            return ['ok' => false, 'error' => 'OTP provider is not configured or disabled.'];
        }

        $apiKey = trim($provider['api_key']);
        $baseUrl = trim($provider['api_url']);
        if ($baseUrl === '') {
            $baseUrl = 'https://api.sms-activate.org/stg/handler.php';
        }

        $params['api_key'] = $apiKey;
        $params['action'] = $action;

        $url = $baseUrl . '?' . http_build_query($params);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        $response = curl_exec($ch);
        $err = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            return ['ok' => false, 'error' => 'cURL error: ' . $err];
        }

        // SMS-Activate API can return strings like "ACCESS_BALANCE:100.50", "BAD_KEY", "NO_NUMBERS", etc.
        return [
            'ok' => true,
            'http_code' => $httpCode,
            'raw' => trim((string) $response),
        ];
    }

    public static function fetchBalance(): array
    {
        $res = self::callApi('getBalance');
        if (!$res['ok']) {
            return $res;
        }
        $raw = $res['raw'];
        if (str_starts_with($raw, 'ACCESS_BALANCE:')) {
            $bal = (float) substr($raw, 15);
            try {
                $pdo = Database::getConnection();
                $pdo->prepare('UPDATE otp_providers SET balance = ?, last_sync_at = NOW() WHERE status = "active"')->execute([$bal]);
            } catch (Throwable $e) {
            }
            return ['ok' => true, 'balance' => $bal];
        }
        return ['ok' => false, 'error' => 'Provider error: ' . $raw];
    }

    public static function fetchCountriesAndServices(): array
    {
        $provider = self::getActive();
        if (!$provider || empty($provider['api_key'])) {
            return ['ok' => false, 'error' => 'No active OTP provider configured.'];
        }

        // Fetch countries and prices / services
        // SMS-Activate action=getPrices or action=getCountries
        $res = self::callApi('getPrices');
        if (!$res['ok']) {
            return $res;
        }

        $data = json_decode($res['raw'], true);
        if (!is_array($data)) {
            return ['ok' => false, 'error' => 'Invalid JSON response from provider: ' . substr($res['raw'], 0, 100)];
        }

        $pdo = Database::getConnection();
        $providerId = (int) $provider['id'];
        $updatedCount = 0;

        $pdo->beginTransaction();
        try {
            // Data format in sms-activate: { "countryId": { "serviceId": { "count": X, "cost": Y } } }
            foreach ($data as $countryCode => $services) {
                if (!is_array($services)) continue;

                $countryName = 'Country ' . $countryCode;
                // Upsert country
                $cStmt = $pdo->prepare('INSERT INTO otp_countries (provider_id, country_code, name, service_count, is_enabled) VALUES (?, ?, ?, ?, 1) ON DUPLICATE KEY UPDATE service_count = VALUES(service_count)');
                $cStmt->execute([$providerId, (string)$countryCode, $countryName, count($services)]);

                foreach ($services as $serviceKey => $info) {
                    $cost = (float) ($info['cost'] ?? 0);
                    $count = (int) ($info['count'] ?? 0);
                    $stockStatus = $count > 0 ? 'Available (' . $count . ')' : 'Out of stock';

                    // Default margin 20%
                    $marginVal = 20.0;
                    $sellingPrice = round($cost * 1.20, 2);

                    $sStmt = $pdo->prepare(
                        'INSERT INTO otp_services (provider_id, external_service_id, country_code, name, category, original_currency, original_provider_rate, provider_rate, price_per_number, stock_status, is_enabled)
                         VALUES (?, ?, ?, ?, "general", "RUB", ?, ?, ?, ?, 1)
                         ON DUPLICATE KEY UPDATE original_provider_rate = VALUES(original_provider_rate), provider_rate = VALUES(provider_rate), stock_status = VALUES(stock_status)'
                    );
                    $sStmt->execute([
                        $providerId,
                        (string)$serviceKey,
                        (string)$countryCode,
                        'Service ' . $serviceKey,
                        $cost,
                        $cost,
                        $sellingPrice,
                        $stockStatus,
                    ]);
                    $updatedCount++;
                }
            }
            $pdo->commit();
            return ['ok' => true, 'updated_count' => $updatedCount];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    public static function buyNumber(int $serviceDbId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT s.*, p.id as prov_id FROM otp_services s JOIN otp_providers p ON p.id = s.provider_id WHERE s.id = ? AND s.is_enabled = 1');
        $stmt->execute([$serviceDbId]);
        $service = $stmt->fetch();
        if (!$service) {
            return ['ok' => false, 'error' => 'OTP service not found or disabled.'];
        }

        $user = current_user();
        if (!$user) {
            return ['ok' => false, 'error' => 'Authentication required.'];
        }

        $userId = (int) $user['id'];
        $sellingPrice = (float) $service['price_per_number'];
        $cost = (float) $service['original_provider_rate'];

        // Check wallet balance
        $uStmt = $pdo->prepare('SELECT wallet_balance FROM users WHERE id = ? FOR UPDATE');
        $uStmt->execute([$userId]);
        $currentBalance = (float) $uStmt->fetchColumn();

        if ($currentBalance < $sellingPrice) {
            return ['ok' => false, 'error' => 'Insufficient wallet balance. Please add funds to your wallet.'];
        }

        // Call provider API to get number: action=getNumber&service=X&country=Y
        $res = self::callApi('getNumber', [
            'service' => $service['external_service_id'],
            'country' => $service['country_code'],
        ]);

        if (!$res['ok']) {
            return $res;
        }

        $raw = $res['raw'];
        // Expected response format: ACCESS_NUMBER:ACTIVATION_ID:PHONE
        if (!str_starts_with($raw, 'ACCESS_NUMBER:')) {
            return ['ok' => false, 'error' => 'Provider error: ' . $raw];
        }

        $parts = explode(':', $raw);
        if (count($parts) < 3) {
            return ['ok' => false, 'error' => 'Invalid provider response: ' . $raw];
        }

        $activationId = $parts[1];
        $phoneNumber = $parts[2];
        $orderCode = 'OTP' . strtoupper(bin2hex(random_bytes(4)));

        $pdo->beginTransaction();
        try {
            // Deduct wallet balance once
            $newBalance = $currentBalance - $sellingPrice;
            $pdo->prepare('UPDATE users SET wallet_balance = ?, spent_total = spent_total + ? WHERE id = ?')->execute([$newBalance, $sellingPrice, $userId]);

            // Create otp_order
            $oStmt = $pdo->prepare(
                'INSERT INTO otp_orders (order_code, user_id, service_id, provider_id, provider_activation_id, phone_number, country_code, cost, charge, profit, status, expires_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "waiting", DATE_ADD(NOW(), INTERVAL 20 MINUTE))'
            );
            $profit = $sellingPrice - $cost;
            $oStmt->execute([
                $orderCode,
                $userId,
                $serviceDbId,
                $service['prov_id'],
                $activationId,
                $phoneNumber,
                $service['country_code'],
                $cost,
                $sellingPrice,
                max(0, $profit),
            ]);
            $orderId = (int) $pdo->lastInsertId();

            // Record otp_transaction
            $pdo->prepare(
                'INSERT INTO otp_transactions (user_id, type, amount, balance_before, balance_after, reference_id, description)
                 VALUES (?, "debit", ?, ?, ?, ?, ?)'
            )->execute([
                $userId,
                $sellingPrice,
                $currentBalance,
                $newBalance,
                $orderId,
                "OTP Number Purchase #{$orderCode} ({$phoneNumber})"
            ]);

            // Record log
            $pdo->prepare('INSERT INTO otp_order_logs (order_id, status, message, raw_response) VALUES (?, "waiting", ?, ?)')
                ->execute([$orderId, 'Number acquired successfully', $raw]);

            $pdo->commit();
            return [
                'ok' => true,
                'order_id' => $orderId,
                'order_code' => $orderCode,
                'phone_number' => $phoneNumber,
                'activation_id' => $activationId,
            ];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['ok' => false, 'error' => 'Database transaction failed: ' . $e->getMessage()];
        }
    }

    public static function checkStatus(int $orderId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM otp_orders WHERE id = ?');
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();
        if (!$order || empty($order['provider_activation_id'])) {
            return ['ok' => false, 'error' => 'Order not found.'];
        }

        if ($order['status'] !== 'waiting') {
            return ['ok' => true, 'status' => $order['status'], 'sms_code' => $order['sms_code']];
        }

        $res = self::callApi('getStatus', ['id' => $order['provider_activation_id']]);
        if (!$res['ok']) {
            return $res;
        }

        $raw = $res['raw'];
        // Possible responses: STATUS_WAIT_CODE, STATUS_OK:12345, STATUS_CANCEL, STATUS_EXPIRED
        if ($raw === 'STATUS_WAIT_CODE') {
            return ['ok' => true, 'status' => 'waiting', 'raw' => $raw];
        }

        if (str_starts_with($raw, 'STATUS_OK:')) {
            $smsCode = substr($raw, 10);
            $pdo->prepare('UPDATE otp_orders SET status = "received", sms_code = ?, full_sms_text = ?, received_at = NOW() WHERE id = ?')
                ->execute([$smsCode, $raw, $orderId]);
            return ['ok' => true, 'status' => 'received', 'sms_code' => $smsCode];
        }

        if ($raw === 'STATUS_CANCEL' || $raw === 'STATUS_EXPIRED') {
            $newStatus = $raw === 'STATUS_CANCEL' ? 'cancelled' : 'expired';
            self::processRefund($orderId, $newStatus);
            return ['ok' => true, 'status' => $newStatus];
        }

        return ['ok' => true, 'status' => $order['status'], 'raw' => $raw];
    }

    public static function cancelOrder(int $orderId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM otp_orders WHERE id = ? AND status = "waiting"');
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();
        if (!$order) {
            return ['ok' => false, 'error' => 'Order cannot be cancelled.'];
        }

        // action=setStatus&status=8&id=...
        $res = self::callApi('setStatus', ['status' => 8, 'id' => $order['provider_activation_id']]);
        if (!$res['ok']) {
            return $res;
        }

        $raw = $res['raw'];
        if ($raw === 'ACCESS_CANCEL' || str_contains($raw, 'CANCEL')) {
            self::processRefund($orderId, 'cancelled');
            return ['ok' => true, 'message' => 'Order cancelled and refunded successfully.'];
        }

        return ['ok' => false, 'error' => 'Provider cancellation failed: ' . $raw];
    }

    private static function processRefund(int $orderId, string $status): void
    {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT * FROM otp_orders WHERE id = ? FOR UPDATE');
            $stmt->execute([$orderId]);
            $order = $stmt->fetch();
            if (!$order || $order['refunded_amount'] > 0 || in_array($order['status'], ['received', 'refunded'])) {
                $pdo->commit();
                return;
            }

            $charge = (float) $order['charge'];
            $userId = (int) $order['user_id'];

            $uStmt = $pdo->prepare('SELECT wallet_balance FROM users WHERE id = ? FOR UPDATE');
            $uStmt->execute([$userId]);
            $currentBalance = (float) $uStmt->fetchColumn();

            $newBalance = $currentBalance + $charge;
            $pdo->prepare('UPDATE users SET wallet_balance = ?, spent_total = GREATEST(0, spent_total - ?) WHERE id = ?')->execute([$newBalance, $charge, $userId]);
            $pdo->prepare('UPDATE otp_orders SET status = ?, refunded_amount = ? WHERE id = ?')->execute([$status, $charge, $orderId]);

            $pdo->prepare(
                'INSERT INTO otp_transactions (user_id, type, amount, balance_before, balance_after, reference_id, description)
                 VALUES (?, "refund", ?, ?, ?, ?, ?)'
            )->execute([
                $userId,
                $charge,
                $currentBalance,
                $newBalance,
                $orderId,
                "OTP Refund for Order #{$order['order_code']}"
            ]);

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
        }
    }
}
