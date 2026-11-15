<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
$admin = require_admin('manage_orders');

$pdo = Database::getConnection();
$statusFilter = sanitize_string($_GET['status'] ?? '', 30);
$search = sanitize_string($_GET['q'] ?? '', 120);

$conds = [];
$params = [];
if ($statusFilter !== '' && array_key_exists($statusFilter, ORDER_STATUSES)) {
    $conds[] = 'o.status = ?';
    $params[] = $statusFilter;
}
if ($search !== '') {
    $conds[] = '(o.order_code LIKE ? OR o.link LIKE ? OR u.username LIKE ? OR u.email LIKE ?)';
    $like = '%' . $search . '%';
    $params = array_merge($params, [$like, $like, $like, $like]);
}
$where = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';

$stmt = $pdo->prepare(
    "SELECT o.*, u.username, u.email, s.name AS service_name, p.name AS provider_name
     FROM orders o
     INNER JOIN users u ON u.id = o.user_id
     INNER JOIN services s ON s.id = o.service_id
     LEFT JOIN providers p ON p.id = o.provider_id
     $where
     ORDER BY o.created_at DESC
     LIMIT 120"
);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$pageTitle = 'Orders Management';
require __DIR__ . '/../includes/admin-header.php';
?>
<div class="space-y-6">
  <div class="bg-white border border-slate-200 rounded-2xl p-4 space-y-3">
    <div class="flex flex-wrap gap-1.5">
      <a href="orders.php" class="px-3 py-1.5 rounded-lg text-xs font-semibold <?= $statusFilter === '' ? 'bg-[#7138D8] text-white' : 'bg-slate-100 text-slate-600' ?>">All</a>
      <?php foreach (ORDER_STATUSES as $k => $lbl): ?>
        <a href="orders.php?status=<?= e($k) ?>" class="px-3 py-1.5 rounded-lg text-xs font-semibold <?= $statusFilter === $k ? 'bg-[#7138D8] text-white' : 'bg-slate-100 text-slate-600' ?>"><?= e($lbl) ?></a>
      <?php endforeach; ?>
    </div>
    <form method="GET" action="orders.php" class="flex gap-2">
      <?php if ($statusFilter): ?><input type="hidden" name="status" value="<?= e($statusFilter) ?>"><?php endif; ?>
      <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search by order code, user, or link..." class="flex-1 px-3.5 py-2 rounded-xl border border-slate-200 text-sm">
      <button type="submit" class="px-5 py-2 rounded-xl bg-[#151515] text-white text-xs font-semibold">Search</button>
    </form>
  </div>

  <?php if (empty($orders)): ?>
    <div class="bg-white border border-slate-200 rounded-2xl p-10 text-center text-sm text-slate-500">No orders match your filter.</div>
  <?php else: ?>
    <div class="bg-white border border-slate-200 rounded-2xl overflow-x-auto">
      <table class="w-full text-left border-collapse text-sm">
        <thead>
          <tr class="border-b border-slate-200 bg-slate-50/70 text-xs font-semibold text-slate-500">
            <th class="py-3 px-4">Order</th>
            <th class="py-3 px-3">User</th>
            <th class="py-3 px-3">Service</th>
            <th class="py-3 px-3 text-right">Qty</th>
            <th class="py-3 px-3 text-right">Charge</th>
            <th class="py-3 px-3 text-right">Profit</th>
            <th class="py-3 px-3">Provider ID</th>
            <th class="py-3 px-3">Status</th>
            <th class="py-3 px-4 text-right">Date</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-xs">
          <?php foreach ($orders as $o): ?>
            <tr class="hover:bg-slate-50/60">
              <td class="py-3 px-4 font-mono-num font-bold">
                <a href="order-details.php?id=<?= (int) $o['id'] ?>" class="text-[#7138D8] hover:underline">#<?= e($o['order_code']) ?></a>
              </td>
              <td class="py-3 px-3">
                <a href="user-details.php?id=<?= (int) $o['user_id'] ?>" class="font-semibold text-[#151515] hover:underline">@<?= e($o['username']) ?></a>
              </td>
              <td class="py-3 px-3 max-w-[220px] truncate"><?= e($o['service_name']) ?></td>
              <td class="py-3 px-3 text-right font-mono-num"><?= number_format((int) $o['quantity']) ?></td>
              <td class="py-3 px-3 text-right font-mono-num font-semibold"><?= e(format_currency($o['charge'])) ?></td>
              <td class="py-3 px-3 text-right font-mono-num font-semibold text-[#16A36A]"><?= e(format_currency($o['profit'])) ?></td>
              <td class="py-3 px-3 font-mono-num text-slate-500"><?= e($o['provider_order_id'] ?: '—') ?></td>
              <td class="py-3 px-3 whitespace-nowrap"><?= order_status_badge($o['status']) ?></td>
              <td class="py-3 px-4 text-right text-slate-500 whitespace-nowrap"><?= e(date('d M, H:i', strtotime($o['created_at']))) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
