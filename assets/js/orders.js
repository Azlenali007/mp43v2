const createNewOrderApp = (initialData) => ({
  categories: initialData.categories || [],
  services: initialData.services || [],
  icons: initialData.icons || {},
  currencySymbol: initialData.currencySymbol || '₹',
  displayCurrency: initialData.displayCurrency || 'INR',
  displayDecimals: Number(initialData.displayDecimals ?? 2),
  baseCurrency: initialData.baseCurrency || 'INR',
  baseSymbol: initialData.baseSymbol || '₹',
  walletBalance: Number(initialData.walletBalance || 0),
  apiBase: initialData.apiBase || '',

  // Search & Custom Searchable Dropdown State
  globalSearch: '',
  searchOpen: false,
  searchLoading: false,
  searchResults: [],
  categoryDropdownOpen: false,
  serviceDropdownOpen: false,
  categorySearch: '',
  serviceSearch: '',

  selectedCategoryId: initialData.defaultCategoryId || null,
  selectedServiceId: initialData.defaultServiceId || null,
  link: '',
  quantity: '',
  submitting: false,
  feedback: null,

  init() {
    if (!this.selectedCategoryId && this.categories.length > 0) {
      this.selectedCategoryId = Number(this.categories[0].id);
    }
    this.syncFirstService();
  },

  get selectedCategory() {
    return this.categories.find(c => Number(c.id) === Number(this.selectedCategoryId)) || null;
  },

  get filteredCategories() {
    const q = this.categorySearch.trim().toLowerCase();
    if (!q) return this.categories;
    return this.categories.filter(c => (c.name || '').toLowerCase().includes(q));
  },

  get categoryServices() {
    let list = this.services;
    if (this.selectedCategoryId) {
      list = list.filter(s => Number(s.category_id) === Number(this.selectedCategoryId));
    }
    const q = this.serviceSearch.trim().toLowerCase();
    if (q) {
      list = list.filter(s =>
        (s.name || '').toLowerCase().includes(q) ||
        String(s.id) === q ||
        String(s.id).includes(q.replace('#', '')) ||
        (s.description || '').toLowerCase().includes(q)
      );
    }
    return list;
  },

  get selectedService() {
    return this.services.find(s => Number(s.id) === Number(this.selectedServiceId)) || null;
  },

  async performAjaxSearch() {
    const q = this.globalSearch.trim();
    if (!q) {
      this.searchResults = [];
      this.searchOpen = false;
      return;
    }
    this.searchOpen = true;
    this.searchLoading = true;
    try {
      const url = `${this.apiBase}api/services-search.php?q=${encodeURIComponent(q)}`;
      const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      const data = await res.json();
      if (data && data.ok && Array.isArray(data.services)) {
        this.searchResults = data.services;
      } else {
        this.searchResults = [];
      }
    } catch (e) {
      this.searchResults = [];
    } finally {
      this.searchLoading = false;
    }
  },

  pickSearchResult(svc) {
    // Ensure service exists in local pool or update its details cleanly
    const idx = this.services.findIndex(s => Number(s.id) === Number(svc.id));
    if (idx === -1) {
      this.services.push(svc);
    }
    this.selectedCategoryId = Number(svc.category_id);
    this.selectedServiceId = Number(svc.id);
    this.quantity = Number(svc.min_qty);
    this.searchOpen = false;
    this.globalSearch = `#${svc.id} ${svc.name}`;
    this.categorySearch = '';
    this.serviceSearch = '';
  },

  selectCategory(catId) {
    this.selectedCategoryId = Number(catId);
    this.serviceSearch = '';
    this.syncFirstService();
  },

  syncFirstService() {
    const list = this.categoryServices;
    if (list.length > 0) {
      const exists = list.some(s => Number(s.id) === Number(this.selectedServiceId));
      if (!exists) {
        this.selectedServiceId = Number(list[0].id);
        this.quantity = list[0].min_qty;
      }
    } else {
      this.selectedServiceId = null;
      this.quantity = '';
    }
  },

  selectService(svcId) {
    this.selectedServiceId = Number(svcId);
    const svc = this.selectedService;
    if (svc) {
      this.quantity = svc.min_qty;
    }
  },

  formatRate(val) {
    const num = Number(val || 0);
    if (num < 1 && num > 0) {
      return num.toFixed(4);
    }
    return num.toFixed(Math.max(2, Math.min(4, this.displayDecimals || 2)));
  },

  get calculatedCharge() {
    const svc = this.selectedService;
    const qty = Number(this.quantity || 0);
    if (!svc || qty <= 0) return 0;
    return (qty / 1000) * Number(svc.price_per_1000);
  },

  get calculatedBaseCharge() {
    const svc = this.selectedService;
    const qty = Number(this.quantity || 0);
    if (!svc || qty <= 0) return 0;
    const baseRate = Number(svc.base_price_per_1000 ?? svc.price_per_1000);
    return (qty / 1000) * baseRate;
  },

  getIconHtml(iconKey) {
    const icon = this.icons[iconKey] || this.icons['globe'] || { bg: 'bg-[#151515]', text: 'text-white', svg: '' };
    return `<span class="inline-flex items-center justify-center w-8 h-8 p-1.5 rounded-lg shrink-0 ${icon.bg} ${icon.text}">${icon.svg}</span>`;
  },

  async submitOrder() {
    if (this.submitting) return;
    this.feedback = null;
    if (!this.selectedService) {
      this.feedback = { ok: false, message: 'Please select a service first.' };
      return;
    }
    this.submitting = true;
    try {
      const res = await window.SMMApp.postJson(this.apiBase + 'api/new-order.php', {
        service_id: Number(this.selectedService.id),
        link: this.link,
        quantity: Number(this.quantity)
      });
      this.feedback = res;
      if (res.ok) {
        if (typeof res.balance_after_display !== 'undefined') {
          this.walletBalance = Number(res.balance_after_display);
        } else if (typeof res.balance_after !== 'undefined') {
          this.walletBalance = Number(res.balance_after);
        }
        this.link = '';
      }
    } catch (e) {
      this.feedback = { ok: false, message: 'Unable to submit order right now. Please try again.' };
    } finally {
      this.submitting = false;
    }
  }
});

window.newOrderApp = createNewOrderApp;
document.addEventListener('alpine:init', () => {
  if (window.Alpine) {
    window.Alpine.data('newOrderApp', createNewOrderApp);
  }
});
