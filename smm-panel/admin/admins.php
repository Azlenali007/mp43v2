<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
$admin = require_admin('all');

$pdo = Database::getConnection();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $name = sanitize_string($_POST['name'] ?? '', 120);
        $email = sanitize_string($_POST['email'] ?? '', 190);
        $roleId = validate_positive_int($_POST['role_id'] ?? null);
        $pass = (string) ($_POST['password'] ?? '');
        if ($name === '' || !is_valid_email($email) || !$roleId || strlen($pass) < 8) {
            $error = 'Provide valid name, email, role, and password (min 8 chars).';
        } else {
            $chk = $pdo->prepare('SELECT id FROM admins WHERE email = ?');
            $chk->execute([$email]);
            if ($chk->fetch()) {
                $error = 'An administrator with that email already exists.';
            } else {
                $hash = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);
                $pdo->prepare('INSERT INTO admins (role_id, name, email, password_hash, status) VALUES (?, ?, ?, ?, "active")')
                    ->execute([$roleId, $name, $email, $hash]);
                log_activity('admin', (int) $admin['id'], 'admin_created', 'admin', null, $email);
                set_flash('success', 'Administrator added.');
                redirect('admins.php');
            }
        }
    } elseif ($action === 'toggle') {
        $aid = validate_positive_int($_POST['admin_id'] ?? null);
        if ($aid && $aid !== (int) $admin['id']) {
            $pdo->prepare('UPDATE admins SET status = IF(status = "active", "disabled", "active") WHERE id = ?')->execute([$aid]);
            set_flash('success', 'Administrator status updated.');
        }
        redirect('admins.php');
    }
}

$roles = $pdo->query('SELECT * FROM admin_roles ORDER BY id ASC')->fetchAll();
$admins = $pdo->query('SELECT a.*, r.name AS role_name FROM admins a INNER JOIN admin_roles r ON r.id = a.role_id ORDER BY a.id ASC')->fetchAll();

$pageTitle = 'Administrators & Roles';
require __DIR__ . '/../includes/admin-header.php';
?>
<div class="grid lg:grid-cols-12 gap-6 items-start">
  <div class="lg:col-span-5 bg-white border border-slate-200 rounded-2xl p-6 space-y-4">
    <h2 class="text-base font-bold text-[#151515]">Add Administrator</h2>
    <?php if ($error): ?>
      <div class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-700"><?= e($error) ?></div>
    <?php endif; ?>
    <form method="POST" action="admins.php" class="space-y-4">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="create">
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Full Name</label>
        <input type="text" name="name" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Email</label>
        <input type="email" name="email" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Role</label>
        <select name="role_id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm bg-white">
          <?php foreach ($roles as $r): ?>
            <option value="<?= (int) $r['id'] ?>"><?= e($r['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Password (min 8 chars)</label>
        <input type="password" name="password" minlength="8" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
      </div>
      <button type="submit" class="px-5 py-2.5 rounded-xl bg-[#7138D8] text-white text-sm font-semibold">Create Admin</button>
    </form>
  </div>

  <div class="lg:col-span-7 bg-white border border-slate-200 rounded-2xl p-6">
    <h2 class="text-base font-bold text-[#151515] mb-4">Authorized Administrators</h2>
    <div class="divide-y divide-slate-100 text-sm">
      <?php foreach ($admins as $a): ?>
        <div class="py-3.5 flex items-center justify-between gap-3">
          <div>
            <p class="font-semibold text-[#151515]"><?= e($a['name']) ?> <span class="text-xs font-normal text-[#7138D8]">(<?= e($a['role_name']) ?>)</span></p>
            <p class="text-xs text-slate-500"><?= e($a['email']) ?></p>
          </div>
          <div class="flex items-center gap-3 text-xs">
            <span class="px-2 py-0.5 rounded font-semibold <?= $a['status'] === 'active' ? 'bg-emerald-50 text-[#16A36A]' : 'bg-slate-100 text-slate-600' ?>"><?= e(ucfirst($a['status'])) ?></span>
            <?php if ((int) $a['id'] !== (int) $admin['id']): ?>
              <form method="POST" action="admins.php" class="inline">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="admin_id" value="<?= (int) $a['id'] ?>">
                <button type="submit" class="font-semibold text-slate-700 hover:underline"><?= $a['status'] === 'active' ? 'Disable' : 'Enable' ?></button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
