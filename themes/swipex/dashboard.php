<?php
declare(strict_types=1);
if (!defined('APP_ROOT')) {
    http_response_code(403);
    exit('Forbidden');
}
$pageTitle = $pageTitle ?? 'Dashboard';
$user = $user ?? $loggedUser ?? [];
$userId = (int) ($user['id'] ?? 0);
$userName = (string) ($user['name'] ?? 'User');
$initials = $initials ?? 'U';

ob_start();
?>
<div class="space-y-6">
  <!-- Welcome Section -->
  <div class="flex items-center gap-4 bg-white rounded-3xl p-6 shadow-sm border border-slate-200/80">
    <div class="w-14 h-14 rounded-full bg-gradient-to-tr from-blue-600 to-indigo-600 text-white font-extrabold text-lg flex items-center justify-center shadow-md shadow-blue-500/25 shrink-0">
      <?= e($initials) ?>
    </div>
    <div>
      <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Welcome back,</p>
      <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight"><?= e($userName) ?></h1>
      <p class="text-xs text-slate-500 font-mono-num mt-0.5">User ID: #<?= e((string) $userId) ?></p>
    </div>
  </div>

  <!-- Wallet Balance Card Component -->
  <?php require __DIR__ . '/components/balance-card.php'; ?>

  <!-- Main Swipeable Card Dashboard Carousel Component -->
  <div class="space-y-3">
    <div class="flex items-center justify-between px-2">
      <h2 class="text-base font-extrabold text-slate-900">Quick Access Hub</h2>
      <span class="text-xs text-slate-500 font-medium">Swipe cards &rarr;</span>
    </div>
    <?php require __DIR__ . '/components/swipe-carousel.php'; ?>
  </div>

  <!-- Recent Orders Section -->
  <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200/80 space-y-4">
    <div class="flex items-center justify-between">
      <h3 class="text-base font-extrabold text-slate-900">Recent Orders</h3>
      <a href="<?= e($rp) ?>orders.php" class="text-xs font-bold text-blue-600 hover:underline">View All &rarr;</a>
    </div>

    <?php if (empty($recentOrders)): ?>
      <div class="py-8 text-center border border-dashed border-slate-200 rounded-2xl">
        <p class="text-xs font-medium text-slate-600">No orders placed yet.</p>
        <a href="<?= e($rp) ?>new-order.php" class="mt-3 inline-flex px-4 py-2 rounded-xl bg-blue-600 text-white text-xs font-bold shadow-sm hover:bg-blue-700">Place New Order</a>
      </div>
    <?php else: ?>
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-100 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
              <th class="py-2.5 pr-3">Order ID</th>
              <th class="py-2.5 px-3">Service</th>
              <th class="py-2.5 px-3 text-right">Qty</th>
              <th class="py-2.5 px-3">Status</th>
              <th class="py-2.5 pl-3 text-right">Time</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-xs">
            <?php foreach ($recentOrders as $ord): ?>
              <tr class="hover:bg-slate-50/80">
                <td class="py-3 pr-3 font-mono-num font-bold">
                  <a href="<?= e($rp) ?>order-details.php?id=<?= (int) $ord['id'] ?>" class="text-blue-600 hover:underline">#<?= e($ord['order_code']) ?></a>
                </td>
                <td class="py-3 px-3">
                  <span class="font-semibold text-slate-800 line-clamp-1"><?= e($ord['service_name']) ?></span>
                </td>
                <td class="py-3 px-3 text-right font-mono-num font-semibold text-slate-700"><?= number_format((int) $ord['quantity']) ?></td>
                <td class="py-3 px-3 whitespace-nowrap"><?= order_status_badge($ord['status']) ?></td>
                <td class="py-3 pl-3 text-right text-slate-400 whitespace-nowrap"><?= e(time_ago($ord['created_at'])) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/layouts/dashboard.php';
