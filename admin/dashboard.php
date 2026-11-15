<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
$admin = require_admin();

$pdo = Database::getConnection();

$totalUsers = 0;
$totalServices = 0;
$totalCategories = 0;
$totalOrders = 0;
$pendingOrders = 0;
$totalRevenue = 0.0;
$totalProfit = 0.0;
$pendingManualPayments = 0;
$salesRows = [];
$recentTransactions = [];
$recentOrders = [];

try {
    $totalUsers = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $totalServices = (int) $pdo->query('SELECT COUNT(*) FROM services')->fetchColumn();
    $totalCategories = (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();

    $orderAgg = $pdo->query(
        'SELECT
            COUNT(*) AS total_orders,
            SUM(CASE WHEN status IN ("pending","processing","in_progress") THEN 1 ELSE 0 END) AS pending_orders,
            SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) AS completed_orders,
            SUM(CASE WHEN status IN ("processing","in_progress") THEN 1 ELSE 0 END) AS processing_orders,
            SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) AS strictly_pending,
            SUM(CASE WHEN status IN ("cancelled","failed","refunded") THEN 1 ELSE 0 END) AS cancelled_orders,
            COALESCE(SUM(CASE WHEN status NOT IN ("cancelled","failed","refunded") THEN charge ELSE 0 END), 0) AS total_revenue,
            COALESCE(SUM(CASE WHEN status NOT IN ("cancelled","failed","refunded") THEN profit ELSE 0 END), 0) AS total_profit
         FROM orders'
    )->fetch() ?: [];

    $totalOrders = (int) ($orderAgg['total_orders'] ?? 0);
    $pendingOrders = (int) ($orderAgg['pending_orders'] ?? 0);
    $totalRevenue = (float) ($orderAgg['total_revenue'] ?? 0);
    $totalProfit = (float) ($orderAgg['total_profit'] ?? 0);
    $pendingManualPayments = (int) $pdo->query('SELECT COUNT(*) FROM payments WHERE method_code = "manual" AND status = "pending"')->fetchColumn();

    $salesRows = $pdo->query(
        'SELECT DATE(created_at) AS d, COALESCE(SUM(charge), 0) AS rev
         FROM orders
         WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND status NOT IN ("cancelled","failed","refunded")
         GROUP BY DATE(created_at)'
    )->fetchAll();

    $recentTransactions = $pdo->query(
        'SELECT wt.*, u.name AS user_name, u.email AS user_email
         FROM wallet_transactions wt
         INNER JOIN users u ON u.id = wt.user_id
         ORDER BY wt.created_at DESC, wt.id DESC
         LIMIT 6'
    )->fetchAll();

    $recentOrders = $pdo->query(
        'SELECT o.*, u.username, s.name AS service_name
         FROM orders o
         INNER JOIN users u ON u.id = o.user_id
         INNER JOIN services s ON s.id = o.service_id
         ORDER BY o.created_at DESC
         LIMIT 6'
    )->fetchAll();
} catch (Throwable $e) {
    write_app_log('app', 'Admin dashboard query warning: ' . $e->getMessage());
}

$byDay = [];
foreach ($salesRows as $r) {
    $byDay[$r['d']] = (float) $r['rev'];
}
$labels = [];
$values = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days"));
    $labels[] = date('D', strtotime($d));
    $values[] = round($byDay[$d] ?? 0.0, 2);
}

