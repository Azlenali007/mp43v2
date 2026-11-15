<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
$admin = require_admin('manage_services');
require_once __DIR__ . '/../classes/Category.php';
require_once __DIR__ . '/../classes/Service.php';
require_once __DIR__ . '/../classes/Provider.php';

$id = validate_positive_int($_GET['id'] ?? null);
if (!$id) {
    redirect('services.php');
}
$svc = Service::find($id);
if (!$svc) {
    redirect('services.php');
}

$categories = Category::all(false);
$providers = Provider::all(false);
$icons = icon_registry();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();
    $categoryId = validate_positive_int($_POST['category_id'] ?? null);
    $name = sanitize_string($_POST['name'] ?? '', 255);
    $providerRate = (float) ($_POST['provider_rate'] ?? 0);
    $marginType = ($_POST['margin_type'] ?? 'percent') === 'fixed' ? 'fixed' : 'percent';
    $marginVal = (float) ($_POST['margin_value'] ?? 0);
    $price = validate_positive_float($_POST['price_per_1000'] ?? null, 0.01, 1000000.0);
    if (!empty($_POST['recalculate_from_margin']) && $providerRate > 0) {
        $price = Service::calculateSellingPrice($providerRate, $marginType, $marginVal);
    }
    $minQty = validate_positive_int($_POST['min_qty'] ?? 10) ?? 10;
    $maxQty = validate_positive_int($_POST['max_qty'] ?? 10000) ?? 10000;

    if (!$categoryId || $name === '' || $price === null) {
        $error = 'Category, service name, and selling price are required.';
    } else {
        Service::update($id, [
            'category_id'         => $categoryId,
            'provider_id'         => validate_positive_int($_POST['provider_id'] ?? null),
            'provider_service_id' => sanitize_string($_POST['provider_service_id'] ?? '', 80),
            'name'                => $name,
            'description'         => sanitize_string($_POST['description'] ?? '', 2000),
            'icon_key'            => sanitize_string($_POST['icon_key'] ?? 'sparkles', 60),
            'platform'            => sanitize_string($_POST['icon_key'] ?? 'general', 60),
            'type'                => $_POST['type'] ?? 'default',
            'provider_rate'       => $providerRate,
            'margin_type'         => $marginType,
            'margin_value'        => $marginVal,
            'price_per_1000'      => $price,
            'min_qty'             => $minQty,
            'max_qty'             => max($minQty, $maxQty),
            'refill'              => !empty($_POST['refill']),
            'cancel'              => !empty($_POST['cancel']),
            'sync_price'          => !empty($_POST['sync_price']) ? 1 : 0,
            'status'              => $_POST['status'] ?? 'active',
        ]);
        log_activity('admin', (int) $admin['id'], 'service_updated', 'service', $id, $name);
        set_flash('success', 'Service updated.');
        redirect('services.php');
    }
}

