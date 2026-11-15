<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
$admin = require_admin('manage_payments');
require_once __DIR__ . '/../includes/payment-functions.php';

$pdo = Database::getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();
    $paymentId = validate_positive_int($_POST['payment_id'] ?? null);
    $action = $_POST['action'] ?? '';
    $note = sanitize_string($_POST['admin_note'] ?? '', 300);

    if ($paymentId) {
        $stmt = $pdo->prepare('SELECT * FROM payments WHERE id = ? LIMIT 1');
        $stmt->execute([$paymentId]);
        $pay = $stmt->fetch();

        if ($pay && $pay['method_code'] === 'manual' && $pay['status'] === 'pending') {
            if ($action === 'approve') {
                $res = Payment::creditVerifiedPayment($paymentId, null, null, (int) $admin['id'], $note ?: 'Approved by administrator');
                set_flash($res['ok'] ? 'success' : 'error', $res['ok'] ? 'Manual payment approved and wallet credited.' : $res['error']);
                log_activity('admin', (int) $admin['id'], 'manual_payment_approved', 'payment', $paymentId);
            } elseif ($action === 'reject') {
                $pdo->prepare('UPDATE payments SET status = "rejected", admin_note = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ? AND status = "pending" AND credited = 0')
                    ->execute([$note ?: 'Payment verification rejected', (int) $admin['id'], $paymentId]);
                create_notification((int) $pay['user_id'], 'Payment Rejected', "Manual payment #{$pay['payment_code']} was rejected: " . ($note ?: 'Verification failed'), 'error', 'add-funds.php');
                log_activity('admin', (int) $admin['id'], 'manual_payment_rejected', 'payment', $paymentId);
                set_flash('warning', 'Manual payment request rejected.');
            }
        }
    }
    redirect('payments.php');
}

$statusFilter = sanitize_string($_GET['status'] ?? '', 30);
$where = ($statusFilter !== '' && array_key_exists($statusFilter, PAYMENT_STATUSES)) ? 'WHERE p.status = ?' : '';
$stmt = $pdo->prepare(
    "SELECT p.*, u.name AS user_name, u.username, u.email
     FROM payments p
     INNER JOIN users u ON u.id = p.user_id
     $where
     ORDER BY FIELD(p.status, 'pending') DESC, p.created_at DESC
     LIMIT 120"
);
$stmt->execute($where ? [$statusFilter] : []);
$payments = $stmt->fetchAll();

$pageTitle = 'Payments & Approvals';
require __DIR__ . '/../includes/admin-header.php';
?>
<div class="space-y-6">
  <div class="flex flex-wrap items-center justify-between gap-4">
    <div class="flex flex-wrap gap-1.5">
      <a href="payments.php" class="px-3 py-1.5 rounded-lg text-xs font-semibold <?= $statusFilter === '' ? 'bg-[#7138D8] text-white' : 'bg-white border border-slate-200 text-slate-600' ?>">All</a>
      <?php foreach (PAYMENT_STATUSES as $k => $lbl): ?>
        <a href="payments.php?status=<?= e($k) ?>" class="px-3 py-1.5 rounded-lg text-xs font-semibold <?= $statusFilter === $k ? 'bg-[#7138D8] text-white' : 'bg-white border border-slate-200 text-slate-600' ?>"><?= e($lbl) ?></a>
      <?php endforeach; ?>
    </div>
    <a href="payment-methods.php" class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-xs font-semibold">Configure Payment Methods</a>
  </div>

  <?php if (empty($payments)): ?>
    <div class="bg-white border border-slate-200 rounded-2xl p-10 text-center text-sm text-slate-500">No payments recorded yet.</div>
  <?php else: ?>
    <div class="bg-white border border-slate-200 rounded-2xl overflow-x-auto">
      <table class="w-full text-left border-collapse text-sm">
        <thead>
          <tr class="border-b border-slate-200 bg-slate-50/70 text-xs font-semibold text-slate-500">
            <th class="py-3 px-4">Ref</th>
            <th class="py-3 px-3">User</th>
            <th class="py-3 px-3">Method</th>
            <th class="py-3 px-3">Transaction / UTR</th>
            <th class="py-3 px-3 text-right">Amount</th>
            <th class="py-3 px-3">Status</th>
            <th class="py-3 px-4 text-right">Review</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-xs">
          <?php foreach ($payments as $p): ?>
            <tr>
              <td class="py-3 px-4 font-mono-num font-bold">#<?= e($p['payment_code']) ?></td>
              <td class="py-3 px-3">
                <a href="user-details.php?id=<?= (int) $p['user_id'] ?>" class="font-semibold text-[#151515] hover:underline"><?= e($p['user_name']) ?></a>
                <p class="text-slate-400">@<?= e($p['username']) ?></p>
              </td>
              <td class="py-3 px-3 uppercase font-semibold"><?= e($p['method_code']) ?></td>
              <td class="py-3 px-3 font-mono-num">
                <?= e($p['razorpay_payment_id'] ?: ($p['utr_reference'] ?: '—')) ?>
                <?php if (!empty($p['screenshot_path'])): ?>
                  <a href="../<?= e($p['screenshot_path']) ?>" target="_blank" class="block text-[#7138D8] font-sans font-semibold hover:underline mt-0.5">View Screenshot</a>
                <?php endif; ?>
              </td>
              <td class="py-3 px-3 text-right font-mono-num font-bold text-[#16A36A]"><?= e(format_currency($p['amount'])) ?></td>
              <td class="py-3 px-3"><?= payment_status_badge($p['status']) ?></td>
              <td class="py-3 px-4 text-right">
                <?php if ($p['method_code'] === 'manual' && $p['status'] === 'pending'): ?>
                  <form method="POST" action="payments.php" class="inline-flex items-center gap-1.5">
                    <?= csrf_field() ?>
                    <input type="hidden" name="payment_id" value="<?= (int) $p['id'] ?>">
                    <input type="text" name="admin_note" placeholder="Optional note" class="w-28 px-2 py-1 rounded-lg border border-slate-200 text-xs">
                    <button type="submit" name="action" value="approve" class="px-2.5 py-1 rounded-lg bg-[#16A36A] text-white font-semibold">Approve</button>
                    <button type="submit" name="action" value="reject" class="px-2.5 py-1 rounded-lg bg-rose-600 text-white font-semibold">Reject</button>
                  </form>
                <?php else: ?>
                  <span class="text-slate-400"><?= e($p['admin_note'] ?: date('d M, H:i', strtotime($p['created_at']))) ?></span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
