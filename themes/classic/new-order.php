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
      <h1 class="text-2xl font-bold text-[#151515]">New Order</h1>
      <p class="text-sm text-slate-600 mt-1">Search services or pick a category below with live multi-currency pricing in <strong><?= e($newOrderPayload['displayCurrency']) ?></strong>.</p>
    </div>
    <a href="<?= e($rp) ?>services.php" class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-[#151515] hover:bg-slate-50 self-start">Browse Full Catalog</a>
  </div>

  <?php if (empty($newOrderPayload['categories']) || empty($newOrderPayload['services'])): ?>
    <div class="bg-white border border-slate-200 rounded-2xl p-10 text-center">
      <p class="text-base font-semibold text-[#151515]">No Active Services Available</p>
      <p class="text-sm text-slate-500 mt-1">An administrator has not enabled any service categories yet.</p>
    </div>
  <?php else: ?>
    <div class="grid lg:grid-cols-12 gap-6 items-start">
      <?php require __DIR__ . '/components/order-form.php'; ?>

      <!-- Live Order Summary & Service Insights -->
      <div class="lg:col-span-5 space-y-5">
        <div class="bg-white border border-slate-200 rounded-2xl p-5 sm:p-6 space-y-4">
          <div class="flex items-center justify-between">
            <h2 class="text-base font-bold text-[#151515]">Selected Service Summary</h2>
            <span class="text-xs font-mono-num font-semibold text-[#7138D8]" x-text="selectedService ? ('#' + selectedService.id) : '—'"></span>
          </div>

          <div class="p-4 rounded-xl bg-[#F8F9FC] border border-slate-200 flex items-center gap-3">
            <span x-html="getIconHtml(selectedService ? (selectedService.icon_key || 'sparkles') : 'sparkles')"></span>
            <div class="min-w-0">
              <p class="text-xs text-slate-500" x-text="selectedCategory ? selectedCategory.name : 'Category'"></p>
              <p class="text-sm font-bold text-[#151515] truncate" x-text="selectedService ? selectedService.name : 'No service selected'"></p>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3 text-xs">
            <div class="p-3 rounded-xl border border-slate-200 bg-white">
              <span class="text-slate-500 block">Price per 1,000</span>
              <span class="text-sm font-bold text-[#16A36A] font-mono-num mt-0.5 block" x-text="selectedService ? (currencySymbol + formatRate(selectedService.price_per_1000)) : '—'"></span>
            </div>
            <div class="p-3 rounded-xl border border-slate-200 bg-white">
              <span class="text-slate-500 block">Delivery Bounds</span>
              <span class="text-sm font-bold text-[#151515] font-mono-num mt-0.5 block" x-text="selectedService ? (Number(selectedService.min_qty).toLocaleString() + ' – ' + Number(selectedService.max_qty).toLocaleString()) : '—'"></span>
            </div>
          </div>

          <div class="border-t border-slate-200 pt-4 space-y-2 text-xs">
            <div class="flex justify-between">
              <span class="text-slate-500">Order Quantity</span>
              <span class="font-mono-num font-semibold text-[#151515]" x-text="Number(quantity || 0).toLocaleString()"></span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500">Display Total (<?= e($newOrderPayload['displayCurrency']) ?>)</span>
              <span class="font-mono-num font-bold text-[#7138D8]" x-text="currencySymbol + calculatedCharge.toFixed(4)"></span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500">Wallet Deduction (<?= e($newOrderPayload['baseCurrency']) ?>)</span>
              <span class="font-mono-num font-semibold text-[#151515]" x-text="baseSymbol + calculatedBaseCharge.toFixed(4)"></span>
            </div>
          </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl p-5 space-y-2.5 text-xs text-slate-600">
          <h3 class="text-sm font-bold text-[#151515]">Before Placing Your Order</h3>
          <p>• Ensure the target account or link is set to <strong>Public</strong> before submitting.</p>
          <p>• Do not place overlapping orders for the exact same URL simultaneously.</p>
          <p>• All charges deduct in base currency (<strong><?= e($newOrderPayload['baseCurrency']) ?></strong>) cleanly inside an atomic database lock.</p>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/layouts/dashboard.php';
