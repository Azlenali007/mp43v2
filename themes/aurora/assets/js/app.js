window.ThemeManager = {
  slug: 'aurora',
  chartPalette: {
    lineColor: '#2DD4BF',
    gradientTop: 'rgba(45, 212, 191, 0.32)',
    gradientBottom: 'rgba(45, 212, 191, 0.0)',
    pointBg: '#0B1220',
    tooltipBg: '#090F1C',
    gridColor: 'rgba(148, 163, 184, 0.14)',
    tickColor: '#94A3B8',
    donutColors: ['#2DD4BF', '#6366F1', '#F59E0B', '#F43F5E'],
    emptyDonut: '#1E293B'
  },
  switchCurrency(code, apiBase, triggerEl) {
    if (window.SMMApp && typeof window.SMMApp.switchCurrency === 'function') {
      return window.SMMApp.switchCurrency(code, apiBase, triggerEl);
    }
    return Promise.resolve({ ok: false });
  }
};
