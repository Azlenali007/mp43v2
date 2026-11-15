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
  <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-4">
    <div>
      <h1 class="text-lg font-bold text-[#151515]">Profile Settings</h1>
      <p class="text-xs text-slate-500 mt-0.5">Update your personal details, timezone, and preferred display currency.</p>
    </div>
    <form method="POST" action="<?= e($rp) ?>profile.php" class="space-y-4">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="update_profile">
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Full Name</label>
        <input type="text" name="name" value="<?= e($user['name'] ?? '') ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-[#7138D8]">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Username</label>
        <input type="text" value="<?= e($user['username'] ?? '') ?>" disabled class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-500">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Email</label>
        <input type="email" value="<?= e($user['email'] ?? '') ?>" disabled class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-500">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Phone</label>
        <input type="text" name="phone" value="<?= e($user['phone'] ?? '') ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-[#7138D8]">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Timezone</label>
        <input type="text" name="timezone" value="<?= e($user['timezone'] ?? 'Asia/Kolkata') ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-[#7138D8]">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Preferred Display Currency</label>
        <select name="preferred_currency" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm bg-white focus:outline-none focus:border-[#7138D8]">
          <?php foreach ($allEnabledCurrencies as $rc): ?>
            <?php $rateReady = (float) ($rc['exchange_rate'] ?? 0) > 0; ?>
            <option value="<?= e($rc['code']) ?>" <?= $activeDispCode === $rc['code'] ? 'selected' : '' ?> <?= !$rateReady ? 'disabled' : '' ?>>
              <?= e($rc['code']) ?> — <?= e($rc['name']) ?> (<?= e($rc['symbol']) ?>)<?= !$rateReady ? ' [FX Sync Required]' : '' ?>
            </option>
          <?php endforeach; ?>
        </select>
        <p class="mt-1 text-[11px] text-slate-500">Prices and wallet balances display in your selected currency across the entire panel.</p>
      </div>
      <button type="submit" class="px-5 py-2.5 rounded-xl bg-[#7138D8] text-white text-sm font-semibold hover:bg-[#5B24C0] transition">Save Changes</button>
    </form>
  </div>

  <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-4">
    <div>
      <h2 class="text-lg font-bold text-[#151515]">Change Password</h2>
      <p class="text-xs text-slate-500 mt-0.5">Use at least 8 characters to keep your wallet and API key secure.</p>
    </div>
    <form method="POST" action="<?= e($rp) ?>profile.php" class="space-y-4">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="change_password">
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Current Password</label>
        <input type="password" name="current_password" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-[#7138D8]">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">New Password (min 8 chars)</label>
        <input type="password" name="new_password" minlength="8" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-[#7138D8]">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Confirm New Password</label>
        <input type="password" name="confirm_password" minlength="8" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-[#7138D8]">
      </div>
      <button type="submit" class="px-5 py-2.5 rounded-xl bg-[#151515] text-white text-sm font-semibold hover:bg-black transition">Update Password</button>
    </form>
  </div>
</div>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/layouts/dashboard.php';
