<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$user = require_user();

require_once __DIR__ . '/classes/Category.php';
require_once __DIR__ . '/classes/Service.php';

$categories = Category::all(true);
$services = Service::all(null, true, '');
$icons = icon_registry();
$currencySymbol = get_setting('currency_symbol', '₹');

$defaultServiceId = validate_positive_int($_GET['service'] ?? null);
$defaultCategoryId = validate_positive_int($_GET['category'] ?? null);
if ($defaultServiceId && !$defaultCategoryId) {
    foreach ($services as $s) {
        if ((int) $s['id'] === $defaultServiceId) {
            $defaultCategoryId = (int) $s['category_id'];
            break;
        }
    }
}

$pageTitle = 'New Order';
require __DIR__ . '/includes/user-header.php';
?>
<div class="space-y-6" x-data='newOrderApp(<?= json_encode([
    "categories"        => $categories,
    "services"          => $services,
    "icons"             => $icons,
    "currencySymbol"    => $currencySymbol,
    "walletBalance"     => (float) $user["wallet_balance"],
    "defaultCategoryId" => $defaultCategoryId,
    "defaultServiceId"  => $defaultServiceId,
    "apiBase"           => app_root_prefix(),
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>

  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h1 class="text-2xl font-bold text-[#151515]">New Order</h1>
      <p class="text-sm text-slate-600 mt-1">Select a platform category, pick a service, and enter your target link and quantity.</p>
    </div>
    <a href="bulk-order.php" class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-[#151515] hover:bg-slate-50 self-start">
      Switch to Bulk Order
    </a>
  </div>

  <?php if (empty($categories) || empty($services)): ?>
    <div class="bg-white border border-slate-200 rounded-2xl p-10 text-center">
      <h2 class="text-lg font-semibold text-[#151515]">No Active Services Available</h2>
      <p class="text-sm text-slate-500 mt-1">Services must be added or imported by the panel administrator before orders can be placed.</p>
    </div>
  <?php else: ?>
    <!-- Step 1: Category Selection with Icons & Search -->
    <div class="bg-white border border-slate-200 rounded-2xl p-5 sm:p-6 space-y-4">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <h2 class="text-base font-bold text-[#151515]">1. Choose Category</h2>
          <p class="text-xs text-slate-500">Click any category card to load its services automatically.</p>
        </div>
        <div class="relative w-full sm:w-64">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4 text-slate-400 absolute left-3 top-2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input type="text" x-model="categorySearch" placeholder="Search categories..." class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 text-xs focus:outline-none focus:border-[#7138D8]">
        </div>
      </div>

      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
        <template x-for="cat in filteredCategories" :key="cat.id">
          <button
            type="button"
            @click="selectCategory(cat.id)"
            :class="Number(selectedCategoryId) === Number(cat.id) ? 'border-[#7138D8] bg-purple-50/70 ring-2 ring-[#7138D8]/20' : 'border-slate-200 bg-white hover:border-slate-300'"
            class="p-3.5 rounded-xl border text-left flex items-center gap-3 transition"
          >
            <span x-html="getIconHtml(cat.icon_key || cat.platform)"></span>
            <div class="min-w-0">
              <p class="text-xs font-bold text-[#151515] truncate" x-text="cat.name"></p>
              <p class="text-[11px] text-slate-500 tabular-nums" x-text="(cat.active_services_count || 0) + ' services'"></p>
            </div>
          </button>
        </template>
      </div>
    </div>

    <!-- Step 2 & 3: Service Selection + Order Summary Form -->
    <div class="grid lg:grid-cols-12 gap-6 items-start">
      <!-- Service Selector & Form -->
      <div class="lg:col-span-7 bg-white border border-slate-200 rounded-2xl p-5 sm:p-6 space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <h2 class="text-base font-bold text-[#151515]">2. Select Service &amp; Configure Order</h2>
          <input type="text" x-model="serviceSearch" placeholder="Filter services..." class="sm:w-52 px-3 py-1.5 rounded-xl border border-slate-200 text-xs focus:outline-none focus:border-[#7138D8]">
        </div>

        <!-- Visual Service Cards List -->
        <div class="max-h-72 overflow-y-auto space-y-2 pr-1">
          <template x-if="categoryServices.length === 0">
            <div class="p-6 text-center text-xs text-slate-500 border border-dashed border-slate-200 rounded-xl">
              No active services found in this category.
            </div>
          </template>
          <template x-for="svc in categoryServices" :key="svc.id">
            <button
              type="button"
              @click="selectService(svc.id)"
              :class="Number(selectedServiceId) === Number(svc.id) ? 'border-[#7138D8] bg-purple-50/50 ring-1 ring-[#7138D8]' : 'border-slate-200 hover:border-slate-300'"
              class="w-full p-3.5 rounded-xl border text-left flex items-center justify-between gap-3 transition"
            >
              <div class="flex items-center gap-3 min-w-0">
                <span x-html="getIconHtml(svc.icon_key || svc.platform)"></span>
                <div class="min-w-0">
                  <p class="text-sm font-semibold text-[#151515] truncate">
                    <span class="font-mono-num text-xs text-slate-400 mr-1" x-text="'#' + svc.id"></span>
                    <span x-text="svc.name"></span>
                  </p>
                  <p class="text-xs text-slate-500 font-mono-num mt-0.5" x-text="'Min: ' + svc.min_qty + ' · Max: ' + svc.max_qty"></p>
                </div>
              </div>
              <div class="text-right shrink-0">
                <p class="text-sm font-bold text-[#16A36A] font-mono-num" x-text="currencySymbol + Number(svc.price_per_1000).toFixed(2)"></p>
                <p class="text-[11px] text-slate-400">per 1,000</p>
              </div>
            </button>
          </template>
        </div>

        <!-- Feedback Banner -->
        <template x-if="feedback">
          <div :class="feedback.ok ? 'bg-emerald-50 border-emerald-200 text-[#16A36A]' : 'bg-rose-50 border-rose-200 text-rose-700'" class="p-3.5 rounded-xl border text-sm font-medium flex items-center justify-between gap-3">
            <span x-text="feedback.message"></span>
            <template x-if="feedback.ok && feedback.order_id">
              <a :href="'order-details.php?id=' + feedback.order_id" class="text-xs font-bold underline shrink-0">View Order</a>
            </template>
          </div>
        </template>

        <!-- Order Input Form -->
        <form @submit.prevent="submitOrder" class="space-y-4 pt-2 border-t border-slate-100">
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Target Link / URL</label>
            <input type="text" x-model="link" required placeholder="https://instagram.com/username or post URL" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-[#7138D8]">
          </div>

          <div class="grid sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">Quantity</label>
              <input
                type="number"
                x-model="quantity"
                :min="selectedService ? selectedService.min_qty : 1"
                :max="selectedService ? selectedService.max_qty : 1000000"
                required
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-mono-num focus:outline-none focus:border-[#7138D8]"
              >
              <p class="mt-1 text-[11px] text-slate-500 font-mono-num" x-show="selectedService" x-text="selectedService ? ('Min: ' + selectedService.min_qty + ' — Max: ' + selectedService.max_qty) : ''"></p>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">Calculated Charge</label>
              <div class="px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-base font-bold text-[#151515] font-mono-num" x-text="currencySymbol + calculatedCharge.toFixed(4)"></div>
            </div>
          </div>

          <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
            <div class="text-xs text-slate-600">
              Available Wallet: <strong class="text-[#16A36A] font-mono-num" x-text="currencySymbol + walletBalance.toFixed(2)"></strong>
            </div>
            <button
              type="submit"
              :disabled="submitting || !selectedService"
              class="px-6 py-2.5 rounded-xl bg-[#7138D8] text-white text-sm font-semibold hover:bg-[#5B24C0] disabled:opacity-50 transition"
            >
              <span x-text="submitting ? 'Placing Order...' : 'Place Order'"></span>
            </button>
          </div>
        </form>
      </div>

      <!-- Selected Service Specification Card -->
      <div class="lg:col-span-5 bg-white border border-slate-200 rounded-2xl p-5 sm:p-6 space-y-4">
        <h2 class="text-base font-bold text-[#151515]">Service Details</h2>
        <template x-if="!selectedService">
          <p class="text-sm text-slate-500">Choose a service on the left to inspect its rate, limits, and delivery instructions.</p>
        </template>
        <template x-if="selectedService">
          <div class="space-y-4">
            <div class="flex items-start gap-3">
              <span x-html="getIconHtml(selectedService.icon_key || selectedService.platform)"></span>
              <div>
                <p class="text-xs font-mono-num text-slate-400" x-text="'Service #' + selectedService.id + ' · ' + selectedService.category_name"></p>
                <h3 class="text-base font-bold text-[#151515] mt-0.5" x-text="selectedService.name"></h3>
              </div>
            </div>

            <div class="grid grid-cols-3 gap-3 text-center">
              <div class="p-3 rounded-xl bg-[#F8F9FC] border border-slate-200">
                <p class="text-[11px] text-slate-500">Rate / 1K</p>
                <p class="text-sm font-bold text-[#16A36A] font-mono-num mt-0.5" x-text="currencySymbol + Number(selectedService.price_per_1000).toFixed(2)"></p>
              </div>
              <div class="p-3 rounded-xl bg-[#F8F9FC] border border-slate-200">
                <p class="text-[11px] text-slate-500">Min Qty</p>
                <p class="text-sm font-bold text-[#151515] font-mono-num mt-0.5" x-text="Number(selectedService.min_qty).toLocaleString()"></p>
              </div>
              <div class="p-3 rounded-xl bg-[#F8F9FC] border border-slate-200">
                <p class="text-[11px] text-slate-500">Max Qty</p>
                <p class="text-sm font-bold text-[#151515] font-mono-num mt-0.5" x-text="Number(selectedService.max_qty).toLocaleString()"></p>
              </div>
            </div>

            <div class="p-4 rounded-xl bg-[#F8F9FC] border border-slate-200">
              <p class="text-xs font-semibold text-slate-700 mb-1">Description &amp; Guidelines</p>
              <p class="text-xs text-slate-600 leading-relaxed whitespace-pre-line" x-text="selectedService.description || 'Standard automated delivery. Ensure the target account or post is set to public before ordering.'"></p>
            </div>
          </div>
        </template>
      </div>
    </div>
  <?php endif; ?>
</div>
<script src="<?= e(app_root_prefix()) ?>assets/js/orders.js"></script>
<?php require __DIR__ . '/includes/user-footer.php'; ?>
