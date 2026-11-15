<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
$admin = require_admin('view_reports');

$pdo = Database::getConnection();
$days = (int) ($_GET['days'] ?? 30);
if (!in_array($days, [7, 30, 90], true)) {
    $days = 30;
}

$summary = $pdo->prepare(
    'SELECT
        COUNT(*) AS orders_count,
        COALESCE(SUM(charge), 0) AS gross_revenue,
        COALESCE(SUM(cost), 0) AS total_cost,
        COALESCE(SUM(profit), 0) AS net_profit
     FROM orders
     WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
       AND status NOT IN ("cancelled", "failed", "refunded")'
);
$summary->execute([$days]);
$sum = $summary->fetch() ?: [];

$depositsStmt = $pdo->prepare('SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = "paid" AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)');
$depositsStmt->execute([$days]);
$totalDeposits = (float) $depositsStmt->fetchColumn();

$topServicesStmt = $pdo->prepare(
    'SELECT s.id, s.name, COUNT(o.id) AS orders_count, SUM(o.charge) AS revenue, SUM(o.profit) AS profit
     FROM orders o
     INNER JOIN services s ON s.id = o.service_id
     WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) AND o.status NOT IN ("cancelled","failed","refunded")
     GROUP BY s.id
     ORDER BY revenue DESC
     LIMIT 10'
);
$topServicesStmt->execute([$days]);
$topServices = $topServicesStmt->fetchAll();

$pageTitle = 'Financial Reports';
require __DIR__ . '/../includes/admin-header.php';
?>
<div class="space-y-6">
  <div class="flex items-center justify-between">
    <h2 class="text-xl font-bold text-[#151515]">Financial &amp; Performance Reports</h2>
    <div class="flex gap-2">
      <?php foreach ([7, 30, 90] as $d): ?>
        <a href="reports.php?days=<?= $d ?>" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold <?= $days === $d ? 'bg-[#7138D8] text-white' : 'bg-white border border-slate-200 text-slate-700' ?>">Last <?= $d ?> Days</a>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="bg-white border border-slate-200 rounded-2xl p-5">
      <p class="text-xs text-slate-500">Order Volume</p>
      <p class="text-2xl font-bold text-[#151515] font-mono-num mt-1"><?= number_format((int) ($sum['orders_count'] ?? 0)) ?></p>
    </div>
    <div class="bg-white border border-slate-200 rounded-2xl p-5">
      <p class="text-xs text-slate-500">Gross Revenue</p>
      <p class="text-2xl font-bold text-[#151515] font-mono-num mt-1"><?= e(format_currency($sum['gross_revenue'] ?? 0)) ?></p>
    </div>
    <div class="bg-white border border-slate-200 rounded-2xl p-5">
      <p class="text-xs text-slate-500">Net Profit</p>
      <p class="text-2xl font-bold text-[#16A36A] font-mono-num mt-1"><?= e(format_currency($sum['net_profit'] ?? 0)) ?></p>
    </div>
    <div class="bg-white border border-slate-200 rounded-2xl p-5">
      <p class="text-xs text-slate-500">Verified Deposits</p>
      <p class="text-2xl font-bold text-[#7138D8] font-mono-num mt-1"><?= e(format_currency($totalDeposits)) ?></p>
    </div>
  </div>

  <div class="bg-white border border-slate-200 rounded-2xl p-6">
    <h3 class="text-base font-bold text-[#151515] mb-4">Top Services by Revenue (Last <?= $days ?> Days)</h3>
    <?php if (empty($topServices)): ?>
      <p class="text-sm text-slate-500">No completed or active revenue recorded in this window.</p>
    <?php else: ?>
      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm border-collapse">
          <thead><tr class="border-b border-slate-200 text-xs text-slate-500"><th class="py-2.5 pr-4">Service</th><th class="py-2.5 px-4 text-right">Orders</th><th class="py-2.5 px-4 text-right">Revenue</th><th class="py-2.5 pl-4 text-right">Profit</th></tr></thead>
          <tbody class="divide-y divide-slate-100 text-xs">
            <?php foreach ($topServices as $ts): ?>
              <tr>
                <td class="py-3 pr-4 font-semibold text-[#151515]">#<?= (int) $ts['id'] ?> · <?= e($ts['name']) ?></td>
                <td class="py-3 px-4 text-right font-mono-num"><?= number_format((int) $ts['orders_count']) ?></td>
                <td class="py-3 px-4 text-right font-mono-num font-semibold"><?= e(format_currency($ts['revenue'])) ?></td>
                <td class="py-3 pl-4 text-right font-mono-num font-bold text-[#16A36A]"><?= e(format_currency($ts['profit'])) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
