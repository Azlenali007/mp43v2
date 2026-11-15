<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
$admin = require_admin('manage_providers');
require_once __DIR__ . '/../classes/Provider.php';
require_once __DIR__ . '/../classes/Category.php';

$providers = Provider::all(true);
$categories = Category::all(false);
$selectedProviderId = validate_positive_int($_GET['provider_id'] ?? null);
$defaultMargin = (float) get_setting('default_margin_percent', '25');

$pageTitle = 'Import Services';
require __DIR__ . '/../includes/admin-header.php';
?>
<div class="space-y-6" x-data='{
  providerId: <?= json_encode($selectedProviderId ? (string) $selectedProviderId : '') ?>,
  services: [],
  selectedIds: {},
  categoryFilter: "",
  search: "",
  marginType: "percent",
  marginValue: <?= json_encode($defaultMargin) ?>,
  targetCategoryId: "",
  updateExisting: false,
  loading: false,
  importing: false,
  feedback: null,

  get externalCategories() {
    const set = new Set();
    this.services.forEach(s => { if (s.category) set.add(s.category); });
    return Array.from(set);
  },

  get filteredServices() {
    const q = this.search.trim().toLowerCase();
    return this.services.filter(s => {
      const catOk = !this.categoryFilter || s.category === this.categoryFilter;
      const qOk = !q || s.name.toLowerCase().includes(q) || String(s.service).includes(q) || s.category.toLowerCase().includes(q);
      return catOk && qOk;
    });
  },

  calcSelling(svc) {
    const rate = Number(svc.rate || 0);
    const mType = svc.custom_margin_type || this.marginType;
    const mVal = svc.custom_margin_value !== "" && typeof svc.custom_margin_value !== "undefined" ? Number(svc.custom_margin_value) : Number(this.marginValue || 0);
    if (mType === "fixed") return Math.max(0.01, rate + mVal);
    return Math.max(0.01, rate + (rate * mVal / 100));
  },

  async fetchProviderServices() {
    this.feedback = null;
    if (!this.providerId) {
      this.feedback = { ok: false, message: "Choose a provider first." };
      return;
    }
    this.loading = true;
    this.services = [];
    this.selectedIds = {};
    try {
      const res = await window.SMMApp.postJson("../api/provider-import.php", {
        action: "fetch_preview",
        provider_id: Number(this.providerId)
      });
      if (!res.ok) {
        this.feedback = { ok: false, message: res.message || "Failed to fetch provider services." };
      } else {
        this.services = (res.services || []).map(s => ({
          ...s,
          custom_margin_type: "",
          custom_margin_value: ""
        }));
      }
    } catch (e) {
      this.feedback = { ok: false, message: "Network error communicating with provider." };
    } finally {
      this.loading = false;
    }
  },

  toggleAllFiltered(checked) {
    this.filteredServices.forEach(s => {
      this.selectedIds[s.service] = checked;
    });
  },

  async runImport(mode) {
    this.feedback = null;
    let toImport = [];
    if (mode === "all") {
      toImport = this.services;
    } else if (mode === "category") {
      toImport = this.filteredServices;
    } else {
      toImport = this.services.filter(s => !!this.selectedIds[s.service]);
    }
    if (toImport.length === 0) {
      this.feedback = { ok: false, message: "No services selected for import." };
      return;
    }
    this.importing = true;
    try {
      const payloadServices = toImport.map(s => ({
        ...s,
        margin_type: s.custom_margin_type || this.marginType,
        margin_value: (s.custom_margin_value !== "" && typeof s.custom_margin_value !== "undefined") ? Number(s.custom_margin_value) : Number(this.marginValue)
      }));
      const res = await window.SMMApp.postJson("../api/provider-import.php", {
        action: "import_services",
        provider_id: Number(this.providerId),
        margin_type: this.marginType,
        margin_value: Number(this.marginValue),
        target_category_id: this.targetCategoryId ? Number(this.targetCategoryId) : null,
        update_existing: this.updateExisting,
        services: payloadServices
      });
      this.feedback = res;
      if (res.ok) {
        await this.fetchProviderServices();
        this.feedback = res;
      }
    } catch (e) {
      this.feedback = { ok: false, message: "Import request failed." };
    } finally {
      this.importing = false;
    }
  }
}'>
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h2 class="text-xl font-bold text-[#151515]">Import Services &amp; Margin Calculator</h2>
      <p class="text-sm text-slate-600">Fetch real services from a provider API, preview duplicates, apply percentage/fixed margins, and import.</p>
    </div>
    <a href="services.php" class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-xs font-semibold">View Local Services</a>
  </div>

  <?php if (empty($providers)): ?>
    <div class="bg-white border border-slate-200 rounded-2xl p-10 text-center">
      <p class="text-base font-semibold text-[#151515]">No Active Providers Available</p>
      <p class="text-sm text-slate-500 mt-1">Add and activate a provider first before importing services.</p>
      <a href="provider-add.php" class="mt-4 inline-flex px-5 py-2.5 rounded-xl bg-[#7138D8] text-white text-xs font-semibold">Add Provider</a>
    </div>
  <?php else: ?>
    <!-- Provider & Margin Controls -->
    <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-4">
      <div class="grid sm:grid-cols-4 gap-4 items-end">
        <div class="sm:col-span-2">
          <label class="block text-xs font-semibold text-slate-700 mb-1">1. Select Provider</label>
          <select x-model="providerId" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm bg-white">
            <option value="">Choose provider...</option>
            <?php foreach ($providers as $p): ?>
              <option value="<?= (int) $p['id'] ?>"><?= e($p['name']) ?> (<?= e($p['currency']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Default Margin Type</label>
          <select x-model="marginType" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm bg-white">
            <option value="percent">Percentage (%)</option>
            <option value="fixed">Fixed (+Amount)</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Default Margin Value</label>
          <input type="number" step="0.1" x-model="marginValue" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-mono-num">
        </div>
      </div>

      <div class="flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-slate-100">
        <div class="flex flex-wrap items-center gap-4 text-xs">
          <label class="flex items-center gap-2 font-medium text-slate-700">
            <input type="checkbox" x-model="updateExisting"> Update Existing Services (sync rates &amp; margins)
          </label>
          <div class="flex items-center gap-2">
            <span class="text-slate-500">Target Category:</span>
            <select x-model="targetCategoryId" class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs bg-white">
              <option value="">Auto-create from Provider Category</option>
              <?php foreach ($categories as $c): ?>
                <option value="<?= (int) $c['id'] ?>"><?= e($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <button type="button" @click="fetchProviderServices" :disabled="loading" class="px-5 py-2.5 rounded-xl bg-[#7138D8] text-white text-xs font-semibold hover:bg-[#5B24C0] disabled:opacity-50">
          <span x-text="loading ? 'Fetching Live Catalog...' : 'Fetch Provider Services'"></span>
        </button>
      </div>
    </div>

    <template x-if="feedback">
      <div :class="feedback.ok ? 'bg-emerald-50 border-emerald-200 text-[#16A36A]' : 'bg-rose-50 border-rose-200 text-rose-700'" class="p-4 rounded-xl border text-sm font-medium" x-text="feedback.message"></div>
    </template>

    <!-- Preview & Import Table -->
    <template x-if="services.length > 0">
      <div class="bg-white border border-slate-200 rounded-2xl p-5 space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div class="flex flex-wrap items-center gap-2">
            <input type="text" x-model="search" placeholder="Search provider services..." class="px-3.5 py-2 rounded-xl border border-slate-200 text-xs w-56">
            <select x-model="categoryFilter" class="px-3 py-2 rounded-xl border border-slate-200 text-xs bg-white max-w-xs">
              <option value="" x-text="'All Provider Categories (' + services.length + ')'"></option>
              <template x-for="ec in externalCategories" :key="ec">
                <option :value="ec" x-text="ec"></option>
              </template>
            </select>
          </div>

          <div class="flex flex-wrap items-center gap-2">
            <button type="button" @click="runImport('selected')" :disabled="importing" class="px-4 py-2 rounded-xl bg-[#7138D8] text-white text-xs font-semibold hover:bg-[#5B24C0]">Import Selected</button>
            <button type="button" @click="runImport('category')" :disabled="importing" class="px-4 py-2 rounded-xl bg-[#151515] text-white text-xs font-semibold">Import Filtered Category</button>
            <button type="button" @click="runImport('all')" :disabled="importing" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 hover:bg-slate-50">Import All</button>
          </div>
        </div>

        <div class="overflow-x-auto max-h-[540px]">
          <table class="w-full text-left border-collapse text-xs">
            <thead class="sticky top-0 bg-slate-50 border-b border-slate-200 text-slate-500 font-semibold">
              <tr>
                <th class="py-3 px-3"><input type="checkbox" @change="toggleAllFiltered($event.target.checked)"></th>
                <th class="py-3 px-3">Ext ID</th>
                <th class="py-3 px-3">Service Name &amp; Category</th>
                <th class="py-3 px-3 text-right">Provider Price</th>
                <th class="py-3 px-3 text-right">Custom Margin</th>
                <th class="py-3 px-3 text-right">Selling Price</th>
                <th class="py-3 px-3">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <template x-for="svc in filteredServices.slice(0, 250)" :key="svc.service">
                <tr class="hover:bg-slate-50/70">
                  <td class="py-2.5 px-3"><input type="checkbox" x-model="selectedIds[svc.service]"></td>
                  <td class="py-2.5 px-3 font-mono-num text-slate-500" x-text="svc.service"></td>
                  <td class="py-2.5 px-3">
                    <p class="font-semibold text-[#151515]" x-text="svc.name"></p>
                    <p class="text-[11px] text-slate-500" x-text="svc.category + ' · Min: ' + svc.min + ' · Max: ' + svc.max"></p>
                  </td>
                  <td class="py-2.5 px-3 text-right font-mono-num" x-text="Number(svc.rate).toFixed(4)"></td>
                  <td class="py-2.5 px-3 text-right">
                    <input type="number" step="0.1" x-model="svc.custom_margin_value" :placeholder="marginValue" class="w-20 px-2 py-1 rounded-lg border border-slate-200 text-right font-mono-num">
                  </td>
                  <td class="py-2.5 px-3 text-right font-mono-num font-bold text-[#16A36A]" x-text="calcSelling(svc).toFixed(4)"></td>
                  <td class="py-2.5 px-3">
                    <span x-show="svc.is_imported" class="px-2 py-0.5 rounded bg-amber-50 text-amber-700 font-semibold">Duplicate (#<span x-text="svc.local_id"></span>)</span>
                    <span x-show="!svc.is_imported" class="px-2 py-0.5 rounded bg-emerald-50 text-[#16A36A] font-semibold">New</span>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
      </div>
    </template>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
