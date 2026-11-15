<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../tools/includes/ai-client.php';
$admin = require_admin();

$pdo = Database::getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();
    $action = $_POST['action'] ?? '';

    if ($action === 'save_ai_config') {
        $provider = in_array($_POST['tools_ai_provider'] ?? '', ['gemini', 'openai_compatible'], true)
            ? (string) $_POST['tools_ai_provider'] : 'gemini';
        $model = sanitize_string($_POST['tools_ai_model'] ?? 'gemini-2.5-flash', 120);
        $endpoint = trim((string) ($_POST['tools_ai_endpoint'] ?? ''));
        $apiKey = trim((string) ($_POST['tools_ai_api_key'] ?? ''));
        $rateLimit = max(1, min(500, (int) ($_POST['tools_rate_limit_per_hour'] ?? 30)));
        $timeout = max(5, min(90, (int) ($_POST['tools_ai_timeout'] ?? 30)));

        set_setting('tools_ai_provider', $provider, 'tools');
        set_setting('tools_ai_model', $model, 'tools');
        set_setting('tools_ai_endpoint', $endpoint, 'tools');
        set_setting('tools_rate_limit_per_hour', (string) $rateLimit, 'tools');
        set_setting('tools_ai_timeout', (string) $timeout, 'tools');
        if ($apiKey !== '') {
            set_setting('tools_ai_api_key', encrypt_secret($apiKey), 'tools');
        }
        log_activity('admin', (int) $admin['id'], 'tools_ai_updated', 'settings', null, "Updated AI settings ({$provider} / {$model})");
        set_flash('success', 'AI integration settings saved.');
    } elseif ($action === 'test_ai') {
        $test = AIClient::testConnection();
        set_flash($test['ok'] ? 'success' : 'error', $test['message'] ?? 'AI test failed.');
    } elseif ($action === 'toggle_tool') {
        $toolId = (int) ($_POST['tool_id'] ?? 0);
        $pdo->prepare('UPDATE tools SET is_enabled = IF(is_enabled = 1, 0, 1) WHERE id = ?')->execute([$toolId]);
        log_activity('admin', (int) $admin['id'], 'tool_toggled', 'tool', $toolId);
        set_flash('success', 'Tool availability updated.');
    } elseif ($action === 'update_tool') {
        $toolId = (int) ($_POST['tool_id'] ?? 0);
        $name = sanitize_string($_POST['name'] ?? '', 120);
        $desc = sanitize_string($_POST['description'] ?? '', 500);
        $icon = sanitize_string($_POST['icon_key'] ?? 'sparkles', 50);
        $sort = (int) ($_POST['sort_order'] ?? 0);
        $isEnabled = isset($_POST['is_enabled']) ? 1 : 0;
        if ($name === '') {
            set_flash('error', 'Tool name cannot be empty.');
        } else {
            $pdo->prepare('UPDATE tools SET name = ?, description = ?, icon_key = ?, sort_order = ?, is_enabled = ? WHERE id = ?')
                ->execute([$name, $desc, $icon, $sort, $isEnabled, $toolId]);
            log_activity('admin', (int) $admin['id'], 'tool_updated', 'tool', $toolId, "Updated tool: {$name}");
            set_flash('success', 'Tool metadata saved.');
        }
    }
    redirect('tools.php');
}

$tools = Tools::all(false);
$aiCfg = AIClient::getConfig();
$icons = icon_registry();
$logs = $pdo->query(
    'SELECT l.*, u.email AS user_email
     FROM tool_usage_logs l
     LEFT JOIN users u ON u.id = l.user_id
     ORDER BY l.created_at DESC
     LIMIT 30'
)->fetchAll();

