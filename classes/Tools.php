<?php
declare(strict_types=1);

class Tools
{
    public static function all(bool $onlyEnabled = false): array
    {
        $pdo = Database::getConnection();
        $where = $onlyEnabled ? 'WHERE is_enabled = 1' : '';
        return $pdo->query("SELECT * FROM tools {$where} ORDER BY sort_order ASC, id ASC")->fetchAll();
    }

    public static function findBySlug(string $slug): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM tools WHERE slug = ? LIMIT 1');
        $stmt->execute([trim($slug)]);
        return $stmt->fetch() ?: null;
    }

    public static function requireEnabled(string $slug): array
    {
        $tool = self::findBySlug($slug);
        if (!$tool || (int) $tool['is_enabled'] !== 1) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/tools/api.php')) {
                json_response(['ok' => false, 'message' => 'This tool is currently disabled by the administrator.'], 403);
            }
            http_response_code(403);
            set_flash('error', 'The requested creator tool is currently disabled.');
            redirect(app_root_prefix() . 'tools/index.php');
        }
        return $tool;
    }

    public static function countUserHourlyUsage(int $userId): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM tool_usage_logs
             WHERE user_id = ? AND status = "success" AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)'
        );
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }

    public static function logUsage(string $toolSlug, ?int $userId, string $provider, string $model, string $inputSummary, string $status, ?string $errorMessage, int $latencyMs): void
    {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare(
                'INSERT INTO tool_usage_logs (tool_slug, user_id, provider, model, input_summary, status, error_message, latency_ms)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $toolSlug,
                $userId,
                $provider,
                $model,
                substr($inputSummary, 0, 480),
                $status,
                $errorMessage ? substr($errorMessage, 0, 480) : null,
                $latencyMs,
            ]);
            if ($status === 'success') {
                $pdo->prepare('UPDATE tools SET usage_count = usage_count + 1 WHERE slug = ?')->execute([$toolSlug]);
            }
        } catch (Throwable $e) {
        }
    }
}
