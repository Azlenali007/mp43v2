<?php
declare(strict_types=1);
/**
 * Classic Theme — Redesigned New Order Page
 * Variables available from /new-order.php:
 * $newOrderPayload (categories, services, icons, displayCurrency, baseCurrency, walletBalance, ...)
 */
?>
<div class="space-y-6" x-data='newOrderApp(<?= json_encode($newOrderPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
  <!-- Top Header Strip -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h1 class="text-2xl font-bold text-[#151515]">New Order</h1>
      <p class="text-sm text-slate-600 mt-1">Search any service, choose your platform category, and place instant automated orders.</p>
    </div>
    <div class="flex items-center gap-2.5">
      <span class="px-3 py-1.5 rounded-xl bg-purple-50 border border-purple-200 text-xs font-semibold text-[#7138D8] font-mono-num">
        <?= e($newOrderPayload['displayCurrency']) ?> (<?= e($newOrderPayload['currencySymbol']) ?>)
      </span>
      <a href="bulk-order.php" class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-[#151515] hover:bg-slate-50">
        Bulk Order
      </a>
    </div>
  </div>

  <?php if (empty($newOrderPayload['categories']) || empty($newOrderPayload['services'])): ?>
    <div class="bg-white border border-slate-200 rounded-2xl p-10 text-center">
      <h2 class="text-lg font-semibold text-[#151515]">No Active Services Available</h2>
      <p class="text-sm text-slate-500 mt-1">Services must be enabled by the panel administrator before orders can be placed.</p>
    </div>
  <?php else: ?>
    <div class="grid lg:grid-cols-12 gap-6 items-start">
      <!-- Main Order Builder (9 Ordered Fields) -->
      <form @submit.prevent="submitOrder" class="lg:col-span-7 bg-white border border-slate-200 rounded-2xl p-5 sm:p-6 space-y-5 shadow-sm">
        <!-- Feedback Alert -->
        <template x-if="feedback">
          <div :class="feedback.ok ? 'bg-emerald-50 border-emerald-200 text-[#16A36A]' : 'bg-rose-50 border-rose-200 text-rose-700'" class="p-3.5 rounded-xl border text-sm font-medium flex items-center justify-between gap-3">
            <span x-text="feedback.message"></span>
            <template x-if="feedback.ok && feedback.order_id">
              <a :href="'order-details.php?id=' + feedback.order_id" class="text-xs font-bold underline shrink-0">View Order</a>
            </template>
          </div>
        </template>

        <!-- 1. Dynamic AJAX Search Bar -->
        <div class="relative" @click.outside="searchOpen = false">
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">1. Quick Search Service</label>
          <div class="relative">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4 text-slate-400 absolute left-3.5 top-3 pointer-events-none"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input
              type="text"
              x-model="globalSearch"
              @focus="if (globalSearch.trim().length > 0) searchOpen = true"
              @input.debounce.220ms="performAjaxSearch()"
              placeholder="Search by Service ID, platform, keyword (e.g. 12, Instagram, Views)..."
              class="w-full pl-10 pr-20 py-2.5 rounded-xl bg-[#F8F9FC] border border-slate-200 text-sm focus:outline-none focus:bg-white focus:border-[#7138D8] transition"
            >
            <button
              type="button"
              x-show="globalSearch.length > 0"
              @click="globalSearch = ''; searchResults = []; searchOpen = false"
              class="absolute right-3 top-2 px-2 py-1 text-[11px] font-semibold text-slate-500 hover:text-[#151515]"
            >Clear</button>
          </div>

          <div x-show="searchOpen" x-cloak class="absolute left-0 right-0 mt-1.5 bg-white border border-slate-200 rounded-2xl theme-search-dropdown max-h-72 overflow-y-auto z-40">
            <template x-if="searchLoading">
              <div class="p-4 text-xs text-slate-500">Searching services...</div>
            </template>
            <template x-if="!searchLoading && searchResults.length === 0">
              <div class="p-4 text-xs text-slate-500">No matching enabled services found.</div>
            </template>
            <template x-if="!searchLoading && searchResults.length > 0">
              <div class="divide-y divide-slate-100">
                <template x-for="res in searchResults" :key="res.id">
                  <button
                    type="button"
                    @click="pickSearchResult(res)"
                    class="w-full px-4 py-2.5 text-left hover:bg-purple-50/60 flex items-center justify-between gap-3 transition"
                  >
                    <div class="flex items-center gap-3 min-w-0">
                      <span x-html="getIconHtml(res.icon_key)"></span>
                      <div class="min-w-0">
                        <p class="text-xs font-semibold text-[#151515] truncate">
                          <span class="font-mono-num text-[#7138D8] mr-1" x-text="'#' + res.id"></span>
                          <span x-text="res.name"></span>
                        </p>
                        <p class="text-[11px] text-slate-500 truncate" x-text="res.category_name + ' · Min ' + res.min_qty + ' / Max ' + res.max_qty"></p>
                      </div>
                    </div>
                    <span class="text-xs font-bold text-[#16A36A] font-mono-num shrink-0" x-text="currencySymbol + formatRate(res.price_per_1000)"></span>
                  </button>
                </template>
              </div>
            </template>
          </div>
        </div>

        <!-- 2. Searchable Category Dropdown -->
        <div class="relative" @click.outside="categoryDropdownOpen = false">
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">2. Category</label>
          <button
            type="button"
            @click="categoryDropdownOpen = !categoryDropdownOpen; serviceDropdownOpen = false"
            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white hover:border-slate-300 flex items-center justify-between gap-3 text-left transition"
          >
            <div class="flex items-center gap-3 min-w-0">
              <span x-html="getIconHtml(selectedCategory ? (selectedCategory.icon_key || 'globe') : 'globe')"></span>
              <div class="min-w-0">
                <p class="text-sm font-semibold text-[#151515] truncate" x-text="selectedCategory ? selectedCategory.name : 'Select a category'"></p>
                <p class="text-[11px] text-slate-500" x-text="selectedCategory ? ((selectedCategory.active_services_count || 0) + ' active services') : ''"></p>
              </div>
            </div>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4 text-slate-400 shrink-0"><polyline points="6 9 12 15 18 9"/></svg>
          </button>

          <div x-show="categoryDropdownOpen" x-cloak class="absolute left-0 right-0 mt-1.5 bg-white border border-slate-200 rounded-2xl theme-search-dropdown z-30 overflow-hidden">
            <div class="p-2.5 border-b border-slate-100 bg-slate-50">
              <input type="text" x-model="categorySearch" placeholder="Filter categories..." class="w-full px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-xs focus:outline-none focus:border-[#7138D8]">
            </div>
            <div class="max-h-64 overflow-y-auto divide-y divide-slate-100">
              <template x-if="filteredCategories.length === 0">
                <div class="p-4 text-xs text-slate-500 text-center">No matching categories.</div>
              </template>
              <template x-for="cat in filteredCategories" :key="cat.id">
                <button
                  type="button"
                  @click="selectCategory(cat.id); categoryDropdownOpen = false"
                  :class="Number(selectedCategoryId) === Number(cat.id) ? 'bg-purple-50/70' : 'hover:bg-slate-50'"
                  class="w-full px-3.5 py-2.5 flex items-center justify-between gap-3 text-left transition"
                >
                  <div class="flex items-center gap-3 min-w-0">
                    <span x-html="getIconHtml(cat.icon_key || 'globe')"></span>
                    <span class="text-sm font-semibold text-[#151515] truncate" x-text="cat.name"></span>
                  </div>
                  <span class="px-2 py-0.5 rounded-md bg-slate-100 text-[11px] font-mono-num text-slate-600 shrink-0" x-text="cat.active_services_count || 0"></span>
                </button>
              </template>
            </div>
          </div>
        </div>

        <!-- 3. Searchable Service Dropdown -->
        <div class="relative" @click.outside="serviceDropdownOpen = false">
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">3. Service</label>
          <button
            type="button"
            @click="serviceDropdownOpen = !serviceDropdownOpen; categoryDropdownOpen = false"
            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white hover:border-slate-300 flex items-center justify-between gap-3 text-left transition"
          >
            <div class="flex items-center gap-3 min-w-0">
              <span x-html="getIconHtml(selectedService ? (selectedService.icon_key || 'sparkles') : 'sparkles')"></span>
              <div class="min-w-0">
                <p class="text-sm font-semibold text-[#151515] truncate">
                  <span x-show="selectedService" class="font-mono-num text-[#7138D8] mr-1" x-text="selectedService ? ('#' + selectedService.id) : ''"></span>
                  <span x-text="selectedService ? selectedService.name : 'Select a service'"></span>
                </p>
                <p class="text-[11px] text-slate-500 font-mono-num" x-text="selectedService ? ('Rate: ' + currencySymbol + formatRate(selectedService.price_per_1000) + ' per 1,000') : ''"></p>
              </div>
            </div>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4 text-slate-400 shrink-0"><polyline points="6 9 12 15 18 9"/></svg>
          </button>

          <div x-show="serviceDropdownOpen" x-cloak class="absolute left-0 right-0 mt-1.5 bg-white border border-slate-200 rounded-2xl theme-search-dropdown z-30 overflow-hidden">
            <div class="p-2.5 border-b border-slate-100 bg-slate-50">
              <input type="text" x-model="serviceSearch" placeholder="Search by service ID or name..." class="w-full px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-xs focus:outline-none focus:border-[#7138D8]">
            </div>
            <div class="max-h-72 overflow-y-auto divide-y divide-slate-100">
              <template x-if="categoryServices.length === 0">
                <div class="p-4 text-xs text-slate-500 text-center">No enabled services match this filter.</div>
              </template>
              <template x-for="svc in categoryServices" :key="svc.id">
                <button
                  type="button"
                  @click="selectService(svc.id); serviceDropdownOpen = false"
                  :class="Number(selectedServiceId) === Number(svc.id) ? 'bg-purple-50/70' : 'hover:bg-slate-50'"
                  class="w-full px-3.5 py-2.5 flex items-center justify-between gap-3 text-left transition"
                >
                  <div class="flex items-center gap-3 min-w-0">
                    <span x-html="getIconHtml(svc.icon_key || 'sparkles')"></span>
                    <div class="min-w-0">
                      <p class="text-xs font-semibold text-[#151515] truncate">
                        <span class="font-mono-num text-[#7138D8] mr-1" x-text="'#' + svc.id"></span>
                        <span x-text="svc.name"></span>
                      </p>
                      <p class="text-[11px] text-slate-500 font-mono-num" x-text="'Min ' + Number(svc.min_qty).toLocaleString() + ' · Max ' + Number(svc.max_qty).toLocaleString()"></p>
                    </div>
                  </div>
                  <div class="text-right shrink-0">
                    <p class="text-xs font-bold text-[#16A36A] font-mono-num" x-text="currencySymbol + formatRate(svc.price_per_1000)"></p>
                    <p class="text-[10px] text-slate-400">per 1K</p>
                  </div>
                </button>
              </template>
            </div>
          </div>
        </div>

        <!-- 4. Automatic Service Description -->
        <div class="p-4 rounded-xl bg-[#F8F9FC] border border-slate-200 space-y-2">
          <div class="flex items-center justify-between gap-2">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500">4. Service Description</span>
            <span x-show="selectedService" class="text-[11px] font-mono-num font-semibold text-[#7138D8]" x-text="selectedService ? ('ID #' + selectedService.id + ' · ' + (selectedService.type || 'default')) : ''"></span>
          </div>
          <p class="text-xs text-slate-700 leading-relaxed whitespace-pre-line" x-text="selectedService && selectedService.description ? selectedService.description : 'Select a service above to inspect automated delivery guidelines and specifications.'"></p>
          <div x-show="selectedService" class="flex flex-wrap gap-2 pt-1">
            <span x-show="selectedService && Number(selectedService.refill) === 1" class="px-2 py-0.5 rounded-md bg-emerald-50 border border-emerald-200 text-[11px] font-semibold text-[#16A36A]">Refill Supported</span>
            <span x-show="selectedService && Number(selectedService.cancel) === 1" class="px-2 py-0.5 rounded-md bg-purple-50 border border-purple-200 text-[11px] font-semibold text-[#7138D8]">Cancel Allowed</span>
          </div>
        </div>

        <!-- 5. Target Link -->
        <div>
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">5. Link</label>
          <input type="text" x-model="link" required placeholder="https://instagram.com/username or post URL" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-[#7138D8]">
        </div>

        <!-- 6. Quantity & 7. Min / Max Qty -->
        <div>
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">6. Quantity</label>
          <input
            type="number"
            x-model="quantity"
            :min="selectedService ? selectedService.min_qty : 1"
            :max="selectedService ? selectedService.max_qty : 1000000"
            required
            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-mono-num focus:outline-none focus:border-[#7138D8]"
          >
          <div class="mt-2 flex items-center justify-between text-xs text-slate-600 bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2">
            <span class="font-semibold text-slate-500">7. Limits:</span>
            <span class="font-mono-num" x-text="selectedService ? ('Min Qty: ' + Number(selectedService.min_qty).toLocaleString() + '  |  Max Qty: ' + Number(selectedService.max_qty).toLocaleString()) : 'Select a service'"></span>
          </div>
        </div>

        <!-- 8. Charge -->
        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 flex flex-wrap items-center justify-between gap-3">
          <div>
            <p class="text-xs font-bold uppercase tracking-wider text-slate-500">8. Total Charge (<?= e($newOrderPayload['displayCurrency']) ?>)</p>
            <p class="text-[11px] text-slate-500 mt-0.5">
              Wallet Balance: <strong class="text-[#16A36A] font-mono-num" x-text="currencySymbol + Number(walletBalance).toFixed(2)"></strong>
              <template x-if="displayCurrency !== baseCurrency">
                <span class="ml-1 text-slate-400" x-text="'(Base: ' + baseSymbol + calculatedBaseCharge.toFixed(4) + ' ' + baseCurrency + ')'"></span>
              </template>
            </p>
          </div>
          <p class="text-2xl font-bold text-[#151515] font-mono-num" x-text="currencySymbol + calculatedCharge.toFixed(4)"></p>
        </div>

        <!-- 9. Submit Order Button -->
        <button
          type="submit"
          :disabled="submitting || !selectedService"
          class="w-full py-3 rounded-xl bg-[#7138D8] hover:bg-[#5E28C2] text-white text-sm font-bold shadow-sm disabled:opacity-50 transition"
        >
          <span x-text="submitting ? 'Submitting Order...' : '9. Submit Order'"></span>
        </button>
      </form>

      <!-- Right Column: Live Service Specification & Guidance -->
      <div class="lg:col-span-5 space-y-5">
        <div class="bg-white border border-slate-200 rounded-2xl p-5 sm:p-6 space-y-4 shadow-sm">
          <h2 class="text-base font-bold text-[#151515]">Selected Service Summary</h2>
          <template x-if="!selectedService">
            <p class="text-sm text-slate-500">Pick a service on the left to preview exact rate and delivery details.</p>
          </template>
          <template x-if="selectedService">
            <div class="space-y-4">
              <div class="flex items-start gap-3">
                <span x-html="getIconHtml(selectedService.icon_key || 'sparkles')"></span>
                <div>
                  <p class="text-xs font-mono-num text-[#7138D8] font-semibold" x-text="'Service #' + selectedService.id + ' · ' + selectedService.category_name"></p>
                  <h3 class="text-base font-bold text-[#151515] mt-0.5" x-text="selectedService.name"></h3>
                </div>
              </div>

              <div class="grid grid-cols-3 gap-3 text-center">
                <div class="p-3 rounded-xl bg-[#F8F9FC] border border-slate-200">
                  <p class="text-[11px] text-slate-500">Rate / 1K</p>
                  <p class="text-sm font-bold text-[#16A36A] font-mono-num mt-0.5" x-text="currencySymbol + formatRate(selectedService.price_per_1000)"></p>
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

              <div class="p-4 rounded-xl bg-[#F8F9FC] border border-slate-200 space-y-2 text-xs text-slate-600">
                <p class="font-semibold text-[#151515]">Order Rules</p>
                <ul class="list-disc pl-4 space-y-1">
                  <li>Ensure target profiles and posts are public prior to ordering.</li>
                  <li>Avoid placing concurrent duplicate orders for the same link while active.</li>
                  <li>All charges are settled atomically in canonical <?= e($newOrderPayload['baseCurrency']) ?>.</li>
                </ul>
              </div>
            </div>
          </template>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>
