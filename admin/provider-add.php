<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
$admin = require_admin('manage_providers');
require_once __DIR__ . '/../classes/Provider.php';

$currencies = Currency::all(false, false);
$baseCode = Currency::getBaseCode();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();
    $name = sanitize_string($_POST['name'] ?? '', 120);
    $apiUrl = trim((string) ($_POST['api_url'] ?? ''));
    $apiKey = trim((string) ($_POST['api_key'] ?? ''));
    $currency = strtoupper(sanitize_string($_POST['currency'] ?? $baseCode, 10));

    if ($name === '' || !filter_var($apiUrl, FILTER_VALIDATE_URL) || $apiKey === '') {
        $error = 'Provider name, valid API URL (https://...), and API key are required.';
    } else {
        $id = Provider::create([
            'name'            => $name,
            'api_url'         => $apiUrl,
            'api_key'         => $apiKey,
            'api_type'        => 'standard',
            'currency'        => $currency ?: $baseCode,
            'currency_source' => 'manual',
            'status'          => $_POST['status'] ?? 'active',
            'notes'           => sanitize_string($_POST['notes'] ?? '', 1000),
        ]);
        log_activity('admin', (int) $admin['id'], 'provider_created', 'provider', $id, "{$name} ({$currency})");
        set_flash('success', 'Provider added. Run "Test & Check Balance" to verify connectivity and currency.');
        redirect('providers.php');
    }
}

$pageTitle = 'Add Provider';
require __DIR__ . '/../includes/admin-header.php';
?>
<div class="max-w-xl bg-white border border-slate-200 rounded-2xl p-6 space-y-4">
  <a href="providers.php" class="text-xs font-semibold text-[#7138D8] hover:underline">&larr; Back to Providers</a>
  <h2 class="text-lg font-bold text-[#151515]">Add API Provider</h2>
  <?php if ($error): ?>
    <div class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-700 font-medium"><?= e($error) ?></div>
  <?php endif; ?>
  <form method="POST" action="provider-add.php" class="space-y-4">
    <?= csrf_field() ?>
    <div>
      <label class="block text-xs font-semibold text-slate-700 mb-1">Provider Name</label>
      <input type="text" name="name" required placeholder="e.g. Primary Wholesale Panel" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
    </div>
    <div>
      <label class="block text-xs font-semibold text-slate-700 mb-1">API Endpoint URL</label>
      <input type="url" name="api_url" required placeholder="https://provider-domain.com/api/v2" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-mono-num">
    </div>
    <div>
      <label class="block text-xs font-semibold text-slate-700 mb-1">API Key (Stored Encrypted)</label>
      <input type="password" name="api_key" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-mono-num">
    </div>
    <div class="grid grid-cols-2 gap-4">
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Provider Currency</label>
        <select name="currency" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm bg-white">
          <?php foreach ($currencies as $c): ?>
            <option value="<?= e($c['code']) ?>" <?= $c['code'] === $baseCode ? 'selected' : '' ?>><?= e($c['code']) ?> — <?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Status</label>
        <select name="status" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm bg-white">
          <option value="active">Active</option>
          <option value="disabled">Disabled</option>
        </select>
      </div>
    </div>
    <div>
      <label class="block text-xs font-semibold text-slate-700 mb-1">Notes</label>
      <textarea name="notes" rows="2" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm"></textarea>
    </div>
    <button type="submit" class="px-6 py-2.5 rounded-xl bg-[#7138D8] text-white text-sm font-semibold hover:bg-[#5B24C0]">Save Provider</button>
  </form>
</div>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
