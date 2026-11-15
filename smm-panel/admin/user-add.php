<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
$admin = require_admin('manage_users');
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Wallet.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();
    $name = sanitize_string($_POST['name'] ?? '', 120);
    $username = sanitize_string($_POST['username'] ?? '', 40);
    $email = sanitize_string($_POST['email'] ?? '', 190);
    $phone = sanitize_string($_POST['phone'] ?? '', 30);
    $password = (string) ($_POST['password'] ?? '');
    $initialBalance = (float) ($_POST['initial_balance'] ?? 0);

    if ($name === '' || !is_valid_username($username) || !is_valid_email($email) || strlen($password) < 8) {
        $error = 'Provide valid name, username (3-40 alphanumeric), email, and password (min 8 chars).';
    } else {
        $res = Auth::registerUser($name, $username, $email, $password, $phone);
        if (!$res['ok']) {
            $error = $res['error'];
        } else {
            if ($initialBalance > 0) {
                Wallet::credit((int) $res['user_id'], $initialBalance, 'admin_adjustment', null, 'Initial balance added by administrator', null, (int) $admin['id']);
            }
            set_flash('success', 'User account created.');
            redirect('user-details.php?id=' . $res['user_id']);
        }
    }
}

$pageTitle = 'Add User';
require __DIR__ . '/../includes/admin-header.php';
?>
<div class="max-w-xl bg-white border border-slate-200 rounded-2xl p-6 space-y-4">
  <a href="users.php" class="text-xs font-semibold text-[#7138D8] hover:underline">&larr; Back to Users</a>
  <h2 class="text-lg font-bold text-[#151515]">Create New User</h2>
  <?php if ($error): ?>
    <div class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-700 font-medium"><?= e($error) ?></div>
  <?php endif; ?>
  <form method="POST" action="user-add.php" class="space-y-4">
    <?= csrf_field() ?>
    <div>
      <label class="block text-xs font-semibold text-slate-700 mb-1">Full Name</label>
      <input type="text" name="name" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
    </div>
    <div class="grid sm:grid-cols-2 gap-4">
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Username</label>
        <input type="text" name="username" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Phone</label>
        <input type="text" name="phone" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
      </div>
    </div>
    <div>
      <label class="block text-xs font-semibold text-slate-700 mb-1">Email</label>
      <input type="email" name="email" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
    </div>
    <div class="grid sm:grid-cols-2 gap-4">
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Password</label>
        <input type="password" name="password" minlength="8" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Opening Balance (Optional)</label>
        <input type="number" step="0.01" min="0" name="initial_balance" value="0" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-mono-num">
      </div>
    </div>
    <button type="submit" class="px-6 py-2.5 rounded-xl bg-[#7138D8] text-white text-sm font-semibold hover:bg-[#5B24C0]">Create User</button>
  </form>
</div>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
