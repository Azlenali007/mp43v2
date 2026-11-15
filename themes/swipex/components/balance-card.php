<?php
declare(strict_types=1);
$rp = $rootPrefix ?? app_root_prefix();
$user = $loggedUser ?? $user ?? [];
$walletBalance = (float) ($user['wallet_balance'] ?? 0.00);
?>
<div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200/80 flex items-center justify-between gap-4">
  <div>
    <span class="text-xs font-semibold text-slate-500 block uppercase tracking-wider mb-1">Your Balance</span>
    <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 font-mono-num tracking-tight">
      <?= e(format_currency($walletBalance)) ?>
    </h2>
  </div>
  <div>
    <a href="<?= e($rp) ?>add-funds.php" class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-md shadow-blue-500/25 transition">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="w-4 h-4"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Add Funds
    </a>
  </div>
</div>
