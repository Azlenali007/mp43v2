<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
$admin = require_admin('manage_orders');
require_once __DIR__ . '/../classes/Provider.php';
require_once __DIR__ . '/../classes/Wallet.php';
require_once __DIR__ . '/../classes/Order.php';

$orderId = validate_positive_int($_GET['id'] ?? null);
if (!$orderId) {
    redirect('orders.php');
}

$pdo = Database::getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();
    $action = $_POST['action'] ?? '';
    if ($action === 'update_status') {
        $status = array_key_exists($_POST['status'] ?? '', ORDER_STATUSES) ? $_POST['status'] : 'pending';
        $startCount = max(0, (int) ($_POST['start_count'] ?? 0));
        $remains = max(0, (int) ($_POST['remains'] ?? 0));
        $pdo->prepare('UPDATE orders SET status = ?, start_count = ?, remains = ? WHERE id = ?')->execute([$status, $startCount, $remains, $orderId]);
        Order::addLog($orderId, $status, 'Status manually updated by admin ' . $admin['name']);
        set_flash('success', 'Order status updated.');
    } elseif ($action === 'refund') {
        $amt = validate_positive_float($_POST['refund_amount'] ?? null, 0.01, 1000000.0);
        $reason = sanitize_string($_POST['reason'] ?? 'Admin refund', 200);
        if ($amt === null) {
            set_flash('error', 'Enter a valid refund amount.');
        } else {
            $res = Order::refundOrder($orderId, $amt, $reason, (int) $admin['id']);
            set_flash($res['ok'] ? 'success' : 'error', $res['ok'] ? 'Refunded ' . format_currency($res['refunded']) : $res['error']);
        }
    }
    redirect('order-details.php?id=' . $orderId);
}

$stmt = $pdo->prepare(
    'SELECT o.*, u.name AS user_name, u.username, u.email, s.name AS service_name, p.name AS provider_name
     FROM orders o
     INNER JOIN users u ON u.id = o.user_id
     INNER JOIN services s ON s.id = o.service_id
     LEFT JOIN providers p ON p.id = o.provider_id
     WHERE o.id = ? LIMIT 1'
);
$stmt->execute([$orderId]);
$order = $stmt->fetch();
if (!$order) {
    redirect('orders.php');
}

$logs = $pdo->prepare('SELECT * FROM order_logs WHERE order_id = ? ORDER BY created_at DESC');
$logs->execute([$orderId]);
$orderLogs = $logs->fetchAll();

