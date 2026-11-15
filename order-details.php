<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$user = require_user();

$orderId = validate_positive_int($_GET['id'] ?? null);
if (!$orderId) {
    redirect('orders.php');
}

$pdo = Database::getConnection();
$stmt = $pdo->prepare(
    'SELECT o.*, s.name AS service_name, s.icon_key AS service_icon, c.name AS category_name
     FROM orders o
     INNER JOIN services s ON s.id = o.service_id
     INNER JOIN categories c ON c.id = s.category_id
     WHERE o.id = ? AND o.user_id = ? LIMIT 1'
);
$stmt->execute([$orderId, (int) $user['id']]);
$order = $stmt->fetch();
if (!$order) {
    set_flash('error', 'Order not found.');
    redirect('orders.php');
}

$logsStmt = $pdo->prepare('SELECT * FROM order_logs WHERE order_id = ? ORDER BY created_at DESC');
$logsStmt->execute([$orderId]);
$logs = $logsStmt->fetchAll();

$pageTitle = 'Order #' . $order['order_code'];
require __DIR__ . '/includes/user-header.php';
?>
<div class="max-w-4xl space-y-6">
  <div class="flex items-center justify-between gap-4">
    <div>
      <a href="orders.php" class="text-xs font-semibold text-[#7138D8] hover:underline">&larr; Back to Orders</a>
      <h1 class="text-2xl font-bold text-[#151515] mt-1 font-mono-num">Order #<?= e($order['order_code']) ?></h1>
    </div>
    <?= order_status_badge($order['status']) ?>
  </div>

  <div class="bg-white border border-slate-200 rounded-2xl p-6 grid sm:grid-cols-2 lg:grid-cols-3 gap-5 text-sm">
    <div>
      <p class="text-xs text-slate-500">Service</p>
      <p class="font-semibold text-[#151515] mt-0.5"><?= e($order['service_name']) ?></p>
    </div>
    <div>
      <p class="text-xs text-slate-500">Category</p>
      <p class="font-medium text-slate-700 mt-0.5"><?= e($order['category_name']) ?></p>
    </div>
    <div>
      <p class="text-xs text-slate-500">Charge</p>
      <p class="font-bold text-[#16A36A] font-mono-num mt-0.5"><?= e(format_currency($order['charge'])) ?></p>
    </div>
    <div class="sm:col-span-2 lg:col-span-3">
      <p class="text-xs text-slate-500">Target Link</p>
      <p class="font-mono-num text-xs text-[#151515] break-all mt-0.5"><?= e($order['link']) ?></p>
    </div>
    <div>
      <p class="text-xs text-slate-500">Quantity</p>
      <p class="font-semibold font-mono-num mt-0.5"><?= number_format((int) $order['quantity']) ?></p>
    </div>
    <div>
      <p class="text-xs text-slate-500">Start Count</p>
      <p class="font-semibold font-mono-num mt-0.5"><?= number_format((int) $order['start_count']) ?></p>
    </div>
    <div>
      <p class="text-xs text-slate-500">Remaining</p>
      <p class="font-semibold font-mono-num mt-0.5"><?= number_format((int) $order['remains']) ?></p>
    </div>
    <?php if ((float) $order['refunded_amount'] > 0): ?>
      <div>
        <p class="text-xs text-slate-500">Refunded Amount</p>
        <p class="font-semibold text-[#16A36A] font-mono-num mt-0.5"><?= e(format_currency($order['refunded_amount'])) ?></p>
      </div>
    <?php endif; ?>
  </div>

  <div class="bg-white border border-slate-200 rounded-2xl p-6">
    <h2 class="text-base font-bold text-[#151515] mb-4">Order Activity Timeline</h2>
    <?php if (empty($logs)): ?>
      <p class="text-sm text-slate-500">No status logs recorded yet.</p>
    <?php else: ?>
      <div class="space-y-3">
        <?php foreach ($logs as $log): ?>
          <div class="p-3.5 rounded-xl bg-[#F8F9FC] border border-slate-200 flex items-start justify-between gap-4">
            <div>
              <span class="text-xs font-semibold uppercase text-[#7138D8]"><?= e($log['status']) ?></span>
              <p class="text-sm text-[#151515] mt-0.5"><?= e($log['message']) ?></p>
            </div>
            <span class="text-xs text-slate-500 whitespace-nowrap"><?= e(date('d M Y, H:i', strtotime($log['created_at']))) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/includes/user-footer.php'; ?>
