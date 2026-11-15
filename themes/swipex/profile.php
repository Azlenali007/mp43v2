<?php
declare(strict_types=1);
if (!defined('APP_ROOT')) {
    http_response_code(403);
    exit('Forbidden');
}
$pageTitle = $pageTitle ?? 'Profile Settings';
ob_start();
?>
<div class="space-y-6 max-w-2xl mx-auto">
  <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200/80 text-center">
    <div class="w-20 h-20 rounded-full bg-gradient-to-tr from-blue-600 to-indigo-600 text-white text-2xl font-black flex items-center justify-center mx-auto shadow-lg shadow-blue-500/30 mb-3">
      <?= e($initials ?? 'U') ?>
    </div>
    <h1 class="text-xl font-black text-slate-900"><?= e($user['name'] ?? 'User') ?></h1>
    <p class="text-xs text-slate-500 font-mono-num mt-0.5"><?= e($user['email'] ?? '') ?> &bull; ID: #<?= (int) ($user['id'] ?? 0) ?></p>
  </div>

  <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border border-slate-200/80 space-y-6">
    <h2 class="text-sm font-extrabold text-slate-900 border-b border-slate-100 pb-3">Account & Currency Settings</h2>
    <form method="POST" action="<?= e($rp) ?>profile.php" class="space-y-4">
      <input type="hidden" name="csrf_token" value="<?= e($csrfToken ?? csrf_token()) ?>">
      <input type="hidden" name="action" value="update_profile">
      
      <div>
        <label class="block text-xs font-bold text-slate-700 mb-1.5">Full Name</label>
        <input type="text" name="name" value="<?= e($user['name'] ?? '') ?>" required class="w-full px-4 py-3 rounded-2xl bg-slate-50 border border-slate-200 text-xs font-bold text-slate-800 focus:outline-none focus:border-blue-600">
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-700 mb-1.5">Preferred Display Currency</label>
        <select name="display_currency" class="w-full px-4 py-3 rounded-2xl bg-slate-50 border border-slate-200 text-xs font-bold text-slate-800 focus:outline-none focus:border-blue-600">
          <?php foreach ($readyCurrencies ?? [] as $rc): ?>
            <option value="<?= e($rc['code']) ?>" <?= (($user['display_currency'] ?? 'INR') === $rc['code']) ? 'selected' : '' ?>>
              <?= e($rc['code'] . ' (' . $rc['symbol'] . ')') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <button type="submit" class="w-full py-3.5 rounded-2xl bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs shadow-md shadow-blue-500/20 transition">
        Save Profile Changes
      </button>
    </form>
  </div>
</div>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/layouts/dashboard.php';
