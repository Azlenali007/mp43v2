<?php
declare(strict_types=1);
if (!defined('APP_ROOT')) {
    http_response_code(403);
    exit('Forbidden');
}
$pageTitle = $pageTitle ?? 'Command Center';
ob_start();
?>
<div class="space-y-6">
  <!-- Aurora Telemetry Header Banner -->
  <div class="aurora-hero-banner rounded-2xl p-6 sm:p-8 text-white flex flex-col lg:flex-row lg:items-center justify-between gap-6">
    <div class="space-y-2 max-w-xl">
      <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md bg-black/30 border border-teal-400/30 text-[11px] font-semibold text-teal-300 uppercase tracking-wider">Aurora Command</span>
      <h1 class="text-2xl sm:text-3xl font-bold tracking-tight">Hello, <?= e($user['name']) ?></h1>
      <p class="text-sm text-slate-200">Monitor fulfillment velocity, inspect order ledger trends, and launch instant social campaigns.</p>
    </div>
    <div class="flex flex-wrap items-center gap-3 shrink-0">
      <a href="<?= e($rp) ?>new-order.php" class="px-5 py-2.5 rounded-xl bg-teal-400 text-[#0B1220] text-xs font-bold hover:bg-teal-300 transition">+ Place New Order</a>
      <a href="<?= e($rp) ?>add-funds.php" class="px-5 py-2.5 rounded-xl bg-black/30 border border-white/20 text-white text-xs font-semibold hover:bg-black/45 transition">Add Wallet Funds</a>
    </div>
  </div>

  <!-- 4 Aurora KPI Telemetry Tiles -->
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="aurora-kpi rounded-2xl p-5 flex items-center gap-4">
      <div class="w-12 h-12 rounded-2xl bg-teal-500/15 border border-teal-500/30 text-teal-300 flex items-center justify-center shrink-0">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
      </div>
      <div class="min-w-0">
        <p class="text-xs font-medium text-slate-400">Wallet Balance</p>
        <p class="text-xl sm:text-2xl font-bold text-teal-300 font-mono-num truncate"><?= e(format_currency($user['wallet_balance'])) ?></p>
      </div>
    </div>

    <div class="aurora-kpi rounded-2xl p-5 flex items-center gap-4">
      <div class="w-12 h-12 rounded-2xl bg-indigo-500/15 border border-indigo-500/30 text-indigo-300 flex items-center justify-center shrink-0">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
      </div>
      <div class="min-w-0">
        <p class="text-xs font-medium text-slate-400">Total Orders</p>
        <p class="text-xl sm:text-2xl font-bold text-white font-mono-num"><?= number_format((int) $totalOrders) ?></p>
      </div>
    </div>

    <div class="aurora-kpi rounded-2xl p-5 flex items-center gap-4">
      <div class="w-12 h-12 rounded-2xl bg-teal-500/15 border border-teal-500/30 text-teal-300 flex items-center justify-center shrink-0">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6"><polyline points="20 6 9 17 4 12"/></svg>
      </div>
      <div class="min-w-0">
        <p class="text-xs font-medium text-slate-400">Completed</p>
        <p class="text-xl sm:text-2xl font-bold text-white font-mono-num"><?= number_format((int) $completedOrders) ?></p>
      </div>
    </div>

    <div class="aurora-kpi rounded-2xl p-5 flex items-center gap-4">
      <div class="w-12 h-12 rounded-2xl bg-amber-500/15 border border-amber-500/30 text-amber-300 flex items-center justify-center shrink-0">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
      </div>
      <div class="min-w-0">
        <p class="text-xs font-medium text-slate-400">Active / Pending</p>
        <p class="text-xl sm:text-2xl font-bold text-white font-mono-num"><?= number_format((int) $pendingOrders) ?></p>
      </div>
    </div>
  </div>

  <!-- Aurora Reversed Proportion Charts Row (Status Left, Spend Telemetry Right) -->
  <div class="grid lg:grid-cols-12 gap-6">
    <div class="lg:col-span-5 aurora-surface rounded-2xl p-5 sm:p-6 flex flex-col justify-between">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-base font-bold text-white">Fulfillment Mix</h2>
        <a href="<?= e($rp) ?>orders.php" class="text-xs font-semibold text-teal-300 hover:underline">Inspect All &rarr;</a>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-center my-auto">
        <div class="relative h-48 flex items-center justify-center">
          <canvas id="orderStatusChart"></canvas>
          <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
            <span class="text-2xl font-bold text-white font-mono-num"><?= number_format((int) $totalOrders) ?></span>
            <span class="text-[11px] text-slate-400">Total</span>
          </div>
        </div>

        <div class="space-y-3 text-xs">
          <?php
          $pct = fn(int $n): string => $totalOrders > 0 ? number_format(($n / $totalOrders) * 100, 1) . '%' : '0.0%';
          ?>
          <div class="flex items-center justify-between">
            <span class="text-slate-300 font-medium">Completed</span>
            <span class="font-mono-num font-semibold text-teal-300"><?= (int) $completedOrders ?> (<?= $pct((int) $completedOrders) ?>)</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-slate-300 font-medium">Processing</span>
            <span class="font-mono-num font-semibold text-indigo-300"><?= (int) $processingCount ?> (<?= $pct((int) $processingCount) ?>)</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-slate-300 font-medium">Pending</span>
            <span class="font-mono-num font-semibold text-amber-300"><?= (int) $strictlyPending ?> (<?= $pct((int) $strictlyPending) ?>)</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-slate-300 font-medium">Cancelled / Refunded</span>
            <span class="font-mono-num font-semibold text-rose-300"><?= (int) $cancelledCount ?> (<?= $pct((int) $cancelledCount) ?>)</span>
          </div>
        </div>
      </div>
    </div>

    <div class="lg:col-span-7 aurora-surface rounded-2xl p-5 sm:p-6">
      <div class="flex items-center justify-between gap-4 mb-4">
        <h2 class="text-base font-bold text-white">Spend Telemetry</h2>
        <form method="GET" action="<?= e($rp) ?>dashboard.php">
          <select name="range" onchange="this.form.submit()" class="px-3 py-1.5 rounded-xl aurora-input border text-xs font-medium">
            <option value="7d" <?= $range === '7d' ? 'selected' : '' ?>>Last 7 Days</option>
            <option value="30d" <?= $range === '30d' ? 'selected' : '' ?>>Last 30 Days</option>
          </select>
        </form>
      </div>
      <div class="h-64">
        <canvas id="salesOverviewChart"></canvas>
      </div>
    </div>
  </div>

  <!-- Bottom Row: Announcements + Recent Orders -->
  <div class="grid lg:grid-cols-12 gap-6">
    <div class="lg:col-span-5 aurora-surface rounded-2xl p-5 sm:p-6">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-base font-bold text-white">System Broadcasts</h2>
      </div>
      <?php if (empty($announcements)): ?>
        <div class="py-10 text-center border border-dashed border-slate-700 rounded-xl">
          <p class="text-sm font-medium text-slate-300">No announcements right now</p>
          <p class="text-xs text-slate-500 mt-1">Platform updates from administrators appear here.</p>
        </div>
      <?php else: ?>
        <div class="divide-y divide-slate-800">
          <?php foreach ($announcements as $ann): ?>
            <div class="py-3.5 first:pt-0 last:pb-0">
              <div class="flex items-center justify-between gap-2">
                <h3 class="text-sm font-semibold text-white"><?= e($ann['title']) ?></h3>
                <span class="text-[11px] text-teal-400 shrink-0"><?= e(date('d M', strtotime($ann['created_at']))) ?></span>
              </div>
              <p class="text-xs text-slate-400 mt-1 leading-relaxed"><?= e($ann['content']) ?></p>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="lg:col-span-7 aurora-surface rounded-2xl p-5 sm:p-6">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-base font-bold text-white">Recent Orders</h2>
        <a href="<?= e($rp) ?>orders.php" class="text-xs font-semibold text-teal-300 hover:underline">View All &rarr;</a>
      </div>

      <?php if (empty($recentOrders)): ?>
        <div class="py-10 text-center border border-dashed border-slate-700 rounded-xl">
          <p class="text-sm font-medium text-slate-300">No orders placed yet</p>
          <p class="text-xs text-slate-500 mt-1">Submitted campaigns will appear here with real-time status.</p>
          <a href="<?= e($rp) ?>new-order.php" class="mt-4 inline-flex px-4 py-2 rounded-xl aurora-btn-primary text-xs font-semibold">Place Your First Order</a>
        </div>
      <?php else: ?>
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b border-slate-800 text-xs font-semibold text-slate-400">
                <th class="py-2.5 pr-3">Order ID</th>
                <th class="py-2.5 px-3">Service</th>
                <th class="py-2.5 px-3 text-right">Qty</th>
                <th class="py-2.5 px-3">Status</th>
                <th class="py-2.5 pl-3 text-right">Time</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/80 text-sm">
              <?php foreach ($recentOrders as $ord): ?>
                <tr class="hover:bg-white/[0.02]">
                  <td class="py-3 pr-3 font-mono-num text-xs font-semibold">
                    <a href="<?= e($rp) ?>order-details.php?id=<?= (int) $ord['id'] ?>" class="text-teal-300 hover:underline">#<?= e($ord['order_code']) ?></a>
                  </td>
                  <td class="py-3 px-3">
                    <div class="flex items-center gap-2.5 max-w-[220px]">
                      <?= render_icon_badge($ord['service_icon'] ?: $ord['category_icon'] ?: 'sparkles', 'w-7 h-7', 'p-1') ?>
                      <span class="truncate text-xs font-medium text-white"><?= e($ord['service_name']) ?></span>
                    </div>
                  </td>
                  <td class="py-3 px-3 text-right font-mono-num text-xs text-slate-200"><?= number_format((int) $ord['quantity']) ?></td>
                  <td class="py-3 px-3 whitespace-nowrap"><?= order_status_badge($ord['status']) ?></td>
                  <td class="py-3 pl-3 text-right text-xs text-slate-400 whitespace-nowrap"><?= e(time_ago($ord['created_at'])) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  if (typeof window.initDashboardCharts === 'function') {
    window.initDashboardCharts({
      currencySymbol: <?= json_encode($currencySymbol) ?>,
      seriesLabel: 'Spend',
      palette: (window.ThemeManager && window.ThemeManager.chartPalette) || undefined,
      series: {
        labels: <?= json_encode($seriesLabels) ?>,
        values: <?= json_encode($seriesValues) ?>
      },
      statusBreakdown: {
        completed: <?= (int) $completedOrders ?>,
        processing: <?= (int) $processingCount ?>,
        pending: <?= (int) $strictlyPending ?>,
        cancelled: <?= (int) $cancelledCount ?>
      }
    });
  }
});
</script>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/layouts/dashboard.php';
