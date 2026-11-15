window.SMMApp = {
  getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
  },

  async postJson(url, payload) {
    const res = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': this.getCsrfToken(),
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify(payload)
    });
    return res.json();
  },

  async postForm(url, formData) {
    const res = await fetch(url, {
      method: 'POST',
      headers: {
        'X-CSRF-Token': this.getCsrfToken(),
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: formData
    });
    return res.json();
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