$currencySymbol = get_setting('currency_symbol', '₹');
$pageTitle = 'Admin Dashboard';
require __DIR__ . '/../includes/admin-header.php';
?>
<div class="space-y-6">
  <!-- OTP Marketplace Management Banner -->
  <div class="rounded-2xl bg-gradient-to-r from-purple-900 via-indigo-900 to-purple-950 p-6 text-white flex items-center justify-between gap-6 shadow-xl border border-purple-800/40">
    <div class="space-y-1">
      <span class="px-2.5 py-0.5 rounded-full bg-purple-500/20 text-purple-300 text-xs font-bold uppercase tracking-wider">Independent Module</span>
      <h2 class="text-xl font-extrabold tracking-tight">OTP Marketplace Integration</h2>
      <p class="text-xs text-purple-200">Manage API settings, sync SMS verification services, configure pricing markup and monitor OTP orders.</p>
    </div>
    <a href="otp-dashboard.php" class="px-6 py-3 rounded-xl bg-white text-purple-950 font-extrabold text-xs hover:bg-purple-50 transition shadow-md whitespace-nowrap shrink-0">
      MANAGE OTP MARKETPLACE
    </a>
  </div>

  <!-- KPI Row 1 -->
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="glass-kpi-card rounded-2xl p-5 flex items-center gap-4">
      <div class="w-12 h-12 rounded-2xl bg-purple-50 text-[#7138D8] flex items-center justify-center shrink-0">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      </div>
      <div>
        <p class="text-xs font-medium text-slate-500">Total Users</p>
        <p class="text-2xl font-bold text-[#151515] font-mono-num"><?= number_format($totalUsers) ?></p>
      </div>
    </div>

    <div class="glass-kpi-card rounded-2xl p-5 flex items-center gap-4">
      <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-[#16A36A] flex items-center justify-center shrink-0">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
      </div>
      <div>
        <p class="text-xs font-medium text-slate-500">Total Orders</p>
        <p class="text-2xl font-bold text-[#151515] font-mono-num"><?= number_format($totalOrders) ?></p>
      </div>
    </div>

    <div class="glass-kpi-card rounded-2xl p-5 flex items-center gap-4">
      <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-[#16A36A] flex items-center justify-center shrink-0">
        <span class="text-lg font-bold"><?= e($currencySymbol) ?></span>
      </div>
      <div class="min-w-0">
        <p class="text-xs font-medium text-slate-500">Total Revenue</p>
        <p class="text-2xl font-bold text-[#151515] font-mono-num truncate"><?= e(format_currency($totalRevenue)) ?></p>
      </div>
    </div>

    <div class="glass-kpi-card rounded-2xl p-5 flex items-center gap-4">
      <div class="w-12 h-12 rounded-2xl bg-purple-50 text-[#7138D8] flex items-center justify-center shrink-0">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
      </div>
      <div class="min-w-0">
        <p class="text-xs font-medium text-slate-500">Total Profit</p>
        <p class="text-2xl font-bold text-[#16A36A] font-mono-num truncate"><?= e(format_currency($totalProfit)) ?></p>
      </div>
    </div>
  </div>

  <!-- Charts & Tables -->
  <div class="grid lg:grid-cols-12 gap-6">
    <div class="lg:col-span-8 bg-white border border-slate-200 rounded-2xl p-6">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-base font-bold text-[#151515]">7-Day Revenue Analytics</h2>
        <span class="text-xs text-slate-500">Live Completed Orders</span>
      </div>
      <div class="h-72">
        <canvas id="adminRevenueChart"></canvas>
      </div>
    </div>

    <div class="lg:col-span-4 bg-white border border-slate-200 rounded-2xl p-6 flex flex-col justify-between">
      <div>
        <h2 class="text-base font-bold text-[#151515] mb-4">Quick Statistics</h2>
        <div class="space-y-4 text-sm">
          <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50">
            <span class="text-slate-600 font-medium">Pending Orders</span>
            <span class="font-bold text-amber-600 font-mono-num"><?= number_format($pendingOrders) ?></span>
          </div>
          <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50">
            <span class="text-slate-600 font-medium">Manual Payments Pending</span>
            <span class="font-bold text-[#7138D8] font-mono-num"><?= number_format($pendingManualPayments) ?></span>
          </div>
          <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50">
            <span class="text-slate-600 font-medium">Total Services</span>
            <span class="font-bold text-[#151515] font-mono-num"><?= number_format($totalServices) ?></span>
          </div>
        </div>
      </div>
      <div class="pt-4">
        <a href="orders.php?status=pending" class="block w-full py-3 text-center rounded-xl bg-[#7138D8] text-white text-xs font-semibold hover:bg-[#5B24C0] transition shadow-sm">
          Review Pending Orders
        </a>
      </div>
    </div>
  </div>

  <!-- Recent Transactions & Orders -->
  <div class="grid lg:grid-cols-12 gap-6">
    <div class="lg:col-span-6 bg-white border border-slate-200 rounded-2xl p-6">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-base font-bold text-[#151515]">Recent Orders</h2>
        <a href="orders.php" class="text-xs font-semibold text-[#7138D8] hover:underline">View All &rarr;</a>
      </div>
      <?php if (empty($recentOrders)): ?>
        <p class="text-sm text-slate-500 py-6 text-center">No orders found.</p>
      <?php else: ?>
        <div class="divide-y divide-slate-100 text-xs">
          <?php foreach ($recentOrders as $ro): ?>
            <div class="py-3 flex items-center justify-between gap-3">
              <div class="min-w-0">
                <p class="font-semibold text-[#151515]">#<?= e($ro['order_code']) ?> — <?= e($ro['username']) ?></p>
                <p class="text-slate-500 truncate mt-0.5"><?= e($ro['service_name']) ?></p>
              </div>
              <div class="text-right shrink-0">
                <p class="font-mono-num font-bold text-[#151515]"><?= e(format_currency($ro['charge'])) ?></p>
                <p class="mt-0.5"><?= order_status_badge($ro['status']) ?></p>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="lg:col-span-6 bg-white border border-slate-200 rounded-2xl p-6">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-base font-bold text-[#151515]">Recent Transactions</h2>
        <a href="transactions.php" class="text-xs font-semibold text-[#7138D8] hover:underline">View All &rarr;</a>
      </div>
      <?php if (empty($recentTransactions)): ?>
        <p class="text-sm text-slate-500 py-6 text-center">No wallet transactions recorded yet.</p>
      <?php else: ?>
        <div class="divide-y divide-slate-100 text-xs">
          <?php foreach ($recentTransactions as $tx): ?>
            <?php $pos = in_array($tx['type'], ['credit', 'refund'], true); ?>
            <div class="py-3 flex items-center justify-between gap-3">
              <div class="min-w-0">
                <p class="font-semibold text-[#151515]"><?= e($tx['user_name']) ?></p>
                <p class="text-slate-500 truncate mt-0.5"><?= e($tx['description']) ?></p>
              </div>
              <div class="text-right shrink-0">
                <p class="font-mono-num font-bold <?= $pos ? 'text-[#16A36A]' : 'text-rose-600' ?>">
                  <?= $pos ? '+' : '-' ?><?= e(format_currency($tx['amount'])) ?>
                </p>
                <p class="text-slate-400 mt-0.5"><?= e(time_ago($tx['created_at'])) ?></p>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  window.initDashboardCharts({
    currencySymbol: <?= json_encode($currencySymbol) ?>,
    seriesLabel: 'Revenue',
    series: {
      labels: <?= json_encode($labels) ?>,
      values: <?= json_encode($values) ?>
    },
    statusBreakdown: {
      completed: <?= (int) ($orderAgg['completed_orders'] ?? 0) ?>,
      processing: <?= (int) ($orderAgg['processing_orders'] ?? 0) ?>,
      pending: <?= (int) ($orderAgg['strictly_pending'] ?? 0) ?>,
      cancelled: <?= (int) ($orderAgg['cancelled_orders'] ?? 0) ?>
    }
  });
});
</script>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
