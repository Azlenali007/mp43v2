<?php
declare(strict_types=1);
if (!defined('APP_ROOT')) {
    http_response_code(403);
    exit('Forbidden');
}
/** Aurora Midnight Order Form Builder Component ($newOrderPayload expected) */
?>
<form @submit.prevent="submitOrder" class="lg:col-span-7 aurora-surface rounded-2xl p-5 sm:p-6 space-y-5">
  <template x-if="feedback">
    <div :class="feedback.ok ? 'bg-teal-950/70 border-teal-500/40 text-teal-200' : 'bg-rose-950/70 border-rose-500/40 text-rose-200'" class="p-3.5 rounded-xl border text-sm font-medium flex items-center justify-between gap-3">
      <span x-text="feedback.message"></span>
      <template x-if="feedback.ok && feedback.order_id">
        <a :href="(apiBase || '') + 'order-details.php?id=' + feedback.order_id" class="text-xs font-bold underline shrink-0 text-teal-300">Inspect Order</a>
      </template>
    </div>
  </template>

  <!-- 1. Quick Search Service -->
  <div class="relative" @click.outside="searchOpen = false">
    <label class="block text-xs font-bold uppercase tracking-wider text-teal-300 mb-1.5">1. Quick Command Search</label>
    <div class="relative">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4 text-slate-400 absolute left-3.5 top-3 pointer-events-none"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input
        type="text"
        x-model="globalSearch"
        @focus="if (globalSearch.trim().length > 0) searchOpen = true"
        @input.debounce.220ms="performAjaxSearch()"
        placeholder="Search by Service ID, platform, keyword (e.g. 12, Instagram, Views)..."
        class="w-full pl-10 pr-20 py-2.5 rounded-xl aurora-input border text-sm transition"
      >
      <button type="button" x-show="globalSearch.length > 0" @click="globalSearch = ''; searchResults = []; searchOpen = false" class="absolute right-3 top-2 px-2 py-1 text-[11px] font-semibold text-slate-400 hover:text-white">Clear</button>
    </div>

    <div x-show="searchOpen" x-cloak class="absolute left-0 right-0 mt-1.5 aurora-dropdown rounded-2xl max-h-72 overflow-y-auto z-40">
      <template x-if="searchLoading"><div class="p-4 text-xs text-slate-400">Searching services...</div></template>
      <template x-if="!searchLoading && searchResults.length === 0"><div class="p-4 text-xs text-slate-400">No matching enabled services found.</div></template>
      <template x-if="!searchLoading && searchResults.length > 0">
        <div class="divide-y divide-slate-800">
          <template x-for="res in searchResults" :key="res.id">
            <button type="button" @click="pickSearchResult(res)" class="w-full px-4 py-2.5 text-left hover:bg-white/5 flex items-center justify-between gap-3 transition">
              <div class="flex items-center gap-3 min-w-0">
                <span x-html="getIconHtml(res.icon_key)"></span>
                <div class="min-w-0">
                  <p class="text-xs font-semibold text-white truncate">
                    <span class="font-mono-num text-teal-400 mr-1" x-text="'#' + res.id"></span>
                    <span x-text="res.name"></span>
                  </p>
                  <p class="text-[11px] text-slate-400 truncate" x-text="res.category_name + ' · Min ' + res.min_qty + ' / Max ' + res.max_qty"></p>
                </div>
              </div>
              <span class="text-xs font-bold text-teal-300 font-mono-num shrink-0" x-text="currencySymbol + formatRate(res.price_per_1000)"></span>
            </button>
          </template>
        </div>
      </template>
    </div>
  </div>

  <!-- 2. Searchable Category Dropdown -->
  <div class="relative" @click.outside="categoryDropdownOpen = false">
    <label class="block text-xs font-bold uppercase tracking-wider text-teal-300 mb-1.5">2. Category</label>
    <button type="button" @click="categoryDropdownOpen = !categoryDropdownOpen; serviceDropdownOpen = false" class="w-full px-3.5 py-2.5 rounded-xl aurora-input border hover:border-teal-400/50 flex items-center justify-between gap-3 text-left transition">
      <div class="flex items-center gap-3 min-w-0">
        <span x-html="getIconHtml(selectedCategory ? (selectedCategory.icon_key || 'globe') : 'globe')"></span>
        <div class="min-w-0">
          <p class="text-sm font-semibold text-white truncate" x-text="selectedCategory ? selectedCategory.name : 'Select a category'"></p>
          <p class="text-[11px] text-slate-400" x-text="selectedCategory ? ((selectedCategory.active_services_count || 0) + ' active services') : ''"></p>
        </div>
      </div>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4 text-slate-400 shrink-0"><polyline points="6 9 12 15 18 9"/></svg>
    </button>

    <div x-show="categoryDropdownOpen" x-cloak class="absolute left-0 right-0 mt-1.5 aurora-dropdown rounded-2xl z-30 overflow-hidden">
      <div class="p-2.5 border-b border-slate-800 bg-[#0B1220]">
        <input type="text" x-model="categorySearch" placeholder="Filter categories..." class="w-full px-3 py-1.5 rounded-lg aurora-input border text-xs">
      </div>
      <div class="max-h-64 overflow-y-auto divide-y divide-slate-800">
        <template x-if="filteredCategories.length === 0"><div class="p-4 text-xs text-slate-400 text-center">No matching categories.</div></template>
        <template x-for="cat in filteredCategories" :key="cat.id">
          <button type="button" @click="selectCategory(cat.id); categoryDropdownOpen = false" :class="Number(selectedCategoryId) === Number(cat.id) ? 'bg-teal-500/15' : 'hover:bg-white/5'" class="w-full px-3.5 py-2.5 flex items-center justify-between gap-3 text-left transition">
            <div class="flex items-center gap-3 min-w-0">
              <span x-html="getIconHtml(cat.icon_key || 'globe')"></span>
              <span class="text-sm font-semibold text-white truncate" x-text="cat.name"></span>
            </div>
            <span class="px-2 py-0.5 rounded-md bg-slate-800 text-[11px] font-mono-num text-teal-300 shrink-0" x-text="cat.active_services_count || 0"></span>
          </button>
        </template>
      </div>
    </div>
  </div>

  <!-- 3. Searchable Service Dropdown -->
  <div class="relative" @click.outside="serviceDropdownOpen = false">
    <label class="block text-xs font-bold uppercase tracking-wider text-teal-300 mb-1.5">3. Service</label>
    <button type="button" @click="serviceDropdownOpen = !serviceDropdownOpen; categoryDropdownOpen = false" class="w-full px-3.5 py-2.5 rounded-xl aurora-input border hover:border-teal-400/50 flex items-center justify-between gap-3 text-left transition">
      <div class="flex items-center gap-3 min-w-0">
        <span x-html="getIconHtml(selectedService ? (selectedService.icon_key || 'sparkles') : 'sparkles')"></span>
        <div class="min-w-0">
          <p class="text-sm font-semibold text-white truncate">
            <span x-show="selectedService" class="font-mono-num text-teal-400 mr-1" x-text="selectedService ? ('#' + selectedService.id) : ''"></span>
            <span x-text="selectedService ? selectedService.name : 'Select a service'"></span>
          </p>
          <p class="text-[11px] text-slate-400 font-mono-num" x-text="selectedService ? ('Rate: ' + currencySymbol + formatRate(selectedService.price_per_1000) + ' per 1,000') : ''"></p>
        </div>
      </div>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4 text-slate-400 shrink-0"><polyline points="6 9 12 15 18 9"/></svg>
    </button>

    <div x-show="serviceDropdownOpen" x-cloak class="absolute left-0 right-0 mt-1.5 aurora-dropdown rounded-2xl z-30 overflow-hidden">
      <div class="p-2.5 border-b border-slate-800 bg-[#0B1220]">
        <input type="text" x-model="serviceSearch" placeholder="Search by service ID or name..." class="w-full px-3 py-1.5 rounded-lg aurora-input border text-xs">
      </div>
      <div class="max-h-72 overflow-y-auto divide-y divide-slate-800">
        <template x-if="categoryServices.length === 0"><div class="p-4 text-xs text-slate-400 text-center">No enabled services match this filter.</div></template>
        <template x-for="svc in categoryServices" :key="svc.id">
          <button type="button" @click="selectService(svc.id); serviceDropdownOpen = false" :class="Number(selectedServiceId) === Number(svc.id) ? 'bg-teal-500/15' : 'hover:bg-white/5'" class="w-full px-3.5 py-2.5 flex items-center justify-between gap-3 text-left transition">
            <div class="flex items-center gap-3 min-w-0">
              <span x-html="getIconHtml(svc.icon_key || 'sparkles')"></span>
              <div class="min-w-0">
                <p class="text-xs font-semibold text-white truncate">
                  <span class="font-mono-num text-teal-400 mr-1" x-text="'#' + svc.id"></span>
                  <span x-text="svc.name"></span>
                </p>
                <p class="text-[11px] text-slate-400 font-mono-num" x-text="'Min ' + Number(svc.min_qty).toLocaleString() + ' · Max ' + Number(svc.max_qty).toLocaleString()"></p>
              </div>
            </div>
            <div class="text-right shrink-0">
              <p class="text-xs font-bold text-teal-300 font-mono-num" x-text="currencySymbol + formatRate(svc.price_per_1000)"></p>
              <p class="text-[10px] text-slate-500">per 1K</p>
            </div>
          </button>
        </template>
      </div>
    </div>
  </div>

  <!-- 4. Service Description -->
  <div class="p-4 rounded-xl bg-[#0B1220] border border-slate-700/60 space-y-2">
    <div class="flex items-center justify-between gap-2">
      <span class="text-xs font-bold uppercase tracking-wider text-teal-300">4. Service Description</span>
      <span x-show="selectedService" class="text-[11px] font-mono-num font-semibold text-indigo-300" x-text="selectedService ? ('ID #' + selectedService.id + ' · ' + (selectedService.type || 'default')) : ''"></span>
    </div>
    <p class="text-xs text-slate-300 leading-relaxed whitespace-pre-line" x-text="selectedService && selectedService.description ? selectedService.description : 'Select a service above to inspect automated delivery guidelines and specifications.'"></p>
    <div x-show="selectedService" class="flex flex-wrap gap-2 pt-1">
      <span x-show="selectedService && Number(selectedService.refill) === 1" class="px-2 py-0.5 rounded-md bg-teal-500/15 border border-teal-500/30 text-[11px] font-semibold text-teal-300">Refill Supported</span>
      <span x-show="selectedService && Number(selectedService.cancel) === 1" class="px-2 py-0.5 rounded-md bg-indigo-500/15 border border-indigo-500/30 text-[11px] font-semibold text-indigo-300">Cancel Allowed</span>
    </div>
  </div>

  <!-- 5. Link -->
  <div>
    <label class="block text-xs font-bold uppercase tracking-wider text-teal-300 mb-1.5">5. Link</label>
    <input type="text" x-model="link" required placeholder="https://instagram.com/username or post URL" class="w-full px-3.5 py-2.5 rounded-xl aurora-input border text-sm transition">
  </div>

  <!-- 6 & 7. Quantity + Limits -->
  <div>
    <label class="block text-xs font-bold uppercase tracking-wider text-teal-300 mb-1.5">6. Quantity</label>
    <input type="number" x-model="quantity" :min="selectedService ? selectedService.min_qty : 1" :max="selectedService ? selectedService.max_qty : 1000000" required class="w-full px-3.5 py-2.5 rounded-xl aurora-input border text-sm font-mono-num transition">
    <div class="mt-2 flex items-center justify-between text-xs text-slate-300 bg-[#0B1220] border border-slate-700/60 rounded-xl px-3.5 py-2">
      <span class="font-semibold text-slate-400">7. Limits:</span>
      <span class="font-mono-num" x-text="selectedService ? ('Min Qty: ' + Number(selectedService.min_qty).toLocaleString() + '  |  Max Qty: ' + Number(selectedService.max_qty).toLocaleString()) : 'Select a service'"></span>
    </div>
  </div>

  <!-- 8. Charge -->
  <div class="p-4 rounded-xl bg-[#0B1220] border border-teal-500/30 flex flex-wrap items-center justify-between gap-3">
    <div>
      <p class="text-xs font-bold uppercase tracking-wider text-teal-300">8. Total Charge (<?= e($newOrderPayload['displayCurrency']) ?>)</p>
      <p class="text-[11px] text-slate-400 mt-0.5">
        Wallet Balance: <strong class="text-teal-300 font-mono-num" x-text="currencySymbol + Number(walletBalance).toFixed(2)"></strong>
        <template x-if="displayCurrency !== baseCurrency">
          <span class="ml-1 text-slate-500" x-text="'(Base: ' + baseSymbol + calculatedBaseCharge.toFixed(4) + ' ' + baseCurrency + ')'"></span>
        </template>
      </p>
    </div>
    <p class="text-2xl font-bold text-white font-mono-num" x-text="currencySymbol + calculatedCharge.toFixed(4)"></p>
  </div>

  <!-- 9. Submit -->
  <button type="submit" :disabled="submitting || !selectedService" class="w-full py-3 rounded-xl aurora-btn-primary text-sm font-bold shadow-sm disabled:opacity-50 transition">
    <span x-text="submitting ? 'Executing Order...' : '9. Submit Order'"></span>
  </button>
</form>
