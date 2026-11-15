<?php
declare(strict_types=1);
if (!defined('APP_ROOT')) {
    http_response_code(403);
    exit('Forbidden');
}
$pageTitle = $pageTitle ?? 'New Order';
$pageScripts = ['assets/js/orders.js'];
ob_start();
?>
<div class="space-y-6" x-data="window.newOrderApp(<?= e(json_encode($newOrderPayload, JSON_UNESCAPED_UNICODE)) ?>)">
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h1 class="text-2xl font-bold text-white">Order Command Builder</h1>
      <p class="text-sm text-slate-400 mt-1">Search services or select a category with live FX pricing in <strong class="text-teal-300"><?= e($newOrderPayload['displayCurrency']) ?></strong>.</p>
    </div>
    <a href="<?= e($rp) ?>services.php" class="px-4 py-2 rounded-xl border border-slate-700 bg-[#111A2E] text-xs font-semibold text-white hover:border-teal-400/50 self-start">Full Catalog</a>
  </div>

  <?php if (empty($newOrderPayload['categories']) || empty($newOrderPayload['services'])): ?>
    <div class="aurora-surface rounded-2xl p-10 text-center">
      <p class="text-base font-semibold text-white">No Active Services Available</p>
      <p class="text-sm text-slate-400 mt-1">An administrator has not enabled any service categories yet.</p>
    </div>
  <?php else: ?>
    <div class="grid lg:grid-cols-12 gap-6 items-start">
      <!-- Aurora places live specs on LEFT, 9-field command builder on RIGHT -->
      <div class="lg:col-span-5 space-y-5">
        <div class="aurora-surface rounded-2xl p-5 sm:p-6 space-y-4">
          <div class="flex items-center justify-between">
            <h2 class="text-base font-bold text-white">Live Order Spec</h2>
            <span class="text-xs font-mono-num font-semibold text-teal-300" x-text="selectedService ? ('#' + selectedService.id) : '—'"></span>
          </div>

          <div class="p-4 rounded-xl bg-[#0B1220] border border-slate-800 flex items-center gap-3">
            <span x-html="getIconHtml(selectedService ? (selectedService.icon_key || 'sparkles') : 'sparkles')"></span>
            <div class="min-w-0">
              <p class="text-xs text-slate-400" x-text="selectedCategory ? selectedCategory.name : 'Category'"></p>
              <p class="text-sm font-bold text-white truncate" x-text="selectedService ? selectedService.name : 'No service selected'"></p>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3 text-xs">
            <div class="p-3 rounded-xl bg-[#0B1220] border border-slate-800">
              <span class="text-slate-400 block">Rate per 1,000</span>
              <span class="text-sm font-bold text-teal-300 font-mono-num mt-0.5 block" x-text="selectedService ? (currencySymbol + formatRate(selectedService.price_per_1000)) : '—'"></span>
            </div>
            <div class="p-3 rounded-xl bg-[#0B1220] border border-slate-800">
              <span class="text-slate-400 block">Min – Max Qty</span>
              <span class="text-sm font-bold text-white font-mono-num mt-0.5 block" x-text="selectedService ? (Number(selectedService.min_qty).toLocaleString() + ' – ' + Number(selectedService.max_qty).toLocaleString()) : '—'"></span>
            </div>
          </div>

          <div class="border-t border-slate-800 pt-4 space-y-2 text-xs">
            <div class="flex justify-between">
              <span class="text-slate-400">Quantity</span>
              <span class="font-mono-num font-semibold text-white" x-text="Number(quantity || 0).toLocaleString()"></span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-400">Display Total (<?= e($newOrderPayload['displayCurrency']) ?>)</span>
              <span class="font-mono-num font-bold text-teal-300" x-text="currencySymbol + calculatedCharge.toFixed(4)"></span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-400">Wallet Debit (<?= e($newOrderPayload['baseCurrency']) ?>)</span>
              <span class="font-mono-num font-semibold text-white" x-text="baseSymbol + calculatedBaseCharge.toFixed(4)"></span>
            </div>
          </div>
        </div>

        <div class="aurora-surface rounded-2xl p-5 space-y-2.5 text-xs text-slate-300">
          <h3 class="text-sm font-bold text-white">Pre-Flight Checklist</h3>
          <p>• Keep the destination URL or profile set to <strong class="text-teal-300">Public</strong> throughout delivery.</p>
          <p>• Avoid submitting duplicate simultaneous orders for the same link.</p>
          <p>• Charges settle atomically in <strong class="text-teal-300"><?= e($newOrderPayload['baseCurrency']) ?></strong>.</p>
        </div>
      </div>

      <?php require __DIR__ . '/components/order-form.php'; ?>
    </div>
  <?php endif; ?>
</div>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/layouts/dashboard.php';
