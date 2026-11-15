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
  <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <span class="text-xs font-semibold text-blue-600 uppercase tracking-wider">Campaign Launcher</span>
      <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">New Social Media Order</h1>
      <p class="text-xs text-slate-500 mt-0.5">Place orders with live multi-currency conversion in <strong><?= e($newOrderPayload['displayCurrency']) ?></strong>.</p>
    </div>
    <a href="<?= e($rp) ?>services.php" class="inline-flex items-center justify-center px-4 py-2.5 rounded-2xl bg-slate-100 hover:bg-slate-200 text-xs font-bold text-slate-800 transition">Browse Services</a>
  </div>

  <?php if (empty($newOrderPayload['categories']) || empty($newOrderPayload['services'])): ?>
    <div class="bg-white border border-slate-200 rounded-3xl p-10 text-center">
      <p class="text-base font-bold text-slate-900">No Active Services Available</p>
      <p class="text-xs text-slate-500 mt-1">Services are currently being updated by administrators.</p>
    </div>
  <?php else: ?>
    <div class="grid lg:grid-cols-12 gap-6 items-start">
      <?php require __DIR__ . '/components/order-form.php'; ?>

      <!-- Live Order Summary -->
      <div class="lg:col-span-5 space-y-5">
        <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-sm space-y-4">
          <div class="flex items-center justify-between">
            <h2 class="text-sm font-extrabold text-slate-900">Selected Service Summary</h2>
            <span class="text-xs font-mono-num font-bold text-blue-600" x-text="selectedService ? ('#' + selectedService.id) : '—'"></span>
          </div>

          <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 flex items-center gap-3">
            <span x-html="getIconHtml(selectedService ? (selectedService.icon_key || 'sparkles') : 'sparkles')"></span>
            <div class="min-w-0">
              <p class="text-[11px] text-slate-500 font-semibold" x-text="selectedCategory ? selectedCategory.name : 'Category'"></p>
              <p class="text-xs font-extrabold text-slate-900 truncate" x-text="selectedService ? selectedService.name : 'No service selected'"></p>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3 text-xs">
            <div class="p-3.5 rounded-2xl border border-slate-200 bg-white">
              <span class="text-slate-400 text-[11px] font-semibold block">Price / 1000</span>
              <span class="text-sm font-black text-emerald-600 font-mono-num mt-0.5 block" x-text="selectedService ? (currencySymbol + formatRate(selectedService.price_per_1000)) : '—'"></span>
            </div>
            <div class="p-3.5 rounded-2xl border border-slate-200 bg-white">
              <span class="text-slate-400 text-[11px] font-semibold block">Min – Max Qty</span>
              <span class="text-sm font-black text-slate-900 font-mono-num mt-0.5 block" x-text="selectedService ? (Number(selectedService.min_qty).toLocaleString() + ' – ' + Number(selectedService.max_qty).toLocaleString()) : '—'"></span>
            </div>
          </div>

          <div class="border-t border-slate-100 pt-4 space-y-2.5 text-xs">
            <div class="flex justify-between font-medium">
              <span class="text-slate-500">Quantity</span>
              <span class="font-mono-num font-bold text-slate-900" x-text="Number(quantity || 0).toLocaleString()"></span>
            </div>
            <div class="flex justify-between font-medium">
              <span class="text-slate-500">Total Price (<?= e($newOrderPayload['displayCurrency']) ?>)</span>
              <span class="font-mono-num font-extrabold text-blue-600" x-text="currencySymbol + calculatedCharge.toFixed(4)"></span>
            </div>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/layouts/dashboard.php';
