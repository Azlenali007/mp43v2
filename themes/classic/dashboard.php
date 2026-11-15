<?php
declare(strict_types=1);
if (!defined('APP_ROOT')) {
    http_response_code(403);
    exit('Forbidden');
}
$pageTitle = $pageTitle ?? 'Dashboard';
ob_start();
?>
<div class="space-y-6">
  <!-- Welcome Hero Banner -->
  <div class="hero-banner-gradient rounded-2xl p-6 sm:p-8 text-white flex flex-col lg:flex-row lg:items-center justify-between gap-6 shadow-sm">
    <div class="space-y-2 max-w-xl">
      <h1 class="text-2xl sm:text-3xl font-bold tracking-tight">Welcome Back, <?= e($user['name']) ?>!</h1>
      <p class="text-sm text-purple-100/90">Launch new social media campaigns, track live delivery progress, and manage your wallet balance.</p>
      <div class="pt-2 flex flex-wrap items-center gap-3">
        <a href="<?= e($rp) ?>new-order.php" class="px-5 py-2.5 rounded-xl bg-white text-[#151515] text-xs font-bold hover:bg-purple-50 transition">+ New Order</a>
        <a href="<?= e($rp) ?>add-funds.php" class="px-5 py-2.5 rounded-xl bg-white/15 border border-white/20 text-white text-xs font-semibold hover:bg-white/25 transition">Add Funds</a>
      </div>
    </div>

    <div class="hidden sm:flex items-center gap-3 shrink-0">
      <?php foreach (['instagram', 'youtube', 'telegram', 'facebook', 'tiktok'] as $pKey): ?>
        <div class="w-11 h-11 rounded-2xl bg-white/10 border border-white/15 p-2 flex items-center justify-center backdrop-blur-sm">
          <?= render_icon_badge($pKey, 'w-8 h-8', 'p-1.5') ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- NEED OTP? CLICK HERE Promo Card -->
  <div class="rounded-2xl bg-gradient-to-r from-purple-900 via-indigo-900 to-purple-950 p-6 text-white flex flex-col sm:flex-row items-center justify-between gap-6 shadow-lg border border-purple-800/30">
    <div class="space-y-1">
      <span class="px-2.5 py-0.5 rounded-full bg-purple-500/20 text-purple-300 text-xs font-bold uppercase tracking-wider">SMS Verification Marketplace</span>
      <h2 class="text-lg font-extrabold tracking-tight">Looking for phone numbers & SMS verification?</h2>
      <p class="text-xs text-purple-200">Access our independent OTP marketplace using your existing SMM wallet and login.</p>
    </div>
    <a href="<?= e($rp) ?>user/otp-dashboard.php" class="px-6 py-3 rounded-xl bg-white text-purple-950 font-extrabold text-xs hover:bg-purple-50 transition shadow-md whitespace-nowrap shrink-0 flex items-center gap-2">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="w-4 h-4"><path d="M12 5v14M5 12h14"/></svg>
      NEED OTP? CLICK HERE
    </a>
  </div>

  <!-- 4 Glass KPI Cards -->
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="glass-kpi-card rounded-2xl p-5 flex items-center gap-4">
      <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-[#16A36A] flex items-center justify-center shrink-0">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
      </div>
      <div class="min-w-0">
        <p class="text-xs font-medium text-slate-500">Wallet Balance</p>
        <p class="text-xl sm:text-2xl font-bold text-[#151515] font-mono-num truncate"><?= e(format_currency($user['wallet_balance'])) ?></p>
      </div>
    </div>

    <div class="glass-kpi-card rounded-2xl p-5 flex items-center gap-4">
      <div class="w-12 h-12 rounded-2xl bg-purple-50 text-[#7138D8] flex items-center justify-center shrink-0">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
      </div>
      <div class="min-w-0">
        <p class="text-xs font-medium text-slate-500">Total Orders</p>
        <p class="text-xl sm:text-2xl font-bold text-[#151515] font-mono-num"><?= number_format((int) $totalOrders) ?></p>
      </div>
    </div>

    <div class="glass-kpi-card rounded-2xl p-5 flex items-center gap-4">
      <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-[#16A36A] flex items-center justify-center shrink-0">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6"><polyline points="20 6 9 17 4 12"/></svg>
      </div>
      <div class="min-w-0">
        <p class="text-xs font-medium text-slate-500">Completed</p>
        <p class="text-xl sm:text-2xl font-bold text-[#151515] font-mono-num"><?= number_format((int) $completedOrders) ?></p>
      </div>
    </div>

    <div class="glass-kpi-card rounded-2xl p-5 flex items-center gap-4">
      <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
      </div>
      <div class="min-w-0">
        <p class="text-xs font-medium text-slate-500">Active / Pending</p>
        <p class="text-xl sm:text-2xl font-bold text-[#151515] font-mono-num"><?= number_format((int) $pendingOrders) ?></p>
      </div>
    </div>
  </div>

  <!-- Charts Row -->
  <div class="grid lg:grid-cols-12 gap-6">
    <div class="lg:col-span-7 bg-white border border-slate-200 rounded-2xl p-5 sm:p-6">
      <div class="flex items-center justify-between gap-4 mb-4">
        <h2 class="text-base font-bold text-[#151515]">Order Spend Overview</h2>
        <form method="GET" action="<?= e($rp) ?>dashboard.php">
          <select name="range" onchange="this.form.submit()" class="px-3 py-1.5 rounded-xl border border-slate-200 text-xs font-medium text-slate-700 bg-white focus:outline-none focus:border-[#7138D8]">
            <option value="7d" <?= $range === '7d' ? 'selected' : '' ?>>Last 7 Days</option>
            <option value="30d" <?= $range === '30d' ? 'selected' : '' ?>>Last 30 Days</option>
          </select>
        </form>
      </div>
      <div class="h-64">
        <canvas id="salesOverviewChart"></canvas>
      </div>
    </div>

    <div class="lg:col-span-5 bg-white border border-slate-200 rounded-2xl p-5 sm:p-6 flex flex-col justify-between">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-base font-bold text-[#151515]">Order Status</h2>
        <a href="<?= e($rp) ?>orders.php" class="text-xs font-semibold text-[#7138D8] hover:underline">View All &rarr;</a>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-center my-auto">
        <div class="relative h-48 flex items-center justify-center">
          <canvas id="orderStatusChart"></canvas>
          <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
            <span class="text-2xl font-bold text-[#151515] font-mono-num"><?= number_format((int) $totalOrders) ?></span>
            <span class="text-[11px] text-slate-500">Total Orders</span>
          </div>
        </div>

        <div class="space-y-3 text-xs">
          <?php
          $pct = fn(int $n): string => $totalOrders > 0 ? number_format(($n / $totalOrders) * 100, 1) . '%' : '0.0%';
          ?>
          <div class="flex items-center justify-between">
            <span class="text-slate-700 font-medium">Completed</span>
            <span class="font-mono-num font-semibold text-[#16A36A]"><?= (int) $completedOrders ?> (<?= $pct((int) $completedOrders) ?>)</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-slate-700 font-medium">Processing</span>
            <span class="font-mono-num font-semibold text-[#7138D8]"><?= (int) $processingCount ?> (<?= $pct((int) $processingCount) ?>)</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-slate-700 font-medium">Pending</span>
            <span class="font-mono-num font-semibold text-amber-600"><?= (int) $strictlyPending ?> (<?= $pct((int) $strictlyPending) ?>)</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-slate-700 font-medium">Cancelled / Refunded</span>
            <span class="font-mono-num font-semibold text-rose-600"><?= (int) $cancelledCount ?> (<?= $pct((int) $cancelledCount) ?>)</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Bottom Row: Recent Orders + Latest Announcements -->
  <div class="grid lg:grid-cols-12 gap-6">
    <div class="lg:col-span-7 bg-white border border-slate-200 rounded-2xl p-5 sm:p-6">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-base font-bold text-[#151515]">Recent Orders</h2>
        <a href="<?= e($rp) ?>orders.php" class="text-xs font-semibold text-[#7138D8] hover:underline">View All &rarr;</a>
      </div>

      <?php if (empty($recentOrders)): ?>
        <div class="py-10 text-center border border-dashed border-slate-200 rounded-xl">
          <p class="text-sm font-medium text-slate-700">No orders placed yet</p>
          <p class="text-xs text-slate-500 mt-1">Your submitted campaigns will appear here with real-time status.</p>
          <a href="<?= e($rp) ?>new-order.php" class="mt-4 inline-flex px-4 py-2 rounded-xl bg-[#7138D8] text-white text-xs font-semibold hover:bg-[#5B24C0]">Place Your First Order</a>
        </div>
      <?php else: ?>
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b border-slate-200 text-xs font-semibold text-slate-500">
                <th class="py-2.5 pr-3">Order ID</th>
                <th class="py-2.5 px-3">Service</th>
                <th class="py-2.5 px-3 text-right">Qty</th>
                <th class="py-2.5 px-3">Status</th>
                <th class="py-2.5 pl-3 text-right">Time</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
              <?php foreach ($recentOrders as $ord): ?>
                <tr class="hover:bg-slate-50/70">
                  <td class="py-3 pr-3 font-mono-num text-xs font-semibold">
                    <a href="<?= e($rp) ?>order-details.php?id=<?= (int) $ord['id'] ?>" class="text-[#7138D8] hover:underline">#<?= e($ord['order_code']) ?></a>
                  </td>
                  <td class="py-3 px-3">
                    <div class="flex items-center gap-2.5 max-w-[220px]">
                      <?= render_icon_badge($ord['service_icon'] ?: $ord['category_icon'] ?: 'sparkles', 'w-7 h-7', 'p-1') ?>
                      <span class="truncate text-xs font-medium text-[#151515]"><?= e($ord['service_name']) ?></span>
                    </div>
                  </td>
                  <td class="py-3 px-3 text-right font-mono-num text-xs"><?= number_format((int) $ord['quantity']) ?></td>
                  <td class="py-3 px-3 whitespace-nowrap"><?= order_status_badge($ord['status']) ?></td>
                  <td class="py-3 pl-3 text-right text-xs text-slate-500 whitespace-nowrap"><?= e(time_ago($ord['created_at'])) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <div class="lg:col-span-5 bg-white border border-slate-200 rounded-2xl p-5 sm:p-6">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-base font-bold text-[#151515]">Latest Announcements</h2>
      </div>
      <?php if (empty($announcements)): ?>
        <div class="py-10 text-center border border-dashed border-slate-200 rounded-xl">
          <p class="text-sm font-medium text-slate-700">No announcements right now</p>
          <p class="text-xs text-slate-500 mt-1">Platform updates and service notices from administrators appear here.</p>
        </div>
      <?php else: ?>
        <div class="divide-y divide-slate-100">
          <?php foreach ($announcements as $ann): ?>
            <div class="py-3.5 first:pt-0 last:pb-0">
              <div class="flex items-center justify-between gap-2">
                <h3 class="text-sm font-semibold text-[#151515]"><?= e($ann['title']) ?></h3>
                <span class="text-[11px] text-slate-400 shrink-0"><?= e(date('d M', strtotime($ann['created_at']))) ?></span>
              </div>
              <p class="text-xs text-slate-600 mt-1 leading-relaxed"><?= e($ann['content']) ?></p>
            </div>
          <?php endforeach; ?>
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
