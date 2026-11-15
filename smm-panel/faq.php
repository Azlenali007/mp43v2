<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
ensure_installed();

$user = current_user();
$pageTitle = 'Frequently Asked Questions';
if ($user) {
    require __DIR__ . '/includes/user-header.php';
} else {
    require __DIR__ . '/includes/public-header.php';
}

$faqs = [
    ['How quickly do orders start after placement?', 'Most services start automatically within minutes of submission. Each service lists its speed, minimum quantity, and description before you place your order.'],
    ['How do I add funds to my wallet?', 'Open the Add Funds page from your dashboard and choose any payment method enabled by the administrator (such as Razorpay online checkout or Manual UPI/Bank transfer).'],
    ['What happens if an order is cancelled or partially delivered?', 'If a provider marks an order as cancelled or failed, or completes a partial delivery, the unfulfilled balance is refunded automatically back to your wallet ledger.'],
    ['Can I connect my own website or script via API?', 'Yes. Visit the API Access page inside your user dashboard to copy your private API key and view the standard JSON v2 endpoint documentation.'],
    ['Is my wallet balance safe?', 'All wallet credits, order charges, and refunds execute inside atomic MySQL transactions with row-level locking and idempotency keys to prevent duplicate deductions or credits.'],
];
?>
<div class="<?= $user ? 'max-w-4xl' : 'max-w-[960px] mx-auto px-4 sm:px-6 lg:px-8 py-12' ?>">
  <div class="mb-8">
    <h1 class="text-2xl sm:text-3xl font-bold text-[#151515]">Frequently Asked Questions</h1>
    <p class="mt-2 text-sm text-slate-600">Clear answers on ordering, wallet funding, refunds, and API usage.</p>
  </div>

  <div class="space-y-3" x-data="{ openIndex: 0 }">
    <?php foreach ($faqs as $idx => [$q, $a]): ?>
      <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden">
        <button type="button" @click="openIndex = openIndex === <?= $idx ?> ? -1 : <?= $idx ?>" class="w-full px-5 py-4 text-left flex items-center justify-between gap-4 hover:bg-slate-50 transition">
          <span class="text-sm sm:text-base font-semibold text-[#151515]"><?= e($q) ?></span>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4 text-slate-500 shrink-0 transition-transform" :class="openIndex === <?= $idx ?> ? 'rotate-180' : ''"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div x-show="openIndex === <?= $idx ?>" x-cloak class="px-5 pb-4 text-sm text-slate-600 leading-relaxed border-t border-slate-100 pt-3">
          <?= e($a) ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php
if ($user) {
    require __DIR__ . '/includes/user-footer.php';
} else {
    require __DIR__ . '/includes/public-footer.php';
}
