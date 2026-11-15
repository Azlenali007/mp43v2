window.SMMApp = {
  _rzpSdkPromise: null,

  getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? (meta.getAttribute('content') || '') : '';
  },

  async _parseResponse(res) {
    const text = await res.text();
    let data = null;
    try {
      data = text ? JSON.parse(text) : null;
    } catch (e) {
      data = null;
    }
    if (data && typeof data === 'object') {
      if (typeof data.ok === 'undefined') {
        data.ok = res.ok && data.success !== false;
      }
      return data;
    }
    return {
      ok: false,
      status: res.status,
      message: res.status === 419
        ? 'Security token expired. Please refresh the page and try again.'
        : 'Server returned an unexpected response (HTTP ' + res.status + ').'
    };
  },

  async postJson(url, payload) {
    const csrf = this.getCsrfToken();
    const body = Object.assign({ csrf_token: csrf }, payload || {});
    const res = await fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-Token': csrf,
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify(body)
    });
    return this._parseResponse(res);
  },

  async postForm(url, formData) {
    const csrf = this.getCsrfToken();
    if (formData instanceof FormData && !formData.has('csrf_token') && csrf) {
      formData.append('csrf_token', csrf);
    }
    const res = await fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-Token': csrf,
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: formData
    });
    return this._parseResponse(res);
  },

  showToast(message, type = 'info') {
    if (!message) return;
    let container = document.getElementById('smmToastContainer');
    if (!container) {
      container = document.createElement('div');
      container.id = 'smmToastContainer';
      container.className = 'fixed bottom-5 right-5 z-50 flex flex-col gap-2 max-w-sm w-full px-4 pointer-events-none';
      document.body.appendChild(container);
    }
    const tone = type === 'success'
      ? 'bg-emerald-600 text-white border-emerald-500'
      : type === 'error'
        ? 'bg-rose-600 text-white border-rose-500'
        : 'bg-[#151515] text-white border-slate-700';
    const toast = document.createElement('div');
    toast.className = 'pointer-events-auto px-4 py-3 rounded-xl border shadow-lg text-xs font-semibold flex items-center justify-between gap-3 ' + tone;
    toast.setAttribute('role', 'status');
    const span = document.createElement('span');
    span.textContent = message;
    const closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.className = 'opacity-80 hover:opacity-100 font-bold shrink-0';
    closeBtn.textContent = '×';
    closeBtn.onclick = () => toast.remove();
    toast.appendChild(span);
    toast.appendChild(closeBtn);
    container.appendChild(toast);
    setTimeout(() => {
      if (toast.parentNode) toast.remove();
    }, 4500);
  },

  async switchCurrency(code, apiBase, triggerEl) {
    if (!code) return;
    if (triggerEl) {
      triggerEl.disabled = true;
      triggerEl.dataset.origOpacity = triggerEl.style.opacity || '';
      triggerEl.style.opacity = '0.6';
    }
    try {
      const base = apiBase || '';
      const res = await this.postJson(base + 'api/currency.php', { currency: code });
      if (res && res.ok) {
        this.showToast(res.message || ('Currency switched to ' + code), 'success');
        setTimeout(() => window.location.reload(), 200);
        return res;
      }
      this.showToast((res && res.message) ? res.message : 'Unable to switch display currency.', 'error');
      return res;
    } catch (e) {
      this.showToast('Network error while switching currency. Please try again.', 'error');
      return { ok: false };
    } finally {
      if (triggerEl) {
        triggerEl.disabled = false;
        triggerEl.style.opacity = triggerEl.dataset.origOpacity || '';
      }
    }
  },

  ensureRazorpaySdk() {
    if (typeof window.Razorpay === 'function') {
      return Promise.resolve(window.Razorpay);
    }
    if (this._rzpSdkPromise) {
      return this._rzpSdkPromise;
    }
    this._rzpSdkPromise = new Promise((resolve, reject) => {
      const existing = document.querySelector('script[src*="checkout.razorpay.com/v1/checkout.js"]');
      if (existing && typeof window.Razorpay === 'function') {
        resolve(window.Razorpay);
        return;
      }
      const script = existing || document.createElement('script');
      if (!existing) {
        script.src = 'https://checkout.razorpay.com/v1/checkout.js';
        script.async = true;
        document.head.appendChild(script);
      }
      const timer = setTimeout(() => {
        this._rzpSdkPromise = null;
        reject(new Error('Razorpay Checkout script timed out while loading. Please check your network or disable script blockers.'));
      }, 12000);
      script.addEventListener('load', () => {
        clearTimeout(timer);
        if (typeof window.Razorpay === 'function') {
          resolve(window.Razorpay);
        } else {
          this._rzpSdkPromise = null;
          reject(new Error('Razorpay Checkout SDK loaded but window.Razorpay was unavailable.'));
        }
      });
      script.addEventListener('error', () => {
        clearTimeout(timer);
        this._rzpSdkPromise = null;
        reject(new Error('Could not load Razorpay Checkout (checkout.js). Verify internet access or disable ad-blockers.'));
      });
    });
    return this._rzpSdkPromise;
  },

  formatMoney(amount, symbol = '₹') {
    const num = Number(amount || 0);
    return symbol + num.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 4 });
  },

  copyText(text, btnEl) {
    if (!navigator.clipboard) return;
    navigator.clipboard.writeText(text).then(() => {
      if (btnEl) {
        const orig = btnEl.textContent;
        btnEl.textContent = 'Copied';
        setTimeout(() => { btnEl.textContent = orig; }, 1600);
      }
    });
  }
};