$pageTitle = 'Edit Service #' . $id;
require __DIR__ . '/../includes/admin-header.php';
?>
<div class="max-w-3xl bg-white border border-slate-200 rounded-2xl p-6 space-y-4">
  <a href="services.php" class="text-xs font-semibold text-[#7138D8] hover:underline">&larr; Back to Services</a>
  <h2 class="text-lg font-bold text-[#151515]">Edit Service #<?= (int) $id ?></h2>
  <?php if ($error): ?>
    <div class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-700 font-medium"><?= e($error) ?></div>
  <?php endif; ?>
  <form method="POST" action="service-edit.php?id=<?= $id ?>" class="space-y-4">
    <?= csrf_field() ?>
    <div class="grid sm:grid-cols-2 gap-4">
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Category</label>
        <select name="category_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm bg-white">
          <?php foreach ($categories as $c): ?>
            <option value="<?= (int) $c['id'] ?>" <?= (int) $svc['category_id'] === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Service Icon</label>
        <select name="icon_key" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm bg-white">
          <?php foreach ($icons as $k => $info): ?>
            <option value="<?= e($k) ?>" <?= $svc['icon_key'] === $k ? 'selected' : '' ?>><?= e($info['label']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div>
      <label class="block text-xs font-semibold text-slate-700 mb-1">Service Name</label>
      <input type="text" name="name" value="<?= e($svc['name']) ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
    </div>

    <div class="grid sm:grid-cols-3 gap-4">
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Provider</label>
        <select name="provider_id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm bg-white">
          <option value="">Manual</option>
          <?php foreach ($providers as $p): ?>
            <option value="<?= (int) $p['id'] ?>" <?= (int) ($svc['provider_id'] ?? 0) === (int) $p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Provider Service ID</label>
        <input type="text" name="provider_service_id" value="<?= e($svc['provider_service_id'] ?? '') ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-mono-num">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Status</label>
        <select name="status" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm bg-white">
          <option value="active" <?= $svc['status'] === 'active' ? 'selected' : '' ?>>Active</option>
          <option value="disabled" <?= $svc['status'] === 'disabled' ? 'selected' : '' ?>>Disabled</option>
        </select>
      </div>
    </div>

    <div class="grid sm:grid-cols-4 gap-4">
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Provider Cost / 1K (<?= e(Currency::getBaseCode()) ?>)</label>
        <input type="number" step="0.0001" min="0" name="provider_rate" value="<?= e((string) $svc['provider_rate']) ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-mono-num">
        <?php if (!empty($svc['original_currency']) && $svc['original_provider_rate'] !== null): ?>
          <p class="text-[10px] text-slate-400 mt-1">Original: <?= number_format((float)$svc['original_provider_rate'], 4) ?> <?= e($svc['original_currency']) ?></p>
        <?php endif; ?>
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Margin Type</label>
        <select name="margin_type" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm bg-white">
          <option value="percent" <?= $svc['margin_type'] === 'percent' ? 'selected' : '' ?>>Percent (%)</option>
          <option value="fixed" <?= $svc['margin_type'] === 'fixed' ? 'selected' : '' ?>>Fixed (+<?= e(Currency::getBase()['symbol'] ?? '₹') ?>)</option>
        </select>
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Margin Value</label>
        <input type="number" step="0.01" name="margin_value" value="<?= e((string) $svc['margin_value']) ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-mono-num">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Selling Price / 1K</label>
        <input type="number" step="0.0001" min="0.01" name="price_per_1000" value="<?= e((string) $svc['price_per_1000']) ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-mono-num">
      </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Min Quantity</label>
        <input type="number" min="1" name="min_qty" value="<?= (int) $svc['min_qty'] ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-mono-num">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Max Quantity</label>
        <input type="number" min="1" name="max_qty" value="<?= (int) $svc['max_qty'] ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-mono-num">
      </div>
    </div>

    <div>
      <label class="block text-xs font-semibold text-slate-700 mb-1">Description</label>
      <textarea name="description" rows="4" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm"><?= e($svc['description'] ?? '') ?></textarea>
    </div>

    <div class="flex flex-wrap items-center gap-6 text-xs font-medium text-slate-700">
      <label class="flex items-center gap-2"><input type="checkbox" name="sync_price" value="1" <?= $svc['sync_price'] ? 'checked' : '' ?>> Auto-Sync Price With Provider</label>
      <label class="flex items-center gap-2"><input type="checkbox" name="recalculate_from_margin" value="1"> Recalculate Selling Price from Margin</label>
      <label class="flex items-center gap-2"><input type="checkbox" name="refill" value="1" <?= $svc['refill'] ? 'checked' : '' ?>> Refill</label>
      <label class="flex items-center gap-2"><input type="checkbox" name="cancel" value="1" <?= $svc['cancel'] ? 'checked' : '' ?>> Cancel</label>
    </div>

    <button type="submit" class="px-6 py-2.5 rounded-xl bg-[#7138D8] text-white text-sm font-semibold hover:bg-[#5B24C0]">Update Service</button>
  </form>
</div>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
