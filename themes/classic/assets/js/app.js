window.ThemeManager = {
  slug: 'classic',
  chartPalette: {
    lineColor: '#7138D8',
    gradientTop: 'rgba(113, 56, 216, 0.28)',
    gradientBottom: 'rgba(113, 56, 216, 0.0)',
    pointBg: '#FFFFFF',
    tooltipBg: '#151515',
    gridColor: 'rgba(148, 163, 184, 0.15)',
    tickColor: '#64748B',
    donutColors: ['#16A36A', '#7138D8', '#F59E0B', '#EF4444'],
    emptyDonut: '#E2E8F0'
  },
  switchCurrency(code, apiBase, triggerEl) {
    if (window.SMMApp && typeof window.SMMApp.switchCurrency === 'function') {
      return window.SMMApp.switchCurrency(code, apiBase, triggerEl);
    }
    return Promise.resolve({ ok: false });
  }
};
