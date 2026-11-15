<?php
declare(strict_types=1);
if (!defined('APP_ROOT')) {
    http_response_code(403);
    exit('Forbidden');
}
$pageTitle = $pageTitle ?? 'Orders History';
ob_start();
?>
<div class="space-y-6">
  <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <span class="text-xs font-semibold text-blue-600 uppercase tracking-wider">Tracking</span>
      <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Order History</h1>
      <p class="text-xs text-slate-500 mt-0.5">Monitor status and progress of your campaigns.</p>
    </div>
    <a href="<?= e($rp) ?>new-order.php" class="inline-flex items-center justify-center px-4 py-2.5 rounded-2xl bg-blue-600 hover:bg-blue-700 text-xs font-bold text-white shadow-md shadow-blue-500/20 transition">+ New Order</a>
  </div>

  <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200/80">
    <?php if (empty($orders)): ?>
      <div class="py-12 text-center">
        <p class="text-sm font-bold text-slate-700">No orders found</p>
        <p class="text-xs text-slate-500 mt-1">Your placed orders will appear here.</p>
      </div>
    <?php else: ?>
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-100 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
              <th class="py-3 px-3">Order ID</th>
              <th class="py-3 px-3">Service</th>
              <th class="py-3 px-3">Link</th>
              <th class="py-3 px-3 text-right">Charge</th>
              <th class="py-3 px-3 text-right">Qty</th>
              <th class="py-3 px-3">Status</th>
              <th class="py-3 px-3 text-right">Date</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-xs">
            <?php foreach ($orders as $ord): ?>
              <tr class="hover:bg-slate-50/80">
                <td class="py-3.5 px-3 font-mono-num font-bold">
                  <a href="<?= e($rp) ?>order-details.php?id=<?= (int) $ord['id'] ?>" class="text-blue-600 hover:underline">#<?= e($ord['order_code']) ?></a>
                </td>
                <td class="py-3.5 px-3 font-semibold text-slate-900 truncate max-w-[200px]"><?= e($ord['service_name']) ?></td>
                <td class="py-3.5 px-3 text-slate-500 truncate max-w-[150px]"><a href="<?= e($ord['link']) ?>" target="_blank" class="hover:underline"><?= e($ord['link']) ?></a></td>
                <td class="py-3.5 px-3 text-right font-mono-num font-extrabold text-slate-900"><?= e(format_currency($ord['charge'])) ?></td>
                <td class="py-3.5 px-3 text-right font-mono-num font-semibold text-slate-700"><?= number_format((int) $ord['quantity']) ?></td>
                <td class="py-3.5 px-3 whitespace-nowrap"><?= order_status_badge($ord['status']) ?></td>
                <td class="py-3.5 px-3 text-right text-slate-400 whitespace-nowrap"><?= e(date('d M Y, H:i', strtotime($ord['created_at']))) ?></td>
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
