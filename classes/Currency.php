<?php
declare(strict_types=1);

class Currency
{
    private static ?array $cacheByCode = null;

    public static function clearCache(): void
    {
        self::$cacheByCode = null;
    }

    public static function all(bool $onlyEnabled = false, bool $onlyReady = false): array
    {
        self::loadCache();
        $list = array_values(self::$cacheByCode ?? []);
        return array_values(array_filter($list, function (array $c) use ($onlyEnabled, $onlyReady): bool {
            if ($onlyEnabled && (int) $c['is_enabled'] !== 1) {
                return false;
            }
            if ($onlyReady && ((float) ($c['exchange_rate'] ?? 0) <= 0)) {
                return false;
            }
            return true;
        }));
    }

    public static function find(string $code): ?array
    {
        self::loadCache();
        $norm = strtoupper(trim($code));
        return self::$cacheByCode[$norm] ?? null;
    }

    public static function getBase(): array
    {
        self::loadCache();
        foreach (self::$cacheByCode as $c) {
            if ((int) $c['is_base'] === 1) {
                return $c;
            }
        }
        $code = strtoupper(get_setting('currency_code', 'INR')) ?: 'INR';
        if (isset(self::$cacheByCode[$code])) {
            return self::$cacheByCode[$code];
        }
        return [
            'code'                 => $code,
            'name'                 => $code,
            'symbol'               => get_setting('currency_symbol', '₹'),
            'decimal_places'       => 2,
            'is_base'              => 1,
            'is_default'           => 1,
            'rate_mode'            => 'auto',
            'exchange_rate'        => '1.00000000',
            'rate_source'          => 'base',
            'is_enabled'           => 1,
            'last_rate_updated_at' => null,
        ];
    }

    public static function getDefault(): array
    {
        self::loadCache();
        foreach (self::$cacheByCode as $c) {
            if ((int) $c['is_default'] === 1 && (int) $c['is_enabled'] === 1 && (float) ($c['exchange_rate'] ?? 0) > 0) {
                return $c;
            }
        }
        return self::getBase();
    }

    /**
     * Resolve display currency for the active session.
     * Logged-out visitors ALWAYS receive the Admin default currency.
     * Logged-in users receive their preferred currency if enabled & rate-ready.
     */
    public static function getUserDisplay(?array $user = null): array
    {
        if ($user === null && function_exists('current_user')) {
            $user = current_user();
        }
        if ($user && !empty($user['preferred_currency'])) {
            $pref = self::find((string) $user['preferred_currency']);
            if ($pref && (int) $pref['is_enabled'] === 1 && (float) ($pref['exchange_rate'] ?? 0) > 0) {
                return $pref;
            }
        }
        return self::getDefault();
    }

    public static function getBaseCode(): string
    {
        return (string) self::getBase()['code'];
    }

    /**
     * Authoritative conversion between any two configured currencies.
     * Rate semantics: exchange_rate = units of Base Currency per 1 unit of Currency.
     * Target Amount = (Source Amount * Source Rate) / Target Rate
     */
    public static function convertStrict(float|string $amount, string $fromCode, string $toCode, ?int $precision = null): array
    {
        $fromNorm = strtoupper(trim($fromCode));
        $toNorm = strtoupper(trim($toCode));

        $srcNum = (float) $amount;
        if (!is_finite($srcNum) || $srcNum < 0) {
            return ['ok' => false, 'error' => 'Invalid numeric amount for conversion.'];
        }

        if ($fromNorm === '' || $toNorm === '') {
            return ['ok' => false, 'error' => 'Both source and target currencies must be specified.'];
        }

        $src = self::find($fromNorm);
        $dst = self::find($toNorm);

        if (!$src) {
            return ['ok' => false, 'error' => "Currency '{$fromNorm}' is not registered in Currency Management."];
        }
        if (!$dst) {
            return ['ok' => false, 'error' => "Currency '{$toNorm}' is not registered in Currency Management."];
        }

        $srcRate = (float) ($src['exchange_rate'] ?? 0);
        $dstRate = (float) ($dst['exchange_rate'] ?? 0);

        if ($srcRate <= 0) {
            return ['ok' => false, 'error' => "Exchange rate for '{$fromNorm}' is not configured yet. Update rates or set a manual rate in Currencies."];
        }
        if ($dstRate <= 0) {
            return ['ok' => false, 'error' => "Exchange rate for '{$toNorm}' is not configured yet. Update rates or set a manual rate in Currencies."];
        }

        $decimals = $precision ?? (int) ($dst['decimal_places'] ?? 2);

        if ($fromNorm === $toNorm) {
            return [
                'ok'            => true,
                'amount'        => round($srcNum, $decimals),
                'raw_amount'    => $srcNum,
                'from'          => $fromNorm,
                'to'            => $toNorm,
                'source_rate'   => $srcRate,
                'target_rate'   => $dstRate,
                'cross_rate'    => 1.0,
            ];
        }

        if (function_exists('bcmul') && function_exists('bcdiv')) {
            $amtStr = number_format($srcNum, 10, '.', '');
            $srcStr = number_format($srcRate, 10, '.', '');
            $dstStr = number_format($dstRate, 10, '.', '');
            $inBase = bcmul($amtStr, $srcStr, 12);
            $convertedStr = bcdiv($inBase, $dstStr, 12);
            $crossStr = bcdiv($srcStr, $dstStr, 10);
            $rawConverted = (float) $convertedStr;
            $crossRate = (float) $crossStr;
        } else {
            $rawConverted = ($srcNum * $srcRate) / $dstRate;
            $crossRate = $srcRate / $dstRate;
        }

        return [
            'ok'          => true,
            'amount'      => round($rawConverted, $decimals),
            'raw_amount'  => $rawConverted,
            'from'        => $fromNorm,
            'to'          => $toNorm,
            'source_rate' => $srcRate,
            'target_rate' => $dstRate,
            'cross_rate'  => round($crossRate, 8),
        ];
    }

