window.ThemeManager = {
  slug: 'aurora',
  async switchCurrency(code, apiBase) {
    try {
      const res = await window.SMMApp.postJson((apiBase || '') + 'api/currency.php', { currency: code });
      if (res && res.ok) {
        window.location.reload();
        return;
      }
      console.warn(res?.message || 'Unable to switch currency');
    } catch (e) {
      console.error('Currency switch error', e);
    }
  }
};
