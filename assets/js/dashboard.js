window.initDashboardCharts = function (config) {
  if (typeof Chart === 'undefined') return;

  const pal = Object.assign({
    lineColor: '#7138D8',
    gradientTop: 'rgba(113, 56, 216, 0.28)',
    gradientBottom: 'rgba(113, 56, 216, 0.0)',
    pointBg: '#FFFFFF',
    tooltipBg: '#151515',
    gridColor: 'rgba(148, 163, 184, 0.15)',
    tickColor: '#64748B',
    donutColors: ['#16A36A', '#7138D8', '#F59E0B', '#EF4444'],
    emptyDonut: '#E2E8F0'
  }, config.palette || {});

  const salesCanvas = document.getElementById('salesOverviewChart');
  if (salesCanvas && config.series) {
    const ctx = salesCanvas.getContext('2d');
    const gradient = ctx.createLinearGradient(0, 0, 0, 240);
    gradient.addColorStop(0, pal.gradientTop);
    gradient.addColorStop(1, pal.gradientBottom);

    if (window._salesChartInstance) {
      window._salesChartInstance.destroy();
    }

    window._salesChartInstance = new Chart(ctx, {
      type: 'line',
      data: {
        labels: config.series.labels,
        datasets: [{
          label: config.seriesLabel || 'Spend',
          data: config.series.values,
          borderColor: pal.lineColor,
          backgroundColor: gradient,
          borderWidth: 2.5,
          tension: 0.4,
          fill: true,
          pointBackgroundColor: pal.pointBg,
          pointBorderColor: pal.lineColor,
          pointBorderWidth: 2,
          pointRadius: 4,
          pointHoverRadius: 6
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: pal.tooltipBg,
            titleColor: '#E5E7EB',
            bodyColor: '#FFFFFF',
            padding: 10,
            cornerRadius: 8,
            displayColors: false,
            callbacks: {
              label: (context) => (config.currencySymbol || '₹') + Number(context.parsed.y || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 })
            }
          }
        },
        scales: {
          x: {
            grid: { display: false },
            ticks: { color: pal.tickColor, font: { size: 11 } }
          },
          y: {
            beginAtZero: true,
            grid: { color: pal.gridColor },
            ticks: {
              color: pal.tickColor,
              font: { size: 11 },
              callback: (v) => (config.currencySymbol || '₹') + v
            }
          }
        }
      }
    });
  }

  const donutCanvas = document.getElementById('orderStatusChart');
  if (donutCanvas && config.statusBreakdown) {
    const counts = config.statusBreakdown;
    const total = counts.completed + counts.processing + counts.pending + counts.cancelled;
    const dataVals = total > 0 ? [counts.completed, counts.processing, counts.pending, counts.cancelled] : [1];
    const bgColors = total > 0 ? pal.donutColors : [pal.emptyDonut];

    if (window._donutChartInstance) {
      window._donutChartInstance.destroy();
    }

    window._donutChartInstance = new Chart(donutCanvas.getContext('2d'), {
      type: 'doughnut',
      data: {
        labels: total > 0 ? ['Completed', 'Processing', 'Pending', 'Cancelled'] : ['No Orders'],
        datasets: [{
          data: dataVals,
          backgroundColor: bgColors,
          borderWidth: 0,
          hoverOffset: 4
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '72%',
        plugins: {
          legend: { display: false },
          tooltip: { enabled: total > 0 }
        }
      }
    });
  }
};
