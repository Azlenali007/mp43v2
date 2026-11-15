<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$user = require_user();

$pdo = Database::getConnection();
$stmt = $pdo->prepare('SELECT * FROM wallet_transactions WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT 150');
$stmt->execute([(int) $user['id']]);
$txs = $stmt->fetchAll();

$pageTitle = 'Transactions';
require __DIR__ . '/includes/user-header.php';
?>
<div class="space-y-6">
  <div class="flex items-center justify-between gap-4">
    <div>
      <h1 class="text-2xl font-bold text-[#151515]">Wallet Transactions</h1>
      <p class="text-sm text-slate-600 mt-1">Complete atomic ledger of all credits, order debits, and refunds.</p>
    </div>
    <a href="add-funds.php" class="px-5 py-2.5 rounded-xl bg-[#7138D8] text-white text-sm font-semibold">+ Add Funds</a>
  </div>

  <?php if (empty($txs)): ?>
    <div class="bg-white border border-slate-200 rounded-2xl p-10 text-center">
      <p class="text-base font-semibold text-[#151515]">No Transactions Yet</p>
      <p class="text-sm text-slate-500 mt-1">Your wallet deposits, order deductions, and refunds will be logged here.</p>
    </div>
  <?php else: ?>
    <div class="bg-white border border-slate-200 rounded-2xl overflow-x-auto">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="border-b border-slate-200 bg-slate-50/70 text-xs font-semibold text-slate-500">
            <th class="py-3.5 px-4">ID</th>
            <th class="py-3.5 px-4">Type</th>
            <th class="py-3.5 px-4">Description</th>
            <th class="py-3.5 px-4 text-right">Amount</th>
            <th class="py-3.5 px-4 text-right">Balance After</th>
            <th class="py-3.5 px-4 text-right">Date</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-sm">
          <?php foreach ($txs as $t): ?>
            <?php $isPositive = in_array($t['type'], ['credit', 'refund'], true); ?>
            <tr class="hover:bg-slate-50/60">
              <td class="py-3.5 px-4 font-mono-num text-xs text-slate-500">#<?= (int) $t['id'] ?></td>
              <td class="py-3.5 px-4 uppercase text-xs font-semibold <?= $isPositive ? 'text-[#16A36A]' : 'text-rose-600' ?>"><?= e($t['type']) ?></td>
              <td class="py-3.5 px-4 text-xs text-slate-700"><?= e($t['description']) ?></td>
              <td class="py-3.5 px-4 text-right font-mono-num font-semibold <?= $isPositive ? 'text-[#16A36A]' : 'text-rose-600' ?>">
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
              <td class="py-3.5 px-4 text-right font-mono-num text-xs text-[#151515]"><?= e(format_currency($t['balance_after'])) ?></td>
              <td class="py-3.5 px-4 text-right text-xs text-slate-500 whitespace-nowrap"><?= e(date('d M Y, H:i', strtotime($t['created_at']))) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/user-footer.php'; ?>