    public static function convertFromBase(float|string $baseAmount, ?string $targetCode = null, ?int $decimals = null): float
    {
        $baseCode = self::getBaseCode();
        $dst = $targetCode ? self::find($targetCode) : self::getUserDisplay();
        if (!$dst || (float) ($dst['exchange_rate'] ?? 0) <= 0) {
            return round((float) $baseAmount, $decimals ?? (int) (self::getBase()['decimal_places'] ?? 2));
        }
        $conv = self::convertStrict($baseAmount, $baseCode, (string) $dst['code'], $decimals);
        if (!$conv['ok']) {
            return round((float) $baseAmount, $decimals ?? 2);
        }
        return (float) $conv['amount'];
    }

    public static function formatBase(float|string $baseAmount, ?string $targetCode = null, ?int $decimals = null): string
    {
        $dst = $targetCode ? self::find($targetCode) : self::getUserDisplay();
        if (!$dst || (float) ($dst['exchange_rate'] ?? 0) <= 0) {
            $dst = self::getBase();
        }
        $dec = $decimals ?? (int) ($dst['decimal_places'] ?? 2);
        $converted = self::convertFromBase($baseAmount, (string) $dst['code'], $dec);
        return self::formatExact($converted, (string) $dst['code'], $dec);
    }

    public static function formatExact(float|string $exactAmount, ?string $currencyCode = null, ?int $decimals = null): string
    {
        $cur = $currencyCode ? self::find($currencyCode) : self::getBase();
        if (!$cur) {
            $cur = self::getBase();
        }
        $sym = (string) ($cur['symbol'] ?? '₹');
        $dec = $decimals ?? (int) ($cur['decimal_places'] ?? 2);
        $val = (float) $exactAmount;
        return $sym . number_format($val, $dec, '.', ',');
    }

    public static function logConversion(string $context, string $from, string $to, float $sourceAmount, float $targetAmount, float $rate, ?int $referenceId = null): void
    {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare(
                'INSERT INTO currency_conversion_logs (context, source_currency, target_currency, source_amount, target_amount, exchange_rate, reference_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$context, strtoupper($from), strtoupper($to), $sourceAmount, $targetAmount, $rate, $referenceId]);
        } catch (Throwable $e) {
        }
    }

