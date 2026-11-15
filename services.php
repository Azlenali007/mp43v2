<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
ensure_installed();

require_once __DIR__ . '/classes/Category.php';
require_once __DIR__ . '/classes/Service.php';

$user = current_user();
$selectedCategoryId = validate_positive_int($_GET['category'] ?? null);
$search = sanitize_string($_GET['q'] ?? '', 120);

$categories = Category::all(true);
$services = Service::all($selectedCategoryId, true, $search);

$pageTitle = 'Services Catalog';
if ($user) {
    require __DIR__ . '/includes/user-header.php';
} else {
    require __DIR__ . '/includes/public-header.php';
}
?>
<div class="<?= $user ? '' : 'max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 py-10' ?> space-y-6">
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h1 class="text-2xl font-bold text-[#151515]">Services Catalog</h1>
      <p class="text-sm text-slate-600 mt-1">Real-time rates per 1,000 units with minimum &amp; maximum order quantities.</p>
    </div>
    <?php if ($user): ?>
      <a href="new-order.php" class="px-5 py-2.5 rounded-xl bg-[#7138D8] text-white text-sm font-semibold hover:bg-[#5B24C0] transition whitespace-nowrap self-start">
        + New Order
      </a>
    <?php endif; ?>
  </div>

  <!-- Filters -->
  <div class="bg-white border border-slate-200 rounded-2xl p-4 flex flex-col md:flex-row gap-3">
    <div class="flex-1 relative">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4 text-slate-400 absolute left-3.5 top-3"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input type="text" id="serviceSearchInput" value="<?= e($search) ?>" placeholder="Search by service ID, name, or description..." class="w-full pl-10 pr-4 py-2 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-[#7138D8]">
    </div>
    <select id="serviceCategoryFilter" class="md:w-72 px-3.5 py-2 rounded-xl border border-slate-200 text-sm bg-white focus:outline-none focus:border-[#7138D8]">
      <option value="">All Categories</option>
      <?php foreach ($categories as $cat): ?>
        <option value="<?= (int) $cat['id'] ?>" <?= $selectedCategoryId === (int) $cat['id'] ? 'selected' : '' ?>>
          <?= e($cat['name']) ?> (<?= (int) $cat['active_services_count'] ?>)
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <?php if (empty($services)): ?>
    <div class="bg-white border border-slate-200 rounded-2xl p-10 text-center">
      <p class="text-base font-semibold text-[#151515]">No Active Services Found</p>
      <p class="text-sm text-slate-500 mt-1">No active services match your current filter or none have been published yet.</p>
    </div>
  <?php else: ?>
    <div class="bg-white border border-slate-200 rounded-2xl overflow-x-auto">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="border-b border-slate-200 bg-slate-50/70 text-xs font-semibold text-slate-500">
            <th class="py-3.5 px-4">ID</th>
            <th class="py-3.5 px-4">Service</th>
            <th class="py-3.5 px-4">Category</th>
            <th class="py-3.5 px-4 text-right">Rate / 1000</th>
            <th class="py-3.5 px-4 text-right">Min</th>
            <th class="py-3.5 px-4 text-right">Max</th>
            <th class="py-3.5 px-4 text-right">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-sm">
          <?php foreach ($services as $svc): ?>
            <tr class="hover:bg-slate-50/60" data-service-row data-category="<?= (int) $svc['category_id'] ?>" data-search="<?= e($svc['id'] . ' ' . $svc['name'] . ' ' . $svc['category_name'] . ' ' . ($svc['description'] ?? '')) ?>">
              <td class="py-3.5 px-4 font-mono-num text-xs text-slate-500">#<?= (int) $svc['id'] ?></td>
              <td class="py-3.5 px-4">
                <div class="flex items-start gap-3">
                  <?= render_icon_badge($svc['icon_key'] ?: $svc['category_icon'] ?: 'sparkles', 'w-8 h-8', 'p-1.5') ?>
                  <div>
                    <p class="font-semibold text-[#151515]"><?= e($svc['name']) ?></p>
                    <?php if (!empty($svc['description'])): ?>
                      <p class="text-xs text-slate-500 mt-0.5 line-clamp-2 max-w-md"><?= e($svc['description']) ?></p>
                    <?php endif; ?>
                  </div>
                </div>
              </td>
              <td class="py-3.5 px-4 text-slate-600 whitespace-nowrap"><?= e($svc['category_name']) ?></td>
              <td class="py-3.5 px-4 text-right font-mono-num font-semibold text-[#16A36A] whitespace-nowrap"><?= e(format_currency($svc['price_per_1000'])) ?></td>
              <td class="py-3.5 px-4 text-right font-mono-num text-xs text-slate-600"><?= number_format((int) $svc['min_qty']) ?></td>
              <td class="py-3.5 px-4 text-right font-mono-num text-xs text-slate-600"><?= number_format((int) $svc['max_qty']) ?></td>
              <td class="py-3.5 px-4 text-right">
                <a href="new-order.php?service=<?= (int) $svc['id'] ?>" class="inline-flex px-3.5 py-1.5 rounded-lg bg-[#7138D8] text-white text-xs font-semibold hover:bg-[#5B24C0] transition whitespace-nowrap">
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
<script src="assets/js/services.js"></script>
<?php
if ($user) {
    require __DIR__ . '/includes/user-footer.php';
} else {
    require __DIR__ . '/includes/public-footer.php';
}
