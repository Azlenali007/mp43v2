<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
$admin = require_admin('manage_payments');

$pdo = Database::getConnection();
$txs = $pdo->query(
    'SELECT wt.*, u.name AS user_name, u.username
     FROM wallet_transactions wt
     INNER JOIN users u ON u.id = wt.user_id
     ORDER BY wt.created_at DESC, wt.id DESC
     LIMIT 150'
)->fetchAll();

$pageTitle = 'Transactions Ledger';
require __DIR__ . '/../includes/admin-header.php';
?>
<div class="bg-white border border-slate-200 rounded-2xl overflow-x-auto">
  <?php if (empty($txs)): ?>
    <div class="p-10 text-center text-sm text-slate-500">No wallet transactions recorded yet.</div>
  <?php else: ?>
    <table class="w-full text-left border-collapse text-sm">
      <thead>
        <tr class="border-b border-slate-200 bg-slate-50/70 text-xs font-semibold text-slate-500">
          <th class="py-3.5 px-4">ID</th>
          <th class="py-3.5 px-4">User</th>
          <th class="py-3.5 px-4">Type</th>
          <th class="py-3.5 px-4">Description</th>
          <th class="py-3.5 px-4 text-right">Amount</th>
          <th class="py-3.5 px-4 text-right">Balance After</th>
          <th class="py-3.5 px-4 text-right">Time</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100 text-xs">
        <?php foreach ($txs as $t): ?>
          <?php $pos = in_array($t['type'], ['credit', 'refund'], true); ?>
          <tr>
            <td class="py-3 px-4 font-mono-num text-slate-500">#<?= (int) $t['id'] ?></td>
            <td class="py-3 px-4 font-semibold"><a href="user-details.php?id=<?= (int) $t['user_id'] ?>" class="hover:underline">@<?= e($t['username']) ?></a></td>
            <td class="py-3 px-4 uppercase font-semibold <?= $pos ? 'text-[#16A36A]' : 'text-rose-600' ?>"><?= e($t['type']) ?></td>
            <td class="py-3 px-4 text-slate-700"><?= e($t['description']) ?></td>
            <td class="py-3 px-4 text-right font-mono-num font-bold <?= $pos ? 'text-[#16A36A]' : 'text-rose-600' ?>"><?= $pos ? '+' : '-' ?><?= e(format_currency($t['amount'])) ?></td>
            <td class="py-3 px-4 text-right font-mono-num"><?= e(format_currency($t['balance_after'])) ?></td>
            <td class="py-3 px-4 text-right text-slate-500 whitespace-nowrap"><?= e(date('d M Y, H:i', strtotime($t['created_at']))) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