    public static function changeBaseCurrency(string $newBaseCode, bool $convertStoredValues = true, ?int $adminId = null): array
    {
        $newCode = strtoupper(trim($newBaseCode));
        $newCur = self::find($newCode);
        if (!$newCur) {
            return ['ok' => false, 'error' => 'Selected base currency does not exist.'];
        }

        $oldBase = self::getBase();
        if ($oldBase['code'] === $newCode) {
            return ['ok' => true, 'message' => "{$newCode} is already the active base currency."];
        }

        $factor = (float) ($newCur['exchange_rate'] ?? 0);
        if ($factor <= 0) {
            return ['ok' => false, 'error' => "Set a valid exchange rate for {$newCode} (relative to {$oldBase['code']}) before promoting it to Base Currency."];
        }

        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            // Re-index all valid rates relative to new base: NewRate = OldRate / factor
            $all = $pdo->query('SELECT id, code, exchange_rate FROM currencies')->fetchAll();
            foreach ($all as $row) {
                $r = (float) ($row['exchange_rate'] ?? 0);
                if ($row['code'] === $newCode) {
                    $pdo->prepare('UPDATE currencies SET is_base = 1, exchange_rate = 1.00000000, rate_source = "base", is_enabled = 1, last_rate_updated_at = NOW() WHERE id = ?')
                        ->execute([(int) $row['id']]);
                } elseif ($r > 0) {
                    $rebased = round($r / $factor, 8);
                    $pdo->prepare('UPDATE currencies SET is_base = 0, exchange_rate = ? WHERE id = ?')
                        ->execute([$rebased, (int) $row['id']]);
                } else {
                    $pdo->prepare('UPDATE currencies SET is_base = 0 WHERE id = ?')->execute([(int) $row['id']]);
                }
            }

            if ($convertStoredValues) {
                // Convert canonical wallet balances, spent_total, service costs and selling prices safely
                $pdo->prepare('UPDATE users SET wallet_balance = ROUND(wallet_balance / ?, 4), spent_total = ROUND(spent_total / ?, 4)')
                    ->execute([$factor, $factor]);
                $pdo->prepare('UPDATE services SET provider_rate = ROUND(provider_rate / ?, 4), price_per_1000 = ROUND(price_per_1000 / ?, 4)')
                    ->execute([$factor, $factor]);
            }

            set_setting('currency_code', $newCode, 'general');
            set_setting('currency_symbol', (string) $newCur['symbol'], 'general');

            $pdo->commit();
            self::clearCache();
            if ($adminId) {
                log_activity('admin', $adminId, 'base_currency_changed', 'currency', (int) $newCur['id'], "Changed base {$oldBase['code']} -> {$newCode} (factor {$factor})");
            }
            return ['ok' => true, 'message' => "Base currency changed from {$oldBase['code']} to {$newCode}."];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Real PHP cURL Exchange Rate Updater.
     * Supports open.er-api.com (no key needed), exchangerate-api.com v6 (keyed), or custom JSON URLs.
     * Never writes zero/invalid rates; never overwrites manual-mode currencies.
     */
    public static function fetchAndUpdateRates(string $triggeredBy = 'manual', ?int $adminId = null): array
    {
        $base = self::getBase();
        $baseCode = (string) $base['code'];
        $provider = get_setting('exchange_api_provider', 'open_er_api');
        $apiKey = decrypt_secret(get_setting('exchange_api_key', ''));
        $customUrl = trim(get_setting('exchange_api_url', 'https://open.er-api.com/v6/latest/{BASE}'));

        if ($provider === 'exchangerate_api_v6') {
            if ($apiKey === '') {
                self::recordRateLog($provider, $baseCode, 'failed', 0, 'ExchangeRate-API v6 key is missing.', null, $triggeredBy);
                return ['ok' => false, 'error' => 'ExchangeRate-API v6 requires an API key.'];
            }
            $url = 'https://v6.exchangerate-api.com/v6/' . rawurlencode($apiKey) . '/latest/' . rawurlencode($baseCode);
        } elseif ($provider === 'custom') {
            if ($customUrl === '' || !filter_var(str_replace(['{BASE}', '{KEY}'], [$baseCode, 'test'], $customUrl), FILTER_VALIDATE_URL)) {
                self::recordRateLog($provider, $baseCode, 'failed', 0, 'Custom exchange API URL is invalid.', null, $triggeredBy);
                return ['ok' => false, 'error' => 'Custom exchange API URL is not valid.'];
            }
            $url = str_replace(['{BASE}', '{KEY}'], [rawurlencode($baseCode), rawurlencode($apiKey)], $customUrl);
        } else {
            $url = 'https://open.er-api.com/v6/latest/' . rawurlencode($baseCode);
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);
        $raw = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($raw === false || $curlErr !== '') {
            $msg = 'cURL error fetching rates: ' . ($curlErr ?: 'Connection failed');
            self::recordRateLog($provider, $baseCode, 'failed', 0, $msg, null, $triggeredBy);
            return ['ok' => false, 'error' => $msg];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded) || $httpCode < 200 || $httpCode >= 300) {
            $msg = 'Exchange rate API returned HTTP ' . $httpCode . ' or invalid JSON.';
            self::recordRateLog($provider, $baseCode, 'failed', 0, $msg, substr($raw, 0, 20000), $triggeredBy);
            return ['ok' => false, 'error' => $msg];
        }

        // Handle error status reported by provider
        if (isset($decoded['result']) && $decoded['result'] === 'error') {
            $msg = 'Exchange rate API error: ' . (string) ($decoded['error-type'] ?? 'Unknown provider error');
            self::recordRateLog($provider, $baseCode, 'failed', 0, $msg, substr($raw, 0, 20000), $triggeredBy);
            return ['ok' => false, 'error' => $msg];
        }

        $ratesMap = $decoded['rates'] ?? ($decoded['conversion_rates'] ?? null);
        if (!is_array($ratesMap) || empty($ratesMap)) {
            $msg = 'API payload did not contain a valid rates/conversion_rates map.';
            self::recordRateLog($provider, $baseCode, 'failed', 0, $msg, substr($raw, 0, 20000), $triggeredBy);
            return ['ok' => false, 'error' => $msg];
        }

        $pdo = Database::getConnection();
        $currencies = self::all(false, false);
        $updated = 0;

        foreach ($currencies as $c) {
            $code = (string) $c['code'];
            if ((int) $c['is_base'] === 1) {
                continue;
            }
            if (($c['rate_mode'] ?? 'auto') === 'manual') {
                // Respect manual override mode strictly
                continue;
            }
            if (!isset($ratesMap[$code]) || !is_numeric($ratesMap[$code])) {
                continue;
            }
            $apiRate = (float) $ratesMap[$code]; // 1 Base = apiRate * Currency
            if (!is_finite($apiRate) || $apiRate <= 0) {
                continue;
            }

            // Stored exchange_rate = Units of Base per 1 Unit of Currency = 1 / apiRate
            $basePerUnit = round(1.0 / $apiRate, 8);
            if ($basePerUnit <= 0) {
                continue;
            }

            $pdo->prepare(
                'UPDATE currencies SET exchange_rate = ?, rate_source = ?, last_rate_updated_at = NOW() WHERE code = ? AND rate_mode = "auto" AND is_base = 0'
            )->execute([$basePerUnit, $provider, $code]);

            $pdo->prepare(
                'INSERT INTO exchange_rates (base_currency, target_currency, rate, source, fetched_at)
                 VALUES (?, ?, ?, ?, NOW())
                 ON DUPLICATE KEY UPDATE rate = VALUES(rate), source = VALUES(source), fetched_at = NOW()'
            )->execute([$baseCode, $code, $basePerUnit, $provider]);

            $updated++;
        }

        self::clearCache();
        set_setting('exchange_last_sync_at', date('Y-m-d H:i:s'), 'currency');

        $status = $updated > 0 ? 'success' : 'partial';
        $msg = $updated > 0
            ? "Updated {$updated} auto-managed currency rate(s) relative to {$baseCode} via {$provider}."
            : 'No eligible auto-mode currencies matched the API feed.';
        self::recordRateLog($provider, $baseCode, $status, $updated, $msg, substr($raw, 0, 12000), $triggeredBy);

        if ($adminId) {
            log_activity('admin', $adminId, 'exchange_rates_synced', 'currency', null, $msg);
        }

        return ['ok' => true, 'updated' => $updated, 'message' => $msg];
    }

    private static function recordRateLog(string $provider, string $base, string $status, int $count, string $message, ?string $rawPayload, string $triggeredBy): void
    {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare(
                'INSERT INTO exchange_rate_logs (provider, base_currency, status, updated_count, message, raw_payload, triggered_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$provider, $base, $status, $count, substr($message, 0, 500), $rawPayload, $triggeredBy]);
        } catch (Throwable $e) {
        }
    }

    private static function loadCache(): void
    {
        if (self::$cacheByCode !== null) {
            return;
        }
        self::$cacheByCode = [];
        try {
            $pdo = Database::getConnection();
            $rows = $pdo->query('SELECT * FROM currencies ORDER BY is_base DESC, is_default DESC, sort_order ASC, code ASC')->fetchAll();
            foreach ($rows as $row) {
                self::$cacheByCode[strtoupper((string) $row['code'])] = $row;
            }
        } catch (Throwable $e) {
            self::$cacheByCode = [];
        }
    }
}
