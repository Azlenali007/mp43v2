<?php
declare(strict_types=1);
if (!defined('APP_ROOT')) {
    http_response_code(403);
    exit('Forbidden');
}
$pageTitle = $pageTitle ?? 'Account Settings';
$allEnabledCurrencies = $enabledCurrencies ?? Currency::all(true, false);
$activeDispCode = (string) (($displayCurrency['code'] ?? '') ?: Currency::getBaseCode());
ob_start();
?>
<div class="max-w-4xl grid md:grid-cols-2 gap-6 items-start">
  <div class="aurora-surface rounded-2xl p-6 space-y-4">
    <div>
      <h1 class="text-lg font-bold text-white">Profile Settings</h1>
      <p class="text-xs text-slate-400 mt-0.5">Configure account metadata and preferred FX display currency.</p>
    </div>
    <form method="POST" action="<?= e($rp) ?>profile.php" class="space-y-4">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="update_profile">
      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Full Name</label>
        <input type="text" name="name" value="<?= e($user['name'] ?? '') ?>" required class="w-full px-3.5 py-2.5 rounded-xl aurora-input border text-sm">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Username</label>
        <input type="text" value="<?= e($user['username'] ?? '') ?>" disabled class="w-full px-3.5 py-2.5 rounded-xl aurora-input border text-sm opacity-60">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Email</label>
        <input type="email" value="<?= e($user['email'] ?? '') ?>" disabled class="w-full px-3.5 py-2.5 rounded-xl aurora-input border text-sm opacity-60">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Phone</label>
        <input type="text" name="phone" value="<?= e($user['phone'] ?? '') ?>" class="w-full px-3.5 py-2.5 rounded-xl aurora-input border text-sm">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Timezone</label>
        <input type="text" name="timezone" value="<?= e($user['timezone'] ?? 'Asia/Kolkata') ?>" class="w-full px-3.5 py-2.5 rounded-xl aurora-input border text-sm">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Preferred Display Currency</label>
        <select name="preferred_currency" class="w-full px-3.5 py-2.5 rounded-xl aurora-input border text-sm">
          <?php foreach ($allEnabledCurrencies as $rc): ?>
            <?php $rateReady = (float) ($rc['exchange_rate'] ?? 0) > 0; ?>
            <option value="<?= e($rc['code']) ?>" <?= $activeDispCode === $rc['code'] ? 'selected' : '' ?> <?= !$rateReady ? 'disabled' : '' ?>>
              <?= e($rc['code']) ?> — <?= e($rc['name']) ?> (<?= e($rc['symbol']) ?>)<?= !$rateReady ? ' [FX Sync Required]' : '' ?>
            </option>
          <?php endforeach; ?>
        </select>
        <p class="mt-1 text-[11px] text-slate-400">Your currency selection updates service rates and wallet figures globally.</p>
      </div>
      <button type="submit" class="px-5 py-2.5 rounded-xl aurora-btn-primary text-sm font-semibold transition">Save Changes</button>
    </form>
  </div>

  <div class="aurora-surface rounded-2xl p-6 space-y-4">
    <div>
      <h2 class="text-lg font-bold text-white">Change Password</h2>
      <p class="text-xs text-slate-400 mt-0.5">Protect your console and API credentials.</p>
    </div>
    <form method="POST" action="<?= e($rp) ?>profile.php" class="space-y-4">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="change_password">
      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Current Password</label>
        <input type="password" name="current_password" required class="w-full px-3.5 py-2.5 rounded-xl aurora-input border text-sm">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1.5">New Password (min 8 chars)</label>
        <input type="password" name="new_password" minlength="8" required class="w-full px-3.5 py-2.5 rounded-xl aurora-input border text-sm">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Confirm New Password</label>
        <input type="password" name="confirm_password" minlength="8" required class="w-full px-3.5 py-2.5 rounded-xl aurora-input border text-sm">
      </div>
      <button type="submit" class="px-5 py-2.5 rounded-xl bg-slate-800 border border-slate-700 text-white text-sm font-semibold hover:bg-slate-700 transition">Update Password</button>
    </form>
  </div>
</div>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/layouts/dashboard.php';
