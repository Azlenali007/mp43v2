<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
$admin = require_admin('manage_services');
require_once __DIR__ . '/../classes/Category.php';
require_once __DIR__ . '/../classes/Service.php';
require_once __DIR__ . '/../classes/Provider.php';

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
    if ($price === null && $providerRate > 0) {
        $price = Service::calculateSellingPrice($providerRate, $marginType, $marginVal);
    }
    $minQty = validate_positive_int($_POST['min_qty'] ?? 10) ?? 10;
    $maxQty = validate_positive_int($_POST['max_qty'] ?? 10000) ?? 10000;

    if (!$categoryId || $name === '' || $price === null) {
        $error = 'Category, service name, and selling price per 1,000 are required.';
    } else {
        $id = Service::create([
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
            'status'              => $_POST['status'] ?? 'active',
        ]);
        log_activity('admin', (int) $admin['id'], 'service_created', 'service', $id, $name);
        set_flash('success', 'Service added.');
        redirect('services.php');
    }
}

$pageTitle = 'Add Service';
require __DIR__ . '/../includes/admin-header.php';
?>
<div class="max-w-3xl bg-white border border-slate-200 rounded-2xl p-6 space-y-4">
  <a href="services.php" class="text-xs font-semibold text-[#7138D8] hover:underline">&larr; Back to Services</a>
  <h2 class="text-lg font-bold text-[#151515]">Add New Service</h2>
  <?php if ($error): ?>
    <div class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-700 font-medium"><?= e($error) ?></div>
  <?php endif; ?>
  <form method="POST" action="service-add.php" class="space-y-4" x-data="{
    providerRate: 0,
    marginType: 'percent',
    marginValue: 25,
    sellingPrice: '',
    recalc() {
      const r = Number(this.providerRate || 0);
      const m = Number(this.marginValue || 0);
      if (r <= 0) return;
      const calc = this.marginType === 'fixed' ? (r + m) : (r + (r * m / 100));
      this.sellingPrice = Math.max(0.01, calc).toFixed(4);
    }
  }">
    <?= csrf_field() ?>
    <div class="grid sm:grid-cols-2 gap-4">
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Category</label>
        <select name="category_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm bg-white">
          <option value="">Select Category</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?= (int) $c['id'] ?>"><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Service Icon</label>
        <select name="icon_key" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm bg-white">
          <?php foreach ($icons as $k => $info): ?>
            <option value="<?= e($k) ?>"><?= e($info['label']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div>
      <label class="block text-xs font-semibold text-slate-700 mb-1">Service Name</label>
      <input type="text" name="name" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
    </div>

    <div class="grid sm:grid-cols-3 gap-4">
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Provider (Optional)</label>
        <select name="provider_id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm bg-white">
          <option value="">Manual Fulfillment</option>
          <?php foreach ($providers as $p): ?>
            <option value="<?= (int) $p['id'] ?>"><?= e($p['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Provider Service ID</label>
        <input type="text" name="provider_service_id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-mono-num">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Status</label>
        <select name="status" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm bg-white">
          <option value="active">Active</option>
          <option value="disabled">Disabled</option>
        </select>
      </div>
    </div>

    <div class="grid sm:grid-cols-4 gap-4">
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Provider Cost / 1K</label>
        <input type="number" step="0.0001" min="0" name="provider_rate" x-model="providerRate" @input="recalc()" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-mono-num">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Margin Type</label>
        <select name="margin_type" x-model="marginType" @change="recalc()" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm bg-white">
          <option value="percent">Percent (%)</option>
          <option value="fixed">Fixed (+₹)</option>
        </select>
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Margin Value</label>
        <input type="number" step="0.01" name="margin_value" x-model="marginValue" @input="recalc()" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-mono-num">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Selling Price / 1K</label>
        <input type="number" step="0.0001" min="0.01" name="price_per_1000" x-model="sellingPrice" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-mono-num">
      </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Min Quantity</label>
        <input type="number" min="1" name="min_qty" value="10" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-mono-num">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Max Quantity</label>
        <input type="number" min="1" name="max_qty" value="10000" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-mono-num">
      </div>
    </div>

    <div>
      <label class="block text-xs font-semibold text-slate-700 mb-1">Service Description</label>
      <textarea name="description" rows="4" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm"></textarea>
    </div>

    <div class="flex items-center gap-6 text-xs font-medium text-slate-700">
      <label class="flex items-center gap-2"><input type="checkbox" name="refill" value="1"> Supports Refill</label>
      <label class="flex items-center gap-2"><input type="checkbox" name="cancel" value="1"> Supports Cancel</label>
    </div>

    <button type="submit" class="px-6 py-2.5 rounded-xl bg-[#7138D8] text-white text-sm font-semibold hover:bg-[#5B24C0]">Save Service</button>
  </form>
</div>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
