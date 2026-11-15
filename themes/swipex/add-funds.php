<?php
declare(strict_types=1);
if (!defined('APP_ROOT')) {
    http_response_code(403);
    exit('Forbidden');
}
$pageTitle = $pageTitle ?? 'Add Funds';
ob_start();
?>
<div class="space-y-6 max-w-2xl mx-auto">
  <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200/80 text-center">
    <span class="text-xs font-semibold text-blue-600 uppercase tracking-wider">Wallet Top-up</span>
    <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight mt-1">Add Funds to Your Account</h1>
    <p class="text-xs text-slate-500 mt-0.5">Select a secure payment method and top-up your balance instantly.</p>
  </div>

  <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border border-slate-200/80">
    <form method="POST" action="<?= e($rp) ?>add-funds.php" class="space-y-5">
      <input type="hidden" name="csrf_token" value="<?= e($csrfToken ?? csrf_token()) ?>">
      
      <div>
        <label class="block text-xs font-bold text-slate-700 mb-2">Select Payment Method</label>
        <select name="method_id" class="w-full px-4 py-3 rounded-2xl bg-slate-50 border border-slate-200 text-xs font-bold text-slate-800 focus:outline-none focus:border-blue-600">
          <?php foreach ($methods ?? [] as $m): ?>
            <option value="<?= (int) $m['id'] ?>"><?= e($m['name']) ?> (Min: <?= e(format_currency($m['min_amount'])) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-700 mb-2">Amount (<?= e($displayCurrency['code'] ?? 'INR') ?>)</label>
        <input type="number" step="0.01" min="1" name="amount" required placeholder="0.00" class="w-full px-4 py-3 rounded-2xl bg-slate-50 border border-slate-200 text-xs font-bold text-slate-800 focus:outline-none focus:border-blue-600 font-mono-num">
      </div>

      <button type="submit" class="w-full py-4 rounded-2xl bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs shadow-lg shadow-blue-500/25 transition">
        Proceed to Payment
      </button>
    </form>
  </div>
</div>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/layouts/dashboard.php';
