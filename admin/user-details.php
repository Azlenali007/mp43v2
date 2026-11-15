<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
$admin = require_admin('manage_users');
require_once __DIR__ . '/../classes/Wallet.php';

$userId = validate_positive_int($_GET['id'] ?? null);
if (!$userId) {
    redirect('users.php');
}

$pdo = Database::getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();
    $txType = ($_POST['wallet_action'] ?? '') === 'debit' ? 'debit' : 'credit';
    $amount = validate_positive_float($_POST['amount'] ?? null, 0.01, 1000000.0);
    $note = sanitize_string($_POST['note'] ?? 'Admin wallet adjustment', 200);

    if ($amount === null) {
        set_flash('error', 'Enter a valid positive adjustment amount.');
    } else {
        if ($txType === 'credit') {
            $res = Wallet::credit($userId, $amount, 'admin_adjustment', null, $note, null, (int) $admin['id'], 'credit');
        } else {
            $res = Wallet::debit($userId, $amount, 'admin_adjustment', null, $note, null, (int) $admin['id'], false);
        }
        set_flash($res['ok'] ? 'success' : 'error', $res['ok'] ? 'Wallet balance updated.' : $res['error']);
    }
    redirect('user-details.php?id=' . $userId);
}

$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$userId]);
$u = $stmt->fetch();
if (!$u) {
    redirect('users.php');
}

$ordersStmt = $pdo->prepare('SELECT o.*, s.name AS service_name FROM orders o INNER JOIN services s ON s.id = o.service_id WHERE o.user_id = ? ORDER BY o.created_at DESC LIMIT 20');
$ordersStmt->execute([$userId]);
$userOrders = $ordersStmt->fetchAll();

$txStmt = $pdo->prepare('SELECT * FROM wallet_transactions WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT 20');
$txStmt->execute([$userId]);
$userTxs = $txStmt->fetchAll();

$pageTitle = 'User Details';
require __DIR__ . '/../includes/admin-header.php';
?>
<div class="space-y-6">
  <div class="flex items-center justify-between">
    <div>
      <a href="users.php" class="text-xs font-semibold text-[#7138D8] hover:underline">&larr; Back to Users</a>
      <h2 class="text-xl font-bold text-[#151515] mt-1"><?= e($u['name']) ?> <span class="text-sm font-normal text-slate-500">(@<?= e($u['username']) ?>)</span></h2>
    </div>
    <a href="user-edit.php?id=<?= $userId ?>" class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-xs font-semibold">Edit Account</a>
  </div>

  <div class="grid lg:grid-cols-12 gap-6">
    <!-- User Summary + Wallet Adjustment -->
    <div class="lg:col-span-4 space-y-6">
      <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-3 text-sm">
        <p class="text-xs text-slate-500">Wallet Balance</p>
        <p class="text-2xl font-bold text-[#16A36A] font-mono-num"><?= e(format_currency($u['wallet_balance'])) ?></p>
        <div class="pt-3 border-t border-slate-100 space-y-1.5 text-xs text-slate-600">
          <p><strong>Email:</strong> <?= e($u['email']) ?></p>
          <p><strong>Total Spent:</strong> <span class="font-mono-num"><?= e(format_currency($u['spent_total'])) ?></span></p>
          <p><strong>Status:</strong> <?= e(ucfirst($u['status'])) ?></p>
          <p><strong>Joined:</strong> <?= e(date('d M Y', strtotime($u['created_at']))) ?></p>
        </div>
      </div>

      <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-4">
        <h3 class="text-sm font-bold text-[#151515]">Admin Wallet Credit / Debit</h3>
        <form method="POST" action="user-details.php?id=<?= $userId ?>" class="space-y-3">
          <?= csrf_field() ?>
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Action</label>
            <select name="wallet_action" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm bg-white">
              <option value="credit">Credit Funds (+)</option>
              <option value="debit">Debit Funds (-)</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Amount</label>
            <input type="number" step="0.01" min="0.01" name="amount" required class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm font-mono-num">
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Reason / Note</label>
            <input type="text" name="note" required placeholder="e.g. Promotional adjustment" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm">
          </div>
          <button type="submit" class="w-full py-2.5 rounded-xl bg-[#7138D8] text-white text-xs font-semibold hover:bg-[#5B24C0]">Apply Adjustment</button>
        </form>
      </div>
    </div>

    <!-- Orders & Transactions -->
    <div class="lg:col-span-8 space-y-6">
      <div class="bg-white border border-slate-200 rounded-2xl p-6">
        <h3 class="text-base font-bold text-[#151515] mb-3">Recent User Orders</h3>
        <?php if (empty($userOrders)): ?>
          <p class="text-xs text-slate-500">No orders placed by this user.</p>
        <?php else: ?>
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
              <thead><tr class="border-b border-slate-200 text-slate-500"><th class="py-2 pr-3">Code</th><th class="py-2 px-3">Service</th><th class="py-2 px-3 text-right">Charge</th><th class="py-2 pl-3">Status</th></tr></thead>
              <tbody class="divide-y divide-slate-100">
                <?php foreach ($userOrders as $o): ?>
                  <tr>
                    <td class="py-2.5 pr-3 font-mono-num font-semibold"><a href="order-details.php?id=<?= (int) $o['id'] ?>" class="text-[#7138D8] hover:underline">#<?= e($o['order_code']) ?></a></td>
                    <td class="py-2.5 px-3 truncate max-w-[240px]"><?= e($o['service_name']) ?></td>
                    <td class="py-2.5 px-3 text-right font-mono-num"><?= e(format_currency($o['charge'])) ?></td>
                    <td class="py-2.5 pl-3"><?= order_status_badge($o['status']) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

      <div class="bg-white border border-slate-200 rounded-2xl p-6">
        <h3 class="text-base font-bold text-[#151515] mb-3">Wallet Ledger</h3>
        <?php if (empty($userTxs)): ?>
          <p class="text-xs text-slate-500">No wallet transactions recorded.</p>
        <?php else: ?>
          <div class="divide-y divide-slate-100 text-xs">
            <?php foreach ($userTxs as $t): ?>
              <?php $pos = in_array($t['type'], ['credit', 'refund'], true); ?>
              <div class="py-2.5 flex items-center justify-between gap-3">
                <div>
                  <p class="font-medium text-[#151515]"><?= e($t['description']) ?></p>
                  <p class="text-slate-400"><?= e(date('d M Y, H:i', strtotime($t['created_at']))) ?></p>
                </div>
                <div class="text-right font-mono-num">
                  <p class="font-bold <?= $pos ? 'text-[#16A36A]' : 'text-rose-600' ?>"><?= $pos ? '+' : '-' ?><?= e(format_currency($t['amount'])) ?></p>
                  <p class="text-slate-400">Bal: <?= e(format_currency($t['balance_after'])) ?></p>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
