<?php
declare(strict_types=1);
if (!defined('APP_ROOT')) {
    http_response_code(403);
    exit('Forbidden');
}
$pageTitle = $pageTitle ?? 'Services Catalog';
$isLogged = !empty($user);
$pageScripts = ['assets/js/services.js'];
ob_start();
?>
<div class="<?= $isLogged ? '' : 'max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 py-10' ?> space-y-6">
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h1 class="text-2xl font-bold text-white">Services Catalog</h1>
      <p class="text-sm text-slate-400 mt-1">Real-time rates per 1,000 units in <strong class="text-teal-300"><?= e($displayCurrency['code']) ?></strong> with min/max bounds.</p>
    </div>
    <?php if ($isLogged): ?>
      <a href="<?= e($rp) ?>new-order.php" class="px-5 py-2.5 rounded-xl aurora-btn-primary text-sm font-semibold transition whitespace-nowrap self-start">
        + New Order
      </a>
    <?php endif; ?>
  </div>

  <!-- Filters -->
  <div class="aurora-surface rounded-2xl p-4 flex flex-col md:flex-row gap-3">
    <div class="flex-1 relative">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4 text-slate-400 absolute left-3.5 top-3"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input type="text" id="serviceSearchInput" value="<?= e($search) ?>" placeholder="Search by service ID, name, or description..." class="w-full pl-10 pr-4 py-2 rounded-xl aurora-input border text-sm">
    </div>
    <select id="serviceCategoryFilter" class="md:w-72 px-3.5 py-2 rounded-xl aurora-input border text-sm">
      <option value="">All Categories</option>
      <?php foreach ($categories as $cat): ?>
        <option value="<?= (int) $cat['id'] ?>" <?= (int) ($selectedCategoryId ?? 0) === (int) $cat['id'] ? 'selected' : '' ?>>
          <?= e($cat['name']) ?> (<?= (int) $cat['active_services_count'] ?>)
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <?php if (empty($services)): ?>
    <div class="aurora-surface rounded-2xl p-10 text-center">
      <p class="text-base font-semibold text-white">No Active Services Found</p>
      <p class="text-sm text-slate-400 mt-1">No active services match your current filter or none have been published yet.</p>
    </div>
  <?php else: ?>
    <div class="aurora-surface rounded-2xl overflow-x-auto">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="border-b border-slate-800 bg-[#0B1220]/70 text-xs font-semibold text-slate-400">
            <th class="py-3.5 px-4">ID</th>
            <th class="py-3.5 px-4">Service</th>
            <th class="py-3.5 px-4">Category</th>
            <th class="py-3.5 px-4 text-right">Rate / 1000</th>
            <th class="py-3.5 px-4 text-right">Min</th>
            <th class="py-3.5 px-4 text-right">Max</th>
            <th class="py-3.5 px-4 text-right">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-800/80 text-sm">
          <?php foreach ($services as $svc): ?>
            <tr class="hover:bg-white/[0.02]" data-service-row data-category="<?= (int) $svc['category_id'] ?>" data-search="<?= e($svc['id'] . ' ' . $svc['name'] . ' ' . $svc['category_name'] . ' ' . ($svc['description'] ?? '')) ?>">
              <td class="py-3.5 px-4 font-mono-num text-xs text-teal-400">#<?= (int) $svc['id'] ?></td>
              <td class="py-3.5 px-4">
                <div class="flex items-start gap-3">
                  <?= render_icon_badge($svc['icon_key'] ?: $svc['category_icon'] ?: 'sparkles', 'w-8 h-8', 'p-1.5') ?>
                  <div>
                    <p class="font-semibold text-white"><?= e($svc['name']) ?></p>
                    <?php if (!empty($svc['description'])): ?>
                      <p class="text-xs text-slate-400 mt-0.5 line-clamp-2 max-w-md"><?= e($svc['description']) ?></p>
                    <?php endif; ?>
                  </div>
                </div>
              </td>
              <td class="py-3.5 px-4 text-slate-300 whitespace-nowrap"><?= e($svc['category_name']) ?></td>
              <td class="py-3.5 px-4 text-right font-mono-num font-semibold text-teal-300 whitespace-nowrap"><?= e(format_currency($svc['price_per_1000'])) ?></td>
              <td class="py-3.5 px-4 text-right font-mono-num text-xs text-slate-400"><?= number_format((int) $svc['min_qty']) ?></td>
              <td class="py-3.5 px-4 text-right font-mono-num text-xs text-slate-400"><?= number_format((int) $svc['max_qty']) ?></td>
              <td class="py-3.5 px-4 text-right">
                <a href="<?= e($rp) ?>new-order.php?service=<?= (int) $svc['id'] ?>" class="inline-flex px-3.5 py-1.5 rounded-lg aurora-btn-primary text-xs font-semibold transition whitespace-nowrap">
                  Place Order
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
<?php
$pageContent = ob_get_clean();
require __DIR__ . ($isLogged ? '/layouts/dashboard.php' : '/layouts/main.php');
