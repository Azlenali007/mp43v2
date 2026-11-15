<?php
declare(strict_types=1);

class AIClient
{
    public static function getConfig(): array
    {
        $provider = get_setting('tools_ai_provider', 'gemini');
        $model = trim(get_setting('tools_ai_model', 'gemini-2.5-flash'));
        $endpoint = trim(get_setting('tools_ai_endpoint', ''));
        $encryptedKey = get_setting('tools_ai_api_key', '');
        $apiKey = trim(decrypt_secret($encryptedKey));
        $timeout = max(5, min(90, (int) get_setting('tools_ai_timeout', '30')));

        if ($provider === 'openai_compatible' && $endpoint === '') {
            $endpoint = 'https://api.openai.com/v1/chat/completions';
        } elseif ($provider === 'gemini' && $endpoint === '') {
            $endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/{MODEL}:generateContent';
        }

        return [
            'provider'   => $provider,
            'model'      => $model !== '' ? $model : ($provider === 'gemini' ? 'gemini-2.5-flash' : 'gpt-4o-mini'),
            'endpoint'   => $endpoint,
            'api_key'    => $apiKey,
            'has_key'    => $apiKey !== '',
            'timeout'    => $timeout,
        ];
    }

    public static function generateStructured(string $systemPrompt, string $userPrompt): array
    {
        $cfg = self::getConfig();
        if (!$cfg['has_key']) {
            return [
                'ok'         => false,
                'code'       => 'NOT_CONFIGURED',
                'latency_ms' => 0,
                'provider'   => $cfg['provider'],
                'model'      => $cfg['model'],
                'message'    => 'AI provider API key is not configured yet. Please ask the administrator to configure it in Admin > Creator Tools.',
            ];
        }

        $start = microtime(true);

        if ($cfg['provider'] === 'openai_compatible') {
            $res = self::callOpenAICompatible($cfg, $systemPrompt, $userPrompt);
        } else {
            $res = self::callGemini($cfg, $systemPrompt, $userPrompt);
        }

        $res['latency_ms'] = (int) round((microtime(true) - $start) * 1000);
        $res['provider'] = $cfg['provider'];
        $res['model'] = $cfg['model'];
        return $res;
    }

    public static function testConnection(): array
    {
        $res = self::generateStructured(
            'You are a connectivity test responder. Always return valid JSON.',
            'Respond strictly with {"status":"ok","message":"Connection verified"}'
        );
        if ($res['ok']) {
            return [
                'ok'         => true,
                'latency_ms' => $res['latency_ms'],
                'message'    => "Verified connection to {$res['provider']} ({$res['model']}) in {$res['latency_ms']} ms.",
            ];
        }
        return $res;
    }

    private static function callGemini(array $cfg, string $systemPrompt, string $userPrompt): array
    {
        $urlTemplate = $cfg['endpoint'];
        $url = str_replace('{MODEL}', rawurlencode($cfg['model']), $urlTemplate);
        $sep = str_contains($url, '?') ? '&' : '?';
        $fullUrl = $url . $sep . 'key=' . rawurlencode($cfg['api_key']);

        $payload = [
            'systemInstruction' => [
                'parts' => [['text' => $systemPrompt]],
            ],
            'contents' => [
                [
                    'role'  => 'user',
                    'parts' => [['text' => $userPrompt]],
                ],
            ],
            'generationConfig' => [
                'temperature'      => 0.7,
                'responseMimeType' => 'application/json',
            ],
        ];

        $http = self::curlPostJson($fullUrl, $payload, ['Content-Type: application/json'], $cfg['timeout']);
        if (!$http['ok']) {
            return $http;
        }

        $decoded = json_decode($http['raw'], true);
        if (!is_array($decoded)) {
            return ['ok' => false, 'code' => 'INVALID_RESPONSE', 'message' => 'Gemini API returned non-JSON response.'];
        }
        if (!empty($decoded['error'])) {
            return ['ok' => false, 'code' => 'API_ERROR', 'message' => (string) ($decoded['error']['message'] ?? 'Gemini API error.')];
        }

        $text = (string) ($decoded['candidates'][0]['content']['parts'][0]['text'] ?? '');
        if ($text === '') {
            return ['ok' => false, 'code' => 'EMPTY_OUTPUT', 'message' => 'AI model returned an empty response.'];
        }

        $parsed = self::extractJson($text);
        if ($parsed === null) {
            return ['ok' => false, 'code' => 'PARSE_ERROR', 'message' => 'AI response was not structured JSON. Please retry.'];
        }

        return ['ok' => true, 'data' => $parsed];
    }

    private static function callOpenAICompatible(array $cfg, string $systemPrompt, string $userPrompt): array
    {
        $payload = [
            'model'           => $cfg['model'],
            'temperature'     => 0.7,
            'response_format' => ['type' => 'json_object'],
            'messages'        => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
        ];

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $cfg['api_key'],
        ];

        $http = self::curlPostJson($cfg['endpoint'], $payload, $headers, $cfg['timeout']);
        if (!$http['ok']) {
            return $http;
        }

        $decoded = json_decode($http['raw'], true);
        if (!is_array($decoded)) {
            return ['ok' => false, 'code' => 'INVALID_RESPONSE', 'message' => 'AI endpoint returned non-JSON response.'];
        }
        if (!empty($decoded['error'])) {
            return ['ok' => false, 'code' => 'API_ERROR', 'message' => (string) ($decoded['error']['message'] ?? 'AI endpoint error.')];
        }

        $text = (string) ($decoded['choices'][0]['message']['content'] ?? '');
        if ($text === '') {
            return ['ok' => false, 'code' => 'EMPTY_OUTPUT', 'message' => 'AI model returned an empty response.'];
        }

        $parsed = self::extractJson($text);
        if ($parsed === null) {
            return ['ok' => false, 'code' => 'PARSE_ERROR', 'message' => 'AI response could not be parsed as JSON.'];
        }

        return ['ok' => true, 'data' => $parsed];
    }

    private static function curlPostJson(string $url, array $payload, array $headers, int $timeout): array
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return ['ok' => false, 'code' => 'INVALID_URL', 'message' => 'AI endpoint URL is invalid.'];
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($raw === false || $err !== '') {
            return ['ok' => false, 'code' => 'CURL_ERROR', 'message' => 'AI request connection error: ' . ($err ?: 'Failed')];
        }
        if ($status < 200 || $status >= 300) {
            $errBody = json_decode($raw, true);
            $errMsg = is_array($errBody) ? ($errBody['error']['message'] ?? null) : null;
            return ['ok' => false, 'code' => 'HTTP_' . $status, 'message' => $errMsg ?: ("AI service responded with HTTP {$status}.")];
        }
        return ['ok' => true, 'raw' => $raw];
    }

    private static function extractJson(string $text): ?array
    {
        $clean = trim($text);
        $clean = preg_replace('/^```(?:json)?\s*/i', '', $clean);
        $clean = preg_replace('/\s*```$/', '', (string) $clean);
        $decoded = json_decode((string) $clean, true);
        if (is_array($decoded)) {
            return $decoded;
        }
        if (preg_match('/\{[\s\S]*\}/', (string) $clean, $m)) {
            $decoded2 = json_decode($m[0], true);
            if (is_array($decoded2)) {
                return $decoded2;
            }
        }
        return null;
    }
}
