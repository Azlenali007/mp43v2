<?php
declare(strict_types=1);
if (!defined('APP_ROOT')) {
    http_response_code(403);
    exit('Forbidden');
}
$pageTitle = $pageTitle ?? 'Services Catalog';
ob_start();
?>
<div class="space-y-6" x-data="{ search: '', selectedCategory: 'all' }">
  <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <span class="text-xs font-semibold text-blue-600 uppercase tracking-wider">Catalog</span>
      <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Available SMM Services</h1>
      <p class="text-xs text-slate-500 mt-0.5">Explore pricing, delivery speed, and minimum/maximum quantities.</p>
    </div>
    <div class="w-full sm:w-72">
      <input type="text" x-model="search" placeholder="Search services..." class="w-full px-4 py-2.5 rounded-2xl bg-slate-100 border border-slate-200 text-xs text-slate-800 focus:outline-none focus:border-blue-600 transition">
    </div>
  </div>

  <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200/80 space-y-4">
    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="border-b border-slate-100 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
            <th class="py-3 px-3">ID</th>
            <th class="py-3 px-3">Service Name</th>
            <th class="py-3 px-3 text-right">Price per 1k</th>
            <th class="py-3 px-3 text-right">Min / Max</th>
            <th class="py-3 px-3 text-center">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-xs">
          <?php foreach ($services ?? [] as $svc): ?>
            <tr class="hover:bg-slate-50/80">
              <td class="py-3.5 px-3 font-mono-num font-bold text-blue-600">#<?= (int) $svc['id'] ?></td>
              <td class="py-3.5 px-3">
                <span class="font-bold text-slate-900 block"><?= e($svc['name']) ?></span>
                <span class="text-[11px] text-slate-400"><?= e($svc['category_name'] ?? '') ?></span>
              </td>
              <td class="py-3.5 px-3 text-right font-mono-num font-extrabold text-emerald-600">
                <?= e(format_currency($svc['price_per_1000'])) ?>
              </td>
              <td class="py-3.5 px-3 text-right font-mono-num text-slate-600">
                <?= number_format((int) $svc['min_qty']) ?> – <?= number_format((int) $svc['max_qty']) ?>
              </td>
              <td class="py-3.5 px-3 text-center">
                <a href="<?= e($rp) ?>new-order.php?service_id=<?= (int) $svc['id'] ?>" class="inline-flex px-3 py-1.5 rounded-xl bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white font-bold text-[11px] transition">Order</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/layouts/dashboard.php';
