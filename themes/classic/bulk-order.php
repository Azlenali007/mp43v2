<?php
declare(strict_types=1);
if (!defined('APP_ROOT')) {
    http_response_code(403);
    exit('Forbidden');
}
$pageTitle = $pageTitle ?? 'Bulk Orders';
ob_start();
?>
<div class="max-w-4xl space-y-6">
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h1 class="text-2xl font-bold text-[#151515]">Bulk Order</h1>
      <p class="text-sm text-slate-600 mt-1">Submit up to 50 orders in one batch using pipe-separated lines.</p>
    </div>
    <a href="<?= e($rp) ?>services.php" class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-[#151515] hover:bg-slate-50 self-start">View Service IDs</a>
  </div>

  <?php if (!empty($error)): ?>
    <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-sm text-rose-700 font-medium"><?= e($error) ?></div>
  <?php endif; ?>

  <?php if (!empty($results)): ?>
    <div class="bg-white border border-slate-200 rounded-2xl p-5 space-y-2">
      <h2 class="text-sm font-bold text-[#151515]">Batch Processing Results</h2>
      <div class="divide-y divide-slate-100 text-xs">
        <?php foreach ($results as $r): ?>
          <div class="py-2 flex items-center justify-between gap-3">
            <span class="font-mono-num text-slate-500">Line <?= (int) $r['line'] ?></span>
            <span class="flex-1 <?= $r['ok'] ? 'text-[#16A36A] font-semibold' : 'text-rose-600 font-medium' ?>"><?= e($r['message']) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <div class="bg-white border border-slate-200 rounded-2xl p-6">
    <form method="POST" action="<?= e($rp) ?>bulk-order.php" class="space-y-4">
      <?= csrf_field() ?>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">One order per line: <code class="font-mono-num text-[#7138D8]">service_id | link | quantity</code></label>
        <textarea name="bulk_lines" rows="8" placeholder="1 | https://instagram.com/example | 1000&#10;2 | https://youtube.com/watch?v=example | 500" class="w-full p-4 rounded-xl border border-slate-200 font-mono-num text-xs focus:outline-none focus:border-[#7138D8]"><?= e($_POST['bulk_lines'] ?? '') ?></textarea>
      </div>
      <div class="flex items-center justify-between">
        <span class="text-xs text-slate-500">Wallet Balance: <strong class="text-[#16A36A] font-mono-num"><?= e(format_currency($user['wallet_balance'])) ?></strong></span>
        <button type="submit" class="px-6 py-2.5 rounded-xl bg-[#7138D8] text-white text-sm font-semibold hover:bg-[#5B24C0] transition">
          Submit Bulk Orders
        </button>
      </div>
    </form>
  </div>
</div>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/layouts/dashboard.php';
