<?php
declare(strict_types=1);
if (!defined('APP_ROOT')) {
    http_response_code(403);
    exit('Forbidden');
}
$pageTitle = $pageTitle ?? 'Order Details';
ob_start();
?>
<div class="space-y-6 max-w-3xl mx-auto">
  <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200/80 flex items-center justify-between">
    <div>
      <span class="text-xs font-semibold text-blue-600 uppercase tracking-wider">Order Inspection</span>
      <h1 class="text-xl font-black text-slate-900 mt-0.5">Order #<?= e($order['order_code'] ?? '') ?></h1>
    </div>
    <a href="<?= e($rp) ?>orders.php" class="px-4 py-2 rounded-2xl bg-slate-100 text-xs font-bold text-slate-800 hover:bg-slate-200 transition">&larr; Back to Orders</a>
  </div>

  <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border border-slate-200/80 space-y-4 text-xs">
    <div class="grid grid-cols-2 gap-4 pb-4 border-b border-slate-100">
      <div>
        <span class="text-slate-400 block font-semibold">Service Name</span>
        <span class="font-extrabold text-slate-900 text-sm mt-0.5 block"><?= e($order['service_name'] ?? '') ?></span>
      </div>
      <div>
        <span class="text-slate-400 block font-semibold">Status</span>
        <div class="mt-1"><?= order_status_badge($order['status'] ?? 'pending') ?></div>
      </div>
    </div>

    <div class="grid grid-cols-3 gap-4 pb-4 border-b border-slate-100">
      <div>
        <span class="text-slate-400 block font-semibold">Quantity</span>
        <span class="font-mono-num font-bold text-slate-900 text-sm mt-0.5 block"><?= number_format((int) ($order['quantity'] ?? 0)) ?></span>
      </div>
      <div>
        <span class="text-slate-400 block font-semibold">Total Charge</span>
        <span class="font-mono-num font-extrabold text-emerald-600 text-sm mt-0.5 block"><?= e(format_currency((float) ($order['charge'] ?? 0))) ?></span>
      </div>
      <div>
        <span class="text-slate-400 block font-semibold">Date Placed</span>
        <span class="text-slate-700 font-medium mt-0.5 block"><?= e($order['created_at'] ?? '') ?></span>
      </div>
    </div>

    <div>
      <span class="text-slate-400 block font-semibold mb-1">Target Link</span>
      <input type="text" readonly value="<?= e($order['link'] ?? '') ?>" class="w-full px-4 py-3 rounded-2xl bg-slate-50 border border-slate-200 text-xs font-mono text-slate-700">
    </div>
  </div>
</div>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/layouts/dashboard.php';
