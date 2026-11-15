<?php
declare(strict_types=1);
/**
 * Aurora Midnight Theme — Command Deck New Order Page
 */
?>
<div class="space-y-6" x-data='newOrderApp(<?= json_encode($newOrderPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
  <!-- Command Banner -->
  <div class="rounded-2xl hero-banner-gradient p-6 border border-teal-500/25 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <span class="inline-block text-[11px] font-bold uppercase tracking-widest text-[#2DD4BF]">Aurora Order Console</span>
      <h1 class="text-2xl font-bold text-white mt-1">Launch New Campaign</h1>
      <p class="text-sm text-slate-200/80 mt-0.5">Real-time service lookup, instant multi-currency conversion, and atomic wallet execution.</p>
    </div>
    <div class="flex items-center gap-3 shrink-0">
      <div class="px-3.5 py-2 rounded-xl bg-black/30 border border-white/10 text-right">
        <p class="text-[10px] uppercase text-slate-300">Active Display</p>
        <p class="text-xs font-bold text-[#2DD4BF] font-mono-num"><?= e($newOrderPayload['displayCurrency']) ?> (<?= e($newOrderPayload['currencySymbol']) ?>)</p>
      </div>
      <a href="bulk-order.php" class="px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 border border-white/15 text-xs font-semibold text-white transition">
        Bulk Orders
      </a>
    </div>
  </div>

  <?php if (empty($newOrderPayload['categories']) || empty($newOrderPayload['services'])): ?>
    <div class="bg-[#111A2E] border border-slate-800 rounded-2xl p-10 text-center">
      <h2 class="text-lg font-semibold text-white">No Active Services Available</h2>
      <p class="text-sm text-slate-400 mt-1">Services must be enabled by the panel administrator before orders can be placed.</p>
    </div>
  <?php else: ?>
    <div class="grid lg:grid-cols-12 gap-6 items-start">
      <!-- Left Spec Sidebar Summary (Aurora puts spec deck on left for distinct spatial layout) -->
      <div class="lg:col-span-4 space-y-4 order-2 lg:order-1">
        <div class="bg-[#111A2E] border border-slate-800 rounded-2xl p-5 space-y-4">
          <div class="flex items-center justify-between">
            <h2 class="text-sm font-bold uppercase tracking-wider text-[#2DD4BF]">Live Specification</h2>
            <span x-show="selectedService" class="text-xs font-mono-num text-slate-400" x-text="selectedService ? ('#' + selectedService.id) : ''"></span>
          </div>

          <template x-if="selectedService">
            <div class="space-y-4">
              <div class="flex items-start gap-3">
                <span x-html="getIconHtml(selectedService.icon_key || 'sparkles')"></span>
                <div>
                  <p class="text-xs text-[#2DD4BF] font-semibold" x-text="selectedService.category_name"></p>
                  <h3 class="text-sm font-bold text-white mt-0.5" x-text="selectedService.name"></h3>
                </div>
              </div>

              <div class="grid grid-cols-1 gap-2.5">
                <div class="p-3 rounded-xl bg-[#0B1220] border border-slate-800 flex items-center justify-between">
                  <span class="text-xs text-slate-400">Selling Rate / 1K</span>
                  <span class="text-sm font-bold text-[#2DD4BF] font-mono-num" x-text="currencySymbol + formatRate(selectedService.price_per_1000)"></span>
                </div>
                <div class="p-3 rounded-xl bg-[#0B1220] border border-slate-800 flex items-center justify-between">
                  <span class="text-xs text-slate-400">Allowed Range</span>
                  <span class="text-xs font-bold text-white font-mono-num" x-text="Number(selectedService.min_qty).toLocaleString() + ' – ' + Number(selectedService.max_qty).toLocaleString()"></span>
                </div>
                <div class="p-3 rounded-xl bg-[#0B1220] border border-slate-800 flex items-center justify-between">
                  <span class="text-xs text-slate-400">Wallet Available</span>
                  <span class="text-xs font-bold text-[#2DD4BF] font-mono-num" x-text="currencySymbol + Number(walletBalance).toFixed(2)"></span>
                </div>
              </div>
            </div>
          </template>
        </div>
      </div>

      <!-- Right Main 9-Field Interactive Order Builder -->
      <form @submit.prevent="submitOrder" class="lg:col-span-8 bg-[#111A2E] border border-slate-800 rounded-2xl p-5 sm:p-6 space-y-5 order-1 lg:order-2">
        <template x-if="feedback">
          <div :class="feedback.ok ? 'bg-teal-950/60 border-teal-500/40 text-[#2DD4BF]' : 'bg-rose-950/60 border-rose-500/40 text-rose-300'" class="p-3.5 rounded-xl border text-sm font-medium flex items-center justify-between gap-3">
            <span x-text="feedback.message"></span>
            <template x-if="feedback.ok && feedback.order_id">
              <a :href="'order-details.php?id=' + feedback.order_id" class="text-xs font-bold underline shrink-0">Inspect Order</a>
            </template>
          </div>
        </template>

        <!-- 1. Search Bar -->
        <div class="relative" @click.outside="searchOpen = false">
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">1. Quick Search Service</label>
          <div class="relative">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4 text-slate-400 absolute left-3.5 top-3 pointer-events-none"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input
              type="text"
              x-model="globalSearch"
              @focus="if (globalSearch.trim().length > 0) searchOpen = true"
              @input.debounce.220ms="performAjaxSearch()"
              placeholder="Search service ID, platform or keyword..."
              class="w-full pl-10 pr-20 py-2.5 rounded-xl bg-[#0B1220] border border-slate-700 text-sm text-white focus:outline-none focus:border-[#0D9488]"
            >
            <button type="button" x-show="globalSearch.length > 0" @click="globalSearch = ''; searchResults = []; searchOpen = false" class="absolute right-3 top-2 px-2 py-1 text-[11px] font-semibold text-slate-400 hover:text-white">Clear</button>
          </div>
          <div x-show="searchOpen" x-cloak class="absolute left-0 right-0 mt-1.5 theme-search-dropdown rounded-2xl max-h-72 overflow-y-auto z-40">
            <template x-if="searchLoading"><div class="p-4 text-xs text-slate-400">Searching...</div></template>
            <template x-if="!searchLoading && searchResults.length === 0"><div class="p-4 text-xs text-slate-400">No services found.</div></template>
            <template x-if="!searchLoading && searchResults.length > 0">
              <div class="divide-y divide-slate-800">
                <template x-for="res in searchResults" :key="res.id">
                  <button type="button" @click="pickSearchResult(res)" class="w-full px-4 py-2.5 text-left hover:bg-slate-800/70 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                      <span x-html="getIconHtml(res.icon_key)"></span>
                      <div class="min-w-0">
                        <p class="text-xs font-semibold text-white truncate">
                          <span class="font-mono-num text-[#2DD4BF] mr-1" x-text="'#' + res.id"></span>
                          <span x-text="res.name"></span>
                        </p>
                        <p class="text-[11px] text-slate-400 truncate" x-text="res.category_name"></p>
                      </div>
                    </div>
                    <span class="text-xs font-bold text-[#2DD4BF] font-mono-num shrink-0" x-text="currencySymbol + formatRate(res.price_per_1000)"></span>
                  </button>
                </template>
              </div>
            </template>
          </div>
        </div>

        <!-- 2. Searchable Category Dropdown -->
        <div class="relative" @click.outside="categoryDropdownOpen = false">
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">2. Category</label>
          <button type="button" @click="categoryDropdownOpen = !categoryDropdownOpen; serviceDropdownOpen = false" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-700 bg-[#0B1220] hover:border-[#0D9488] flex items-center justify-between gap-3 text-left transition">
            <div class="flex items-center gap-3 min-w-0">
              <span x-html="getIconHtml(selectedCategory ? (selectedCategory.icon_key || 'globe') : 'globe')"></span>
              <span class="text-sm font-semibold text-white truncate" x-text="selectedCategory ? selectedCategory.name : 'Select category'"></span>
            </div>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4 text-slate-400 shrink-0"><polyline points="6 9 12 15 18 9"/></svg>
          </button>
          <div x-show="categoryDropdownOpen" x-cloak class="absolute left-0 right-0 mt-1.5 theme-search-dropdown rounded-2xl z-30 overflow-hidden">
            <div class="p-2.5 border-b border-slate-800">
              <input type="text" x-model="categorySearch" placeholder="Filter categories..." class="w-full px-3 py-1.5 rounded-lg border border-slate-700 text-xs">
            </div>
            <div class="max-h-60 overflow-y-auto divide-y divide-slate-800">
              <template x-for="cat in filteredCategories" :key="cat.id">
                <button type="button" @click="selectCategory(cat.id); categoryDropdownOpen = false" class="w-full px-3.5 py-2.5 flex items-center justify-between gap-3 text-left hover:bg-slate-800/70">
                  <div class="flex items-center gap-3 min-w-0">
                    <span x-html="getIconHtml(cat.icon_key || 'globe')"></span>
                    <span class="text-sm font-semibold text-white truncate" x-text="cat.name"></span>
                  </div>
                  <span class="text-xs font-mono-num text-[#2DD4BF]" x-text="cat.active_services_count || 0"></span>
                </button>
              </template>
            </div>
          </div>
        </div>

        <!-- 3. Searchable Service Dropdown -->
        <div class="relative" @click.outside="serviceDropdownOpen = false">
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">3. Service</label>
          <button type="button" @click="serviceDropdownOpen = !serviceDropdownOpen; categoryDropdownOpen = false" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-700 bg-[#0B1220] hover:border-[#0D9488] flex items-center justify-between gap-3 text-left transition">
            <div class="flex items-center gap-3 min-w-0">
              <span x-html="getIconHtml(selectedService ? (selectedService.icon_key || 'sparkles') : 'sparkles')"></span>
              <span class="text-sm font-semibold text-white truncate">
                <span x-show="selectedService" class="font-mono-num text-[#2DD4BF] mr-1" x-text="selectedService ? ('#' + selectedService.id) : ''"></span>
                <span x-text="selectedService ? selectedService.name : 'Select service'"></span>
              </span>
            </div>
            <span class="text-xs font-mono-num text-[#2DD4BF] shrink-0" x-text="selectedService ? (currencySymbol + formatRate(selectedService.price_per_1000)) : ''"></span>
          </button>
          <div x-show="serviceDropdownOpen" x-cloak class="absolute left-0 right-0 mt-1.5 theme-search-dropdown rounded-2xl z-30 overflow-hidden">
            <div class="p-2.5 border-b border-slate-800">
              <input type="text" x-model="serviceSearch" placeholder="Search service ID or name..." class="w-full px-3 py-1.5 rounded-lg border border-slate-700 text-xs">
            </div>
            <div class="max-h-72 overflow-y-auto divide-y divide-slate-800">
              <template x-for="svc in categoryServices" :key="svc.id">
                <button type="button" @click="selectService(svc.id); serviceDropdownOpen = false" class="w-full px-3.5 py-2.5 flex items-center justify-between gap-3 text-left hover:bg-slate-800/70">
                  <div class="flex items-center gap-3 min-w-0">
                    <span x-html="getIconHtml(svc.icon_key || 'sparkles')"></span>
                    <div class="min-w-0">
                      <p class="text-xs font-semibold text-white truncate">
                        <span class="font-mono-num text-[#2DD4BF] mr-1" x-text="'#' + svc.id"></span>
                        <span x-text="svc.name"></span>
                      </p>
                      <p class="text-[11px] text-slate-400 font-mono-num" x-text="'Min ' + svc.min_qty + ' · Max ' + svc.max_qty"></p>
                    </div>
                  </div>
                  <span class="text-xs font-bold text-[#2DD4BF] font-mono-num shrink-0" x-text="currencySymbol + formatRate(svc.price_per_1000)"></span>
                </button>
              </template>
            </div>
          </div>
        </div>

        <!-- 4. Service Description -->
        <div class="p-4 rounded-xl bg-[#0B1220] border border-slate-800 space-y-1.5">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">4. Service Description</span>
            <span x-show="selectedService" class="text-xs font-mono-num text-[#2DD4BF]" x-text="selectedService ? (selectedService.type || 'default') : ''"></span>
          </div>
          <p class="text-xs text-slate-300 leading-relaxed whitespace-pre-line" x-text="selectedService && selectedService.description ? selectedService.description : 'Select a service to view delivery guidelines.'"></p>
        </div>

        <!-- 5. Link -->
        <div>
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">5. Link</label>
          <input type="text" x-model="link" required placeholder="https://instagram.com/username or post URL" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-700 bg-[#0B1220] text-sm text-white">
        </div>

        <!-- 6. Quantity & 7. Limits -->
        <div>
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">6. Quantity</label>
          <input type="number" x-model="quantity" :min="selectedService ? selectedService.min_qty : 1" :max="selectedService ? selectedService.max_qty : 1000000" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-700 bg-[#0B1220] text-sm font-mono-num text-white">
          <div class="mt-2 flex items-center justify-between text-xs text-slate-400 bg-[#0B1220] border border-slate-800 rounded-xl px-3.5 py-2">
            <span>7. Min / Max Qty</span>
            <span class="font-mono-num text-slate-200" x-text="selectedService ? (Number(selectedService.min_qty).toLocaleString() + ' / ' + Number(selectedService.max_qty).toLocaleString()) : '-'"></span>
          </div>
        </div>

        <!-- 8. Charge -->
        <div class="p-4 rounded-xl bg-[#0B1220] border border-teal-500/30 flex flex-wrap items-center justify-between gap-3">
          <div>
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">8. Charge (<?= e($newOrderPayload['displayCurrency']) ?>)</p>
            <template x-if="displayCurrency !== baseCurrency">
              <p class="text-[11px] text-slate-400 mt-0.5" x-text="'Settled in ' + baseCurrency + ': ' + baseSymbol + calculatedBaseCharge.toFixed(4)"></p>
            </template>
          </div>
          <p class="text-2xl font-bold text-[#2DD4BF] font-mono-num" x-text="currencySymbol + calculatedCharge.toFixed(4)"></p>
        </div>

        <!-- 9. Submit -->
        <button type="submit" :disabled="submitting || !selectedService" class="w-full py-3 rounded-xl bg-gradient-to-r from-[#0D9488] to-[#4F46E5] text-white text-sm font-bold hover:opacity-95 disabled:opacity-50 transition">
          <span x-text="submitting ? 'Submitting Order...' : '9. Submit Order'"></span>
        </button>
      </form>
    </div>
  <?php endif; ?>
</div>
