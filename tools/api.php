<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/includes/tool-functions.php';

ensure_installed();
$user = current_user();
if (!$user) {
    json_response(['ok' => false, 'message' => 'Authentication required.'], 401);
}
verify_csrf_or_abort(true);

$input = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

$slug = sanitize_string($input['slug'] ?? '', 80);
$tool = Tools::requireEnabled($slug);

$rateLimit = max(1, (int) get_setting('tools_rate_limit_per_hour', '30'));
$hourlyCount = Tools::countUserHourlyUsage((int) $user['id']);
if ($hourlyCount >= $rateLimit) {
    json_response([
        'ok'      => false,
        'message' => "Hourly tool usage limit reached ({$rateLimit} requests/hour). Please try again shortly.",
    ], 429);
}

$summaryParts = [];
foreach ($input as $k => $v) {
    if ($k === 'slug' || !is_scalar($v)) {
        continue;
    }
    $summaryParts[] = $k . ':' . trim((string) $v);
}
$summary = implode(' | ', $summaryParts);

$res = ToolFunctions::generate($slug, $input);

if ($res['ok']) {
    Tools::logUsage(
        $slug,
        (int) $user['id'],
        (string) ($res['provider'] ?? 'gemini'),
        (string) ($res['model'] ?? ''),
        $summary,
        'success',
        null,
        (int) ($res['latency_ms'] ?? 0)
    );
    json_response([
        'ok'         => true,
        'tool'       => $tool['name'],
        'latency_ms' => $res['latency_ms'] ?? 0,
        'result'     => $res['result'],
    ]);
}

Tools::logUsage(
    $slug,
    (int) $user['id'],
    (string) ($res['provider'] ?? get_setting('tools_ai_provider', 'gemini')),
    (string) ($res['model'] ?? get_setting('tools_ai_model', '')),
    $summary,
    'failed',
    (string) ($res['message'] ?? 'Unknown error'),
    (int) ($res['latency_ms'] ?? 0)
);

$httpCode = ($res['code'] ?? '') === 'NOT_CONFIGURED' ? 503 : (($res['code'] ?? '') === 'VALIDATION' ? 422 : 500);
json_response([
    'ok'      => false,
    'code'    => $res['code'] ?? 'ERROR',
    'message' => $res['message'] ?? 'Unable to generate output.',
], $httpCode);
