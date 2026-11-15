<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
$admin = require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();
    set_setting('site_name', sanitize_string($_POST['site_name'] ?? 'CrownSMM', 80), 'general');
    set_setting('site_tagline', sanitize_string($_POST['site_tagline'] ?? '', 180), 'general');
    set_setting('support_email', sanitize_string($_POST['support_email'] ?? '', 190), 'general');
    set_setting('currency_code', strtoupper(sanitize_string($_POST['currency_code'] ?? 'INR', 10)), 'general');
    set_setting('currency_symbol', sanitize_string($_POST['currency_symbol'] ?? '₹', 5), 'general');
    set_setting('default_margin_percent', (string) max(0, (float) ($_POST['default_margin_percent'] ?? 25)), 'pricing');
    set_setting('registration_enabled', !empty($_POST['registration_enabled']) ? '1' : '0', 'general');
    log_activity('admin', (int) $admin['id'], 'settings_updated');
    set_flash('success', 'System settings updated.');
    redirect('settings.php');
}

$cronToken = get_setting('cron_token', '');
$baseUrl = app_base_url();

$pageTitle = 'System Settings';
require __DIR__ . '/../includes/admin-header.php';
?>
<div class="max-w-3xl space-y-6">
  <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-4">
    <h2 class="text-lg font-bold text-[#151515]">General Settings</h2>
    <form method="POST" action="settings.php" class="space-y-4">
      <?= csrf_field() ?>
      <div class="grid sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Site Name</label>
          <input type="text" name="site_name" value="<?= e(get_setting('site_name', 'CrownSMM')) ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Support Email</label>
          <input type="email" name="support_email" value="<?= e(get_setting('support_email', '')) ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
        </div>
        <div class="sm:col-span-2">
          <label class="block text-xs font-semibold text-slate-700 mb-1">Tagline</label>
          <input type="text" name="site_tagline" value="<?= e(get_setting('site_tagline', '')) ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Currency Code</label>
          <input type="text" name="currency_code" value="<?= e(get_setting('currency_code', 'INR')) ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Currency Symbol</label>
          <input type="text" name="currency_symbol" value="<?= e(get_setting('currency_symbol', '₹')) ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Default Import Margin (%)</label>
          <input type="number" step="0.5" min="0" name="default_margin_percent" value="<?= e(get_setting('default_margin_percent', '25')) ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-mono-num">
        </div>
      </div>
      <label class="flex items-center gap-2 text-xs font-semibold text-slate-700">
        <input type="checkbox" name="registration_enabled" value="1" <?= get_setting('registration_enabled', '1') === '1' ? 'checked' : '' ?>> Allow New User Registrations
      </label>
      <button type="submit" class="px-6 py-2.5 rounded-xl bg-[#7138D8] text-white text-sm font-semibold hover:bg-[#5B24C0]">Save Settings</button>
    </form>
  </div>

  <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-3">
    <h3 class="text-base font-bold text-[#151515]">cPanel Cron Job Endpoints</h3>
    <p class="text-xs text-slate-600">Configure these URLs or CLI PHP paths in cPanel Cron Jobs to automate order syncing, rate synchronization, and refunds.</p>
    <div class="space-y-2 text-xs font-mono-num">
      <div class="p-3 rounded-xl bg-[#F8F9FC] border border-slate-200 break-all"><?= e($baseUrl) ?>/cron/update-orders.php?token=<?= e($cronToken) ?></div>
      <div class="p-3 rounded-xl bg-[#F8F9FC] border border-slate-200 break-all"><?= e($baseUrl) ?>/cron/sync-services.php?token=<?= e($cronToken) ?></div>
      <div class="p-3 rounded-xl bg-[#F8F9FC] border border-slate-200 break-all"><?= e($baseUrl) ?>/cron/process-refunds.php?token=<?= e($cronToken) ?></div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
