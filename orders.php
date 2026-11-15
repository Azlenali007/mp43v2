<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$user = require_user();

$pdo = Database::getConnection();
$statusFilter = sanitize_string($_GET['status'] ?? '', 30);
$search = sanitize_string($_GET['q'] ?? '', 120);

$conds = ['o.user_id = ?'];
$params = [(int) $user['id']];

if ($statusFilter !== '' && array_key_exists($statusFilter, ORDER_STATUSES)) {
    $conds[] = 'o.status = ?';
    $params[] = $statusFilter;
}
if ($search !== '') {
    $conds[] = '(o.order_code LIKE ? OR o.link LIKE ? OR s.name LIKE ?)';
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$sql = 'SELECT o.*, s.name AS service_name, s.icon_key AS service_icon, c.icon_key AS category_icon
        FROM orders o
        INNER JOIN services s ON s.id = o.service_id
        INNER JOIN categories c ON c.id = s.category_id
        WHERE ' . implode(' AND ', $conds) . '
        ORDER BY o.created_at DESC
        LIMIT 100';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$pageTitle = 'My Orders';
require __DIR__ . '/includes/user-header.php';
?>
<div class="space-y-6">
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h1 class="text-2xl font-bold text-[#151515]">My Orders</h1>
      <p class="text-sm text-slate-600 mt-1">Track live order progress, start counts, and delivery logs.</p>
    </div>
    <a href="new-order.php" class="px-5 py-2.5 rounded-xl bg-[#7138D8] text-white text-sm font-semibold hover:bg-[#5B24C0] transition self-start">+ New Order</a>
  </div>

  <!-- Status Filter Tabs + Search -->
  <div class="bg-white border border-slate-200 rounded-2xl p-4 space-y-3">
    <div class="flex flex-wrap gap-1.5">
      <a href="orders.php" class="px-3 py-1.5 rounded-lg text-xs font-semibold <?= $statusFilter === '' ? 'bg-[#7138D8] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">All</a>
      <?php foreach (ORDER_STATUSES as $key => $label): ?>
        <a href="orders.php?status=<?= e($key) ?>" class="px-3 py-1.5 rounded-lg text-xs font-semibold <?= $statusFilter === $key ? 'bg-[#7138D8] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>"><?= e($label) ?></a>
      <?php endforeach; ?>
    </div>
    <form method="GET" action="orders.php" class="flex gap-2">
      <?php if ($statusFilter): ?><input type="hidden" name="status" value="<?= e($statusFilter) ?>"><?php endif; ?>
      <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search by Order ID, service, or link..." class="flex-1 px-3.5 py-2 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-[#7138D8]">
      <button type="submit" class="px-4 py-2 rounded-xl bg-[#151515] text-white text-xs font-semibold">Search</button>
    </form>
  </div>

  <?php if (empty($orders)): ?>
    <div class="bg-white border border-slate-200 rounded-2xl p-10 text-center">
      <p class="text-base font-semibold text-[#151515]">No Orders Found</p>
      <p class="text-sm text-slate-500 mt-1">Once you place an order, it will appear here with live status tracking.</p>
      <a href="new-order.php" class="mt-4 inline-flex px-5 py-2.5 rounded-xl bg-[#7138D8] text-white text-xs font-semibold">Place Order</a>
    </div>
  <?php else: ?>
    <div class="bg-white border border-slate-200 rounded-2xl overflow-x-auto">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="border-b border-slate-200 bg-slate-50/70 text-xs font-semibold text-slate-500">
            <th class="py-3.5 px-4">Order ID</th>
            <th class="py-3.5 px-4">Service</th>
            <th class="py-3.5 px-4">Link</th>
            <th class="py-3.5 px-4 text-right">Quantity</th>
            <th class="py-3.5 px-4 text-right">Charge</th>
            <th class="py-3.5 px-4 text-right">Remains</th>
            <th class="py-3.5 px-4">Status</th>
            <th class="py-3.5 px-4 text-right">Date</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-sm">
          <?php foreach ($orders as $ord): ?>
            <tr class="hover:bg-slate-50/60">
              <td class="py-3.5 px-4 font-mono-num text-xs font-semibold">
                <a href="order-details.php?id=<?= (int) $ord['id'] ?>" class="text-[#7138D8] hover:underline">#<?= e($ord['order_code']) ?></a>
              </td>
              <td class="py-3.5 px-4">
                <div class="flex items-center gap-2.5 max-w-[240px]">
                  <?= render_icon_badge($ord['service_icon'] ?: $ord['category_icon'] ?: 'sparkles', 'w-7 h-7', 'p-1') ?>
                  <span class="truncate text-xs font-semibold text-[#151515]"><?= e($ord['service_name']) ?></span>
                </div>
              </td>
              <td class="py-3.5 px-4 text-xs text-slate-600 max-w-[200px] truncate"><?= e($ord['link']) ?></td>
              <td class="py-3.5 px-4 text-right font-mono-num text-xs"><?= number_format((int) $ord['quantity']) ?></td>
              <td class="py-3.5 px-4 text-right font-mono-num text-xs">
                <?php if (!empty($ord['display_currency']) && $ord['displayed_charge'] !== null): ?>
                  <span class="font-semibold text-[#151515]"><?= e(format_exact_currency((float) $ord['displayed_charge'], (string) $ord['display_currency'], 4)) ?> <?= e($ord['display_currency']) ?></span>
                  <?php $ordBase = $ord['base_currency'] ?? Currency::getBaseCode(); ?>
                  <?php if (strtoupper((string) $ord['display_currency']) !== strtoupper((string) $ordBase)): ?>
                    <span class="block text-[10px] text-slate-400"><?= e(format_exact_currency((float) $ord['charge'], (string) $ordBase, 4)) ?> <?= e($ordBase) ?></span>
                  <?php endif; ?>
                <?php else: ?>
                  <span class="font-semibold text-[#151515]"><?= e(format_currency($ord['charge'])) ?></span>
                <?php endif; ?>
              </td>
              <td class="py-3.5 px-4 text-right font-mono-num text-xs text-slate-600"><?= number_format((int) $ord['remains']) ?></td>
              <td class="py-3.5 px-4 whitespace-nowrap"><?= order_status_badge($ord['status']) ?></td>
              <td class="py-3.5 px-4 text-right text-xs text-slate-500 whitespace-nowrap"><?= e(date('d M Y, H:i', strtotime($ord['created_at']))) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/user-footer.php'; ?>
