<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
$admin = require_admin('manage_users');

$pdo = Database::getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();
    $action = $_POST['action'] ?? '';
    $uid = validate_positive_int($_POST['user_id'] ?? null);
    if ($uid) {
        if ($action === 'toggle_status') {
            $newStatus = ($_POST['new_status'] ?? '') === 'blocked' ? 'blocked' : 'active';
            $pdo->prepare('UPDATE users SET status = ? WHERE id = ?')->execute([$newStatus, $uid]);
            log_activity('admin', (int) $admin['id'], 'user_status_' . $newStatus, 'user', $uid);
            set_flash('success', 'User status updated.');
        } elseif ($action === 'delete') {
            $cntStmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE user_id = ?');
            $cntStmt->execute([$uid]);
            $hasOrders = (int) $cntStmt->fetchColumn();
            if ($hasOrders > 0) {
                set_flash('error', 'Cannot delete user with existing orders. Block the account instead.');
            } else {
                $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$uid]);
                log_activity('admin', (int) $admin['id'], 'user_deleted', 'user', $uid);
                set_flash('success', 'User deleted.');
            }
        }
    }
    redirect('users.php');
}

$search = sanitize_string($_GET['q'] ?? '', 120);
if ($search !== '') {
    $like = '%' . $search . '%';
    $stmt = $pdo->prepare('SELECT * FROM users WHERE name LIKE ? OR username LIKE ? OR email LIKE ? ORDER BY id DESC LIMIT 100');
    $stmt->execute([$like, $like, $like]);
    $users = $stmt->fetchAll();
} else {
    $users = $pdo->query('SELECT * FROM users ORDER BY id DESC LIMIT 100')->fetchAll();
}

$pageTitle = 'Users Management';
require __DIR__ . '/../includes/admin-header.php';
?>
<div class="space-y-6">
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h2 class="text-xl font-bold text-[#151515]">Users</h2>
      <p class="text-sm text-slate-600">Manage customer accounts, wallet balances, and access status.</p>
    </div>
    <a href="user-add.php" class="px-5 py-2.5 rounded-xl bg-[#7138D8] text-white text-sm font-semibold hover:bg-[#5B24C0] self-start">+ Add User</a>
  </div>

  <form method="GET" action="users.php" class="bg-white border border-slate-200 rounded-2xl p-4 flex gap-3">
    <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search by name, username, or email..." class="flex-1 px-3.5 py-2 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-[#7138D8]">
    <button type="submit" class="px-5 py-2 rounded-xl bg-[#151515] text-white text-xs font-semibold">Search</button>
  </form>

  <?php if (empty($users)): ?>
    <div class="bg-white border border-slate-200 rounded-2xl p-10 text-center text-sm text-slate-500">No users found.</div>
  <?php else: ?>
    <div class="bg-white border border-slate-200 rounded-2xl overflow-x-auto">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="border-b border-slate-200 bg-slate-50/70 text-xs font-semibold text-slate-500">
            <th class="py-3.5 px-4">ID</th>
            <th class="py-3.5 px-4">User</th>
            <th class="py-3.5 px-4 text-right">Wallet Balance</th>
            <th class="py-3.5 px-4 text-right">Total Spent</th>
            <th class="py-3.5 px-4">Status</th>
            <th class="py-3.5 px-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-sm">
          <?php foreach ($users as $u): ?>
            <tr class="hover:bg-slate-50/60">
              <td class="py-3.5 px-4 font-mono-num text-xs text-slate-500">#<?= (int) $u['id'] ?></td>
              <td class="py-3.5 px-4">
                <a href="user-details.php?id=<?= (int) $u['id'] ?>" class="font-semibold text-[#151515] hover:text-[#7138D8]"><?= e($u['name']) ?></a>
                <p class="text-xs text-slate-500">@<?= e($u['username']) ?> · <?= e($u['email']) ?></p>
              </td>
              <td class="py-3.5 px-4 text-right font-mono-num font-bold text-[#16A36A]"><?= e(format_currency($u['wallet_balance'])) ?></td>
              <td class="py-3.5 px-4 text-right font-mono-num text-xs text-slate-600"><?= e(format_currency($u['spent_total'])) ?></td>
              <td class="py-3.5 px-4">
                <span class="px-2.5 py-0.5 rounded-md text-xs font-semibold border <?= $u['status'] === 'active' ? 'bg-emerald-50 text-[#16A36A] border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200' ?>">
                  <?= e(ucfirst($u['status'])) ?>
                </span>
              </td>
              <td class="py-3.5 px-4 text-right whitespace-nowrap space-x-2">
                <a href="user-details.php?id=<?= (int) $u['id'] ?>" class="text-xs font-semibold text-[#7138D8] hover:underline">Details</a>
                <a href="user-edit.php?id=<?= (int) $u['id'] ?>" class="text-xs font-semibold text-slate-700 hover:underline">Edit</a>
                <form method="POST" action="users.php" class="inline">
                  <?= csrf_field() ?>
                  <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                  <input type="hidden" name="action" value="toggle_status">
                  <input type="hidden" name="new_status" value="<?= $u['status'] === 'active' ? 'blocked' : 'active' ?>">
                  <button type="submit" class="text-xs font-semibold <?= $u['status'] === 'active' ? 'text-amber-600' : 'text-[#16A36A]' ?> hover:underline">
                    <?= $u['status'] === 'active' ? 'Block' : 'Unblock' ?>
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
