<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$user = require_user();
require_once __DIR__ . '/classes/User.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();
    $action = $_POST['action'] ?? '';
    if ($action === 'update_profile') {
        $name = sanitize_string($_POST['name'] ?? '', 120);
        $phone = sanitize_string($_POST['phone'] ?? '', 30);
        $tz = sanitize_string($_POST['timezone'] ?? 'Asia/Kolkata', 64);
        $prefCur = strtoupper(sanitize_string($_POST['preferred_currency'] ?? '', 10));
        if ($prefCur !== '') {
            $curRow = Currency::find($prefCur);
            if (!$curRow || (int) $curRow['is_enabled'] !== 1 || (float) ($curRow['exchange_rate'] ?? 0) <= 0) {
                $prefCur = null;
            }
        } else {
            $prefCur = null;
        }
        if ($name === '') {
            set_flash('error', 'Name is required.');
        } else {
            User::updateProfile((int) $user['id'], $name, $phone, $tz, $prefCur);
            current_user(true);
            set_flash('success', 'Profile settings saved.');
        }
        redirect('profile.php');
    } elseif ($action === 'change_password') {
        $current = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');
        if (strlen($new) < 8) {
            set_flash('error', 'New password must be at least 8 characters.');
        } elseif ($new !== $confirm) {
            set_flash('error', 'New passwords do not match.');
        } else {
            $res = User::changePassword((int) $user['id'], $current, $new);
            set_flash($res['ok'] ? 'success' : 'error', $res['ok'] ? 'Password updated successfully.' : $res['error']);
        }
        redirect('profile.php');
    }
}

$readyCurrencies = Currency::all(true, true);
$activeDisplayCur = Currency::getUserDisplay($user);
$pageTitle = 'Account Settings';
require __DIR__ . '/includes/user-header.php';
?>
<div class="max-w-4xl grid md:grid-cols-2 gap-6 items-start">
  <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-4">
    <h1 class="text-lg font-bold text-[#151515]">Profile Settings</h1>
    <form method="POST" action="profile.php" class="space-y-4">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="update_profile">
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Full Name</label>
        <input type="text" name="name" value="<?= e($user['name']) ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-[#7138D8]">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Username</label>
        <input type="text" value="<?= e($user['username']) ?>" disabled class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-500">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Email</label>
        <input type="email" value="<?= e($user['email']) ?>" disabled class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-500">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Phone</label>
        <input type="text" name="phone" value="<?= e($user['phone'] ?? '') ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-[#7138D8]">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Timezone</label>
        <input type="text" name="timezone" value="<?= e($user['timezone']) ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-[#7138D8]">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Preferred Display Currency</label>
        <select name="preferred_currency" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm bg-white focus:outline-none focus:border-[#7138D8]">
          <?php foreach ($readyCurrencies as $rc): ?>
            <option value="<?= e($rc['code']) ?>" <?= $activeDisplayCur['code'] === $rc['code'] ? 'selected' : '' ?>>
              <?= e($rc['code']) ?> — <?= e($rc['name']) ?> (<?= e($rc['symbol']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="px-5 py-2.5 rounded-xl bg-[#7138D8] text-white text-sm font-semibold hover:bg-[#5B24C0]">Save Changes</button>
    </form>
  </div>

  <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-4">
    <h2 class="text-lg font-bold text-[#151515]">Change Password</h2>
    <form method="POST" action="profile.php" class="space-y-4">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="change_password">
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Current Password</label>
        <input type="password" name="current_password" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">New Password (min 8 chars)</label>
        <input type="password" name="new_password" minlength="8" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Confirm New Password</label>
        <input type="password" name="confirm_password" minlength="8" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
      </div>
      <button type="submit" class="px-5 py-2.5 rounded-xl bg-[#151515] text-white text-sm font-semibold hover:bg-black">Update Password</button>
    </form>
  </div>
</div>
<?php require __DIR__ . '/includes/user-footer.php'; ?>
