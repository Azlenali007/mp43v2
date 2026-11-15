<?php
declare(strict_types=1);
if (!defined('APP_ROOT')) {
    http_response_code(403);
    exit('Forbidden');
}
$pageTitle = $pageTitle ?? 'Wallet Transactions';
ob_start();
?>
<div class="space-y-6">
  <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <span class="text-xs font-semibold text-blue-600 uppercase tracking-wider">Activity</span>
      <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Wallet Transactions</h1>
      <p class="text-xs text-slate-500 mt-0.5">Review history of deposits, order charges, and refunds.</p>
    </div>
  </div>

  <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200/80">
    <?php if (empty($transactions)): ?>
      <div class="py-12 text-center">
        <p class="text-sm font-bold text-slate-700">No transactions recorded</p>
      </div>
    <?php else: ?>
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-100 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
              <th class="py-3 px-3">ID</th>
              <th class="py-3 px-3">Type</th>
              <th class="py-3 px-3 text-right">Amount</th>
              <th class="py-3 px-3 text-right">Balance After</th>
              <th class="py-3 px-3">Description</th>
              <th class="py-3 px-3 text-right">Date</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-xs">
            <?php foreach ($transactions as $tx): ?>
              <tr class="hover:bg-slate-50/80">
                <td class="py-3.5 px-3 font-mono-num font-bold text-slate-800">#<?= (int) $tx['id'] ?></td>
                <td class="py-3.5 px-3 uppercase font-extrabold text-[11px] text-blue-600"><?= e($tx['type']) ?></td>
                <td class="py-3.5 px-3 text-right font-mono-num font-extrabold <?= (float)$tx['amount'] >= 0 ? 'text-emerald-600' : 'text-rose-600' ?>">
                  <?= (float)$tx['amount'] >= 0 ? '+' : '' ?><?= e(format_currency($tx['amount'])) ?>
                </td>
                <td class="py-3.5 px-3 text-right font-mono-num font-semibold text-slate-700"><?= e(format_currency($tx['balance_after'])) ?></td>
                <td class="py-3.5 px-3 text-slate-600 truncate max-w-[250px]"><?= e($tx['description']) ?></td>
                <td class="py-3.5 px-3 text-right text-slate-400 whitespace-nowrap"><?= e(date('d M Y, H:i', strtotime($tx['created_at']))) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/layouts/dashboard.php';
