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
      <h1 class="text-2xl font-bold text-white">Bulk Order Dispatch</h1>
      <p class="text-sm text-slate-400 mt-1">Submit up to 50 pipe-separated orders in one atomic batch.</p>
    </div>
    <a href="<?= e($rp) ?>services.php" class="px-4 py-2 rounded-xl border border-slate-700 bg-[#111A2E] text-xs font-semibold text-white hover:border-teal-400/50 self-start">View Service IDs</a>
  </div>

  <?php if (!empty($error)): ?>
    <div class="p-4 rounded-xl bg-rose-950/70 border border-rose-500/40 text-sm text-rose-200 font-medium"><?= e($error) ?></div>
  <?php endif; ?>

  <?php if (!empty($results)): ?>
    <div class="aurora-surface rounded-2xl p-5 space-y-2">
      <h2 class="text-sm font-bold text-white">Batch Results</h2>
      <div class="divide-y divide-slate-800 text-xs">
        <?php foreach ($results as $r): ?>
          <div class="py-2 flex items-center justify-between gap-3">
            <span class="font-mono-num text-slate-400">Line <?= (int) $r['line'] ?></span>
            <span class="flex-1 <?= $r['ok'] ? 'text-teal-300 font-semibold' : 'text-rose-300 font-medium' ?>"><?= e($r['message']) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <div class="aurora-surface rounded-2xl p-6">
    <form method="POST" action="<?= e($rp) ?>bulk-order.php" class="space-y-4">
      <?= csrf_field() ?>
      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Format per line: <code class="font-mono-num text-teal-300">service_id | link | quantity</code></label>
        <textarea name="bulk_lines" rows="8" placeholder="1 | https://instagram.com/example | 1000&#10;2 | https://youtube.com/watch?v=example | 500" class="w-full p-4 rounded-xl aurora-input border font-mono-num text-xs"><?= e($_POST['bulk_lines'] ?? '') ?></textarea>
      </div>
      <div class="flex items-center justify-between">
        <span class="text-xs text-slate-400">Wallet Balance: <strong class="text-teal-300 font-mono-num"><?= e(format_currency($user['wallet_balance'])) ?></strong></span>
        <button type="submit" class="px-6 py-2.5 rounded-xl aurora-btn-primary text-sm font-semibold transition">
          Submit Bulk Orders
        </button>
      </div>
    </form>
  </div>
</div>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/layouts/dashboard.php';
