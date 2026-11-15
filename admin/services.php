<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
$admin = require_admin('manage_services');
require_once __DIR__ . '/../classes/Category.php';
require_once __DIR__ . '/../classes/Service.php';
require_once __DIR__ . '/../classes/Provider.php';

$catFilter = validate_positive_int($_GET['category'] ?? null);
$search = sanitize_string($_GET['q'] ?? '', 120);

$categories = Category::all(false);
$providers = Provider::all(false);
$services = Service::all($catFilter, false, $search);

$pageTitle = 'Services Management';
require __DIR__ . '/../includes/admin-header.php';
?>
<div class="space-y-6">
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h2 class="text-xl font-bold text-[#151515]">Services</h2>
      <p class="text-sm text-slate-600">Manage service pricing, min/max bounds, icons, and bulk catalog updates.</p>
    </div>
    <div class="flex items-center gap-2 self-start">
      <a href="import-services.php" class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-[#151515] hover:bg-slate-50">Import from Provider</a>
      <a href="service-add.php" class="px-5 py-2.5 rounded-xl bg-[#7138D8] text-white text-xs font-semibold hover:bg-[#5B24C0]">+ Add Service</a>
    </div>
  </div>

  <!-- Filter Bar -->
  <form method="GET" action="services.php" class="bg-white border border-slate-200 rounded-2xl p-4 flex flex-col sm:flex-row gap-3">
    <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search by ID, name, or category..." class="flex-1 px-3.5 py-2 rounded-xl border border-slate-200 text-sm">
    <select name="category" class="sm:w-64 px-3.5 py-2 rounded-xl border border-slate-200 text-sm bg-white">
      <option value="">All Categories</option>
      <?php foreach ($categories as $c): ?>
        <option value="<?= (int) $c['id'] ?>" <?= $catFilter === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="px-5 py-2 rounded-xl bg-[#151515] text-white text-xs font-semibold">Filter</button>
  </form>

  <?php if (empty($services)): ?>
    <div class="bg-white border border-slate-200 rounded-2xl p-10 text-center">
      <p class="text-base font-semibold text-[#151515]">No Services Found</p>
      <p class="text-sm text-slate-500 mt-1">Create a service manually or import services directly from a configured provider API.</p>
    </div>
  <?php else: ?>
    <form method="POST" action="../api/bulk-operations.php" class="space-y-4">
      <?= csrf_field() ?>
      <input type="hidden" name="entity" value="services">

      <!-- Bulk Operations Toolbar -->
      <div class="bg-white border border-slate-200 rounded-2xl p-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
          <select name="bulk_action" required class="px-3 py-2 rounded-xl border border-slate-200 text-xs bg-white font-semibold">
            <option value="">Bulk Operation...</option>
            <option value="enable">Enable Selected</option>
            <option value="disable">Disable Selected</option>
            <option value="price_adjust">Bulk Price Increase / Decrease (%)</option>
            <option value="move_category">Bulk Change Category</option>
            <option value="change_provider">Bulk Change Provider</option>
            <option value="delete">Bulk Delete Selected</option>
          </select>

          <input type="number" step="0.5" name="price_percent" placeholder="% (e.g. 15 or -10)" class="w-36 px-3 py-2 rounded-xl border border-slate-200 text-xs font-mono-num">

          <select name="target_category_id" class="px-3 py-2 rounded-xl border border-slate-200 text-xs bg-white">
            <option value="">Category...</option>
            <?php foreach ($categories as $c): ?>
              <option value="<?= (int) $c['id'] ?>"><?= e($c['name']) ?></option>
            <?php endforeach; ?>
          </select>

          <select name="target_provider_id" class="px-3 py-2 rounded-xl border border-slate-200 text-xs bg-white">
            <option value="">Provider...</option>
            <?php foreach ($providers as $p): ?>
              <option value="<?= (int) $p['id'] ?>"><?= e($p['name']) ?></option>
            <?php endforeach; ?>
          </select>

          <button type="submit" class="px-4 py-2 rounded-xl bg-[#7138D8] text-white text-xs font-semibold hover:bg-[#5B24C0]">Apply Bulk Action</button>
        </div>
        <span id="selectedCountBadge" class="text-xs font-semibold text-slate-500">0 selected</span>
      </div>

      <div class="bg-white border border-slate-200 rounded-2xl overflow-x-auto">
        <table class="w-full text-left border-collapse text-sm">
          <thead>
            <tr class="border-b border-slate-200 bg-slate-50/70 text-xs font-semibold text-slate-500">
              <th class="py-3 px-4"><input type="checkbox" onclick="window.AdminBulk.toggleAll(this, '.svc-cb')"></th>
              <th class="py-3 px-3">ID</th>
              <th class="py-3 px-3">Service</th>
              <th class="py-3 px-3">Category</th>
              <th class="py-3 px-3">Provider</th>
              <th class="py-3 px-3 text-right">Provider Rate</th>
              <th class="py-3 px-3 text-right">Selling / 1K</th>
              <th class="py-3 px-3 text-right">Min / Max</th>
              <th class="py-3 px-3">Status</th>
              <th class="py-3 px-4 text-right">Edit</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <?php foreach ($services as $s): ?>
              <tr class="hover:bg-slate-50/60">
                <td class="py-3 px-4"><input type="checkbox" name="ids[]" value="<?= (int) $s['id'] ?>" class="svc-cb" onchange="window.AdminBulk.updateCount('.svc-cb')"></td>
                <td class="py-3 px-3 font-mono-num text-xs text-slate-500">#<?= (int) $s['id'] ?></td>
                <td class="py-3 px-3">
                  <div class="flex items-center gap-2.5 max-w-[260px]">
                    <?= render_icon_badge($s['icon_key'] ?: $s['category_icon'] ?: 'sparkles', 'w-7 h-7', 'p-1') ?>
                    <span class="truncate text-xs font-semibold text-[#151515]"><?= e($s['name']) ?></span>
                  </div>
                </td>
                <td class="py-3 px-3 text-xs text-slate-600"><?= e($s['category_name']) ?></td>
                <td class="py-3 px-3 text-xs text-slate-500"><?= e($s['provider_name'] ?: 'Manual') ?><?= $s['provider_service_id'] ? ' (#' . e($s['provider_service_id']) . ')' : '' ?></td>
                <td class="py-3 px-3 text-right font-mono-num text-xs text-slate-500"><?= e(format_currency($s['provider_rate'])) ?></td>
                <td class="py-3 px-3 text-right font-mono-num text-xs font-bold text-[#16A36A]"><?= e(format_currency($s['price_per_1000'])) ?></td>
                <td class="py-3 px-3 text-right font-mono-num text-xs text-slate-600"><?= (int) $s['min_qty'] ?> / <?= (int) $s['max_qty'] ?></td>
                <td class="py-3 px-3">
                  <span class="px-2 py-0.5 rounded-md text-xs font-semibold <?= $s['status'] === 'active' ? 'bg-emerald-50 text-[#16A36A]' : 'bg-slate-100 text-slate-600' ?>"><?= e(ucfirst($s['status'])) ?></span>
                </td>
                <td class="py-3 px-4 text-right text-xs">
                  <a href="service-edit.php?id=<?= (int) $s['id'] ?>" class="font-semibold text-[#7138D8] hover:underline">Edit</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </form>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