$pageTitle = 'Creator Tools & AI Integration';
require __DIR__ . '/../includes/admin-header.php';
?>
<div class="space-y-6">
  <div>
    <h1 class="text-2xl font-bold text-[#151515]">Creator Tools &amp; AI Settings</h1>
    <p class="text-sm text-slate-600 mt-1">Manage the 3 built-in creator tools, configure live AI credentials, and monitor real usage logs.</p>
  </div>

  <!-- AI Configuration -->
  <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-5">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <div>
        <h2 class="text-base font-bold text-[#151515]">AI Provider Configuration</h2>
        <p class="text-xs text-slate-500 mt-0.5">
          Key Status:
          <?php if ($aiCfg['has_key']): ?>
            <span class="font-semibold text-[#16A36A]">Configured (AES-256-GCM Encrypted)</span>
          <?php else: ?>
            <span class="font-semibold text-amber-700">Not Configured (Tools will instruct users honestly until a key is saved)</span>
          <?php endif; ?>
        </p>
      </div>
      <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="test_ai">
        <button type="submit" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-[#151515] hover:bg-slate-50">
          Test AI Connection
        </button>
      </form>
    </div>

    <form method="POST" class="grid md:grid-cols-2 gap-4">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save_ai_config">

      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">AI Provider</label>
        <select name="tools_ai_provider" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
          <option value="gemini" <?= $aiCfg['provider'] === 'gemini' ? 'selected' : '' ?>>Google Gemini REST API</option>
          <option value="openai_compatible" <?= $aiCfg['provider'] === 'openai_compatible' ? 'selected' : '' ?>>OpenAI-Compatible Chat Completions API</option>
        </select>
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Model Name</label>
        <input type="text" name="tools_ai_model" value="<?= e($aiCfg['model']) ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-mono-num">
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Endpoint URL (optional override)</label>
        <input type="text" name="tools_ai_endpoint" value="<?= e(get_setting('tools_ai_endpoint', '')) ?>" placeholder="Default used if left blank ({MODEL} supported)" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-mono-num">
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">API Key <?= $aiCfg['has_key'] ? '(leave blank to keep existing key)' : '' ?></label>
        <input type="password" name="tools_ai_api_key" autocomplete="new-password" placeholder="<?= $aiCfg['has_key'] ? '••••••••••••••••' : 'Enter live API key...' ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-mono-num">
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Hourly Rate Limit (requests/user/hr)</label>
        <input type="number" name="tools_rate_limit_per_hour" min="1" max="500" value="<?= e(get_setting('tools_rate_limit_per_hour', '30')) ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-mono-num">
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Timeout (seconds)</label>
        <input type="number" name="tools_ai_timeout" min="5" max="90" value="<?= (int) $aiCfg['timeout'] ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-mono-num">
      </div>

      <div class="md:col-span-2">
        <button type="submit" class="px-6 py-2.5 rounded-xl bg-[#7138D8] hover:bg-[#5E28C2] text-white text-sm font-semibold">
          Save AI Settings
        </button>
      </div>
    </form>
  </div>

  <!-- Registered Tools (3 Core Tools) -->
  <div class="grid md:grid-cols-3 gap-6">
    <?php foreach ($tools as $t): ?>
      <form method="POST" class="bg-white border border-slate-200 rounded-2xl p-5 space-y-4">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="update_tool">
        <input type="hidden" name="tool_id" value="<?= (int) $t['id'] ?>">

        <div class="flex items-center justify-between gap-2">
          <div class="flex items-center gap-2.5">
            <?= render_icon_badge((string) $t['icon_key'], 'w-9 h-9', 'p-2') ?>
            <span class="text-xs font-mono-num text-slate-500"><?= e($t['slug']) ?></span>
          </div>
          <span class="px-2 py-0.5 rounded-md text-[11px] font-bold <?= (int)$t['is_enabled'] === 1 ? 'bg-emerald-50 text-[#16A36A]' : 'bg-slate-100 text-slate-600' ?>">
            <?= (int)$t['is_enabled'] === 1 ? 'Enabled' : 'Disabled' ?>
          </span>
        </div>

        <div>
          <label class="block text-[11px] font-semibold text-slate-500 mb-1">Display Title</label>
          <input type="text" name="name" value="<?= e($t['name']) ?>" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-semibold">
        </div>

        <div>
          <label class="block text-[11px] font-semibold text-slate-500 mb-1">Description</label>
          <textarea name="description" rows="2" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs"><?= e($t['description']) ?></textarea>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-[11px] font-semibold text-slate-500 mb-1">Icon</label>
            <select name="icon_key" class="w-full px-2.5 py-2 rounded-xl border border-slate-200 text-xs">
              <?php foreach ($icons as $k => $meta): ?>
                <option value="<?= e($k) ?>" <?= $t['icon_key'] === $k ? 'selected' : '' ?>><?= e($meta['label']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-slate-500 mb-1">Sort Order</label>
            <input type="number" name="sort_order" value="<?= (int) $t['sort_order'] ?>" class="w-full px-2.5 py-2 rounded-xl border border-slate-200 text-xs font-mono-num">
          </div>
        </div>

        <label class="flex items-center gap-2 text-xs font-medium text-slate-700">
          <input type="checkbox" name="is_enabled" value="1" <?= (int)$t['is_enabled'] === 1 ? 'checked' : '' ?> class="rounded border-slate-300 text-[#7138D8]">
          <span>Enable tool for users</span>
        </label>

        <div class="flex items-center justify-between pt-2 border-t border-slate-100">
          <span class="text-[11px] text-slate-500 font-mono-num"><?= number_format((int)$t['usage_count']) ?> runs</span>
          <button type="submit" class="px-4 py-1.5 rounded-xl bg-[#151515] text-white text-xs font-semibold hover:bg-black">
            Save Tool
          </button>
        </div>
      </form>
    <?php endforeach; ?>
  </div>

  <!-- Recent Tool Usage Logs -->
  <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100">
      <h2 class="text-base font-bold text-[#151515]">Recent Tool Usage Logs</h2>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-[#F8F9FC] text-xs text-slate-500 uppercase">
          <tr>
            <th class="px-5 py-3 text-left">Tool</th>
            <th class="px-5 py-3 text-left">User</th>
            <th class="px-5 py-3 text-left">Provider / Model</th>
            <th class="px-5 py-3 text-left">Input Summary</th>
            <th class="px-5 py-3 text-left">Status</th>
            <th class="px-5 py-3 text-right">Latency</th>
            <th class="px-5 py-3 text-right">Time</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <?php if (empty($logs)): ?>
            <tr><td colspan="7" class="px-5 py-8 text-center text-xs text-slate-500">No tool executions logged yet.</td></tr>
          <?php else: ?>
            <?php foreach ($logs as $log): ?>
              <tr>
                <td class="px-5 py-3 text-xs font-semibold text-[#151515]"><?= e($log['tool_slug']) ?></td>
                <td class="px-5 py-3 text-xs text-slate-600"><?= e($log['user_email'] ?? 'Guest') ?></td>
                <td class="px-5 py-3 text-xs font-mono-num text-slate-600"><?= e($log['provider']) ?> / <?= e($log['model']) ?></td>
                <td class="px-5 py-3 text-xs text-slate-500 max-w-xs truncate"><?= e($log['input_summary']) ?></td>
                <td class="px-5 py-3 text-xs">
                  <?php if ($log['status'] === 'success'): ?>
                    <span class="px-2 py-0.5 rounded-md bg-emerald-50 text-[#16A36A] font-semibold">Success</span>
                  <?php else: ?>
                    <span class="px-2 py-0.5 rounded-md bg-rose-50 text-rose-700 font-semibold" title="<?= e((string)$log['error_message']) ?>">Failed</span>
                  <?php endif; ?>
                </td>
                <td class="px-5 py-3 text-xs font-mono-num text-right"><?= (int) $log['latency_ms'] ?> ms</td>
                <td class="px-5 py-3 text-xs text-slate-400 text-right"><?= e(time_ago($log['created_at'])) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
