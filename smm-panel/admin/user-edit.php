<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
$admin = require_admin('manage_users');

$userId = validate_positive_int($_GET['id'] ?? null);
if (!$userId) {
    redirect('users.php');
}

$pdo = Database::getConnection();
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$userId]);
$u = $stmt->fetch();
if (!$u) {
    redirect('users.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();
    $name = sanitize_string($_POST['name'] ?? '', 120);
    $email = sanitize_string($_POST['email'] ?? '', 190);
    $phone = sanitize_string($_POST['phone'] ?? '', 30);
    $status = in_array($_POST['status'] ?? '', ['active', 'blocked', 'pending'], true) ? $_POST['status'] : 'active';
    $newPass = (string) ($_POST['new_password'] ?? '');

    if ($name === '' || !is_valid_email($email)) {
        $error = 'Valid name and email are required.';
    } else {
        $upd = $pdo->prepare('UPDATE users SET name = ?, email = ?, phone = ?, status = ? WHERE id = ?');
        $upd->execute([$name, $email, $phone ?: null, $status, $userId]);
        if ($newPass !== '' && strlen($newPass) >= 8) {
            $hash = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 12]);
            $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$hash, $userId]);
        }
        log_activity('admin', (int) $admin['id'], 'user_updated', 'user', $userId);
        set_flash('success', 'User updated.');
        redirect('user-details.php?id=' . $userId);
    }
}

$pageTitle = 'Edit User';
require __DIR__ . '/../includes/admin-header.php';
?>
<div class="max-w-xl bg-white border border-slate-200 rounded-2xl p-6 space-y-4">
  <a href="user-details.php?id=<?= $userId ?>" class="text-xs font-semibold text-[#7138D8] hover:underline">&larr; Back to User Details</a>
  <h2 class="text-lg font-bold text-[#151515]">Edit User: <?= e($u['name']) ?></h2>
  <?php if ($error): ?>
    <div class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-700 font-medium"><?= e($error) ?></div>
  <?php endif; ?>
  <form method="POST" action="user-edit.php?id=<?= $userId ?>" class="space-y-4">
    <?= csrf_field() ?>
    <div>
      <label class="block text-xs font-semibold text-slate-700 mb-1">Full Name</label>
      <input type="text" name="name" value="<?= e($u['name']) ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
    </div>
    <div>
      <label class="block text-xs font-semibold text-slate-700 mb-1">Email</label>
      <input type="email" name="email" value="<?= e($u['email']) ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
    </div>
    <div class="grid sm:grid-cols-2 gap-4">
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Phone</label>
        <input type="text" name="phone" value="<?= e($u['phone'] ?? '') ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Status</label>
        <select name="status" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm bg-white">
          <option value="active" <?= $u['status'] === 'active' ? 'selected' : '' ?>>Active</option>
          <option value="blocked" <?= $u['status'] === 'blocked' ? 'selected' : '' ?>>Blocked</option>
          <option value="pending" <?= $u['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
        </select>
      </div>
    </div>
    <div>
      <label class="block text-xs font-semibold text-slate-700 mb-1">Reset Password (leave blank to keep current)</label>
      <input type="password" name="new_password" minlength="8" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
    </div>
    <button type="submit" class="px-6 py-2.5 rounded-xl bg-[#7138D8] text-white text-sm font-semibold hover:bg-[#5B24C0]">Save Changes</button>
  </form>
</div>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
