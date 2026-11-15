<?php
declare(strict_types=1);
if (!defined('APP_ROOT')) {
    http_response_code(403);
    exit('Forbidden');
}
$pageTitle = $pageTitle ?? 'Transactions';
ob_start();
?>
<div class="space-y-6">
  <div class="flex items-center justify-between gap-4">
    <div>
      <h1 class="text-2xl font-bold text-white">Wallet Ledger</h1>
      <p class="text-sm text-slate-400 mt-1">Complete atomic ledger of all credits, order debits, and refunds.</p>
    </div>
    <a href="<?= e($rp) ?>add-funds.php" class="px-5 py-2.5 rounded-xl aurora-btn-primary text-sm font-semibold">+ Add Funds</a>
  </div>

  <?php if (empty($txs)): ?>
    <div class="aurora-surface rounded-2xl p-10 text-center">
      <p class="text-base font-semibold text-white">No Transactions Yet</p>
      <p class="text-sm text-slate-400 mt-1">Your wallet deposits, order deductions, and refunds will be logged here.</p>
    </div>
  <?php else: ?>
    <div class="aurora-surface rounded-2xl overflow-x-auto">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="border-b border-slate-800 bg-[#0B1220]/70 text-xs font-semibold text-slate-400">
            <th class="py-3.5 px-4">ID</th>
            <th class="py-3.5 px-4">Type</th>
            <th class="py-3.5 px-4">Description</th>
            <th class="py-3.5 px-4 text-right">Amount</th>
            <th class="py-3.5 px-4 text-right">Balance After</th>
            <th class="py-3.5 px-4 text-right">Date</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-800/80 text-sm">
          <?php foreach ($txs as $t): ?>
            <?php $isPositive = in_array($t['type'], ['credit', 'refund'], true); ?>
            <tr class="hover:bg-white/[0.02]">
              <td class="py-3.5 px-4 font-mono-num text-xs text-slate-400">#<?= (int) $t['id'] ?></td>
              <td class="py-3.5 px-4 uppercase text-xs font-semibold <?= $isPositive ? 'text-teal-300' : 'text-rose-400' ?>"><?= e($t['type']) ?></td>
              <td class="py-3.5 px-4 text-xs text-slate-300"><?= e($t['description']) ?></td>
              <td class="py-3.5 px-4 text-right font-mono-num font-semibold <?= $isPositive ? 'text-teal-300' : 'text-rose-400' ?>">
                <?php if (!empty($t['display_currency']) && $t['displayed_amount'] !== null): ?>
                  <span><?= $isPositive ? '+' : '-' ?><?= e(format_exact_currency((float) $t['displayed_amount'], (string) $t['display_currency'], 4)) ?> <?= e($t['display_currency']) ?></span>
                  <?php $txBase = $t['base_currency'] ?? Currency::getBaseCode(); ?>
                  <?php if (strtoupper((string) $t['display_currency']) !== strtoupper((string) $txBase)): ?>
                    <span class="block text-[10px] text-slate-400 font-normal">Base: <?= e(format_exact_currency((float) $t['amount'], (string) $txBase, 4)) ?> <?= e($txBase) ?></span>
                  <?php endif; ?>
                <?php else: ?>
                  <?= $isPositive ? '+' : '-' ?><?= e(format_currency($t['amount'])) ?>
                <?php endif; ?>
              </td>
              <td class="py-3.5 px-4 text-right font-mono-num text-xs text-white"><?= e(format_currency($t['balance_after'])) ?></td>
              <td class="py-3.5 px-4 text-right text-xs text-slate-400 whitespace-nowrap"><?= e(date('d M Y, H:i', strtotime($t['created_at']))) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/layouts/dashboard.php';