$pageTitle = 'Order #' . $order['order_code'];
require __DIR__ . '/../includes/admin-header.php';
?>
<div class="space-y-6" x-data="{
  msg: null,
  async dispatchProvider() {
    this.msg = null;
    const r = await window.SMMApp.postJson('../api/provider-order.php', { order_id: <?= $orderId ?> });
    this.msg = r;
    if (r.ok) setTimeout(() => location.reload(), 900);
  },
  async syncProvider() {
    this.msg = null;
    const r = await window.SMMApp.postJson('../api/provider-status.php', { order_id: <?= $orderId ?> });
    this.msg = r;
    if (r.ok) setTimeout(() => location.reload(), 900);
  }
}">
  <div class="flex flex-wrap items-center justify-between gap-4">
    <div>
      <a href="orders.php" class="text-xs font-semibold text-[#7138D8] hover:underline">&larr; Back to Orders</a>
      <h2 class="text-xl font-bold text-[#151515] font-mono-num mt-1">Order #<?= e($order['order_code']) ?></h2>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <?php if (empty($order['provider_order_id'])): ?>
        <button type="button" @click="dispatchProvider" class="px-4 py-2 rounded-xl bg-[#7138D8] text-white text-xs font-semibold">Dispatch to Provider</button>
      <?php else: ?>
        <button type="button" @click="syncProvider" class="px-4 py-2 rounded-xl bg-[#151515] text-white text-xs font-semibold">Sync Provider Status</button>
      <?php endif; ?>
      <?= order_status_badge($order['status']) ?>
    </div>
  </div>

  <template x-if="msg">
    <div :class="msg.ok ? 'bg-emerald-50 border-emerald-200 text-[#16A36A]' : 'bg-rose-50 border-rose-200 text-rose-700'" class="p-3.5 rounded-xl border text-sm font-medium" x-text="msg.message"></div>
  </template>

  <div class="grid lg:grid-cols-12 gap-6 items-start">
    <div class="lg:col-span-7 space-y-6">
      <div class="bg-white border border-slate-200 rounded-2xl p-6 grid sm:grid-cols-2 gap-4 text-sm">
        <div><p class="text-xs text-slate-500">Customer</p><p class="font-semibold"><?= e($order['user_name']) ?> (@<?= e($order['username']) ?>)</p></div>
        <div><p class="text-xs text-slate-500">Service</p><p class="font-semibold"><?= e($order['service_name']) ?></p></div>
        <div class="sm:col-span-2"><p class="text-xs text-slate-500">Link</p><p class="font-mono-num text-xs break-all"><?= e($order['link']) ?></p></div>
        <div><p class="text-xs text-slate-500">Quantity</p><p class="font-mono-num font-semibold"><?= number_format((int) $order['quantity']) ?></p></div>
        <div><p class="text-xs text-slate-500">Provider Order ID</p><p class="font-mono-num font-semibold"><?= e($order['provider_order_id'] ?: 'Not dispatched') ?></p></div>
        <div><p class="text-xs text-slate-500">Charge / Cost / Profit</p><p class="font-mono-num font-semibold"><?= e(format_currency($order['charge'])) ?> / <?= e(format_currency($order['cost'])) ?> / <span class="text-[#16A36A]"><?= e(format_currency($order['profit'])) ?></span></p></div>
        <div><p class="text-xs text-slate-500">Refunded So Far</p><p class="font-mono-num font-semibold text-rose-600"><?= e(format_currency($order['refunded_amount'])) ?></p></div>
      </div>

      <div class="bg-white border border-slate-200 rounded-2xl p-6">
        <h3 class="text-base font-bold text-[#151515] mb-3">Order Logs</h3>
        <div class="space-y-2.5 text-xs">
          <?php foreach ($orderLogs as $l): ?>
            <div class="p-3 rounded-xl bg-[#F8F9FC] border border-slate-200 flex justify-between gap-4">
              <div><strong class="uppercase text-[#7138D8]"><?= e($l['status']) ?></strong>: <?= e($l['message']) ?></div>
              <span class="text-slate-400 whitespace-nowrap"><?= e(date('d M, H:i', strtotime($l['created_at']))) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div class="lg:col-span-5 space-y-6">
      <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-4">
        <h3 class="text-sm font-bold text-[#151515]">Manual Status Override</h3>
        <form method="POST" action="order-details.php?id=<?= $orderId ?>" class="space-y-3">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="update_status">
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Status</label>
            <select name="status" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm bg-white">
              <?php foreach (ORDER_STATUSES as $k => $lbl): ?>
                <option value="<?= e($k) ?>" <?= $order['status'] === $k ? 'selected' : '' ?>><?= e($lbl) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Start Count</label>
              <input type="number" name="start_count" value="<?= (int) $order['start_count'] ?>" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm font-mono-num">
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Remains</label>
              <input type="number" name="remains" value="<?= (int) $order['remains'] ?>" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm font-mono-num">
            </div>
          </div>
          <button type="submit" class="w-full py-2.5 rounded-xl bg-[#151515] text-white text-xs font-semibold">Update Order</button>
        </form>
      </div>

      <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-4">
        <h3 class="text-sm font-bold text-[#151515]">Process Atomic Refund</h3>
        <form method="POST" action="order-details.php?id=<?= $orderId ?>" class="space-y-3">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="refund">
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Refund Amount (Max: <?= e(format_currency(max(0, (float) $order['charge'] - (float) $order['refunded_amount']))) ?>)</label>
            <input type="number" step="0.0001" min="0.01" max="<?= max(0.01, (float) $order['charge'] - (float) $order['refunded_amount']) ?>" name="refund_amount" value="<?= number_format(max(0, (float) $order['charge'] - (float) $order['refunded_amount']), 4, '.', '') ?>" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm font-mono-num">
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Reason</label>
            <input type="text" name="reason" required placeholder="e.g. Service cancelled by provider" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm">
          </div>
          <button type="submit" class="w-full py-2.5 rounded-xl bg-rose-600 text-white text-xs font-semibold hover:bg-rose-700">Issue Wallet Refund</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
