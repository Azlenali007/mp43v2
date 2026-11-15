<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
ensure_installed();

require_once __DIR__ . '/classes/Auth.php';

if (current_user()) {
    redirect('dashboard.php');
}

$registrationEnabled = get_setting('registration_enabled', '1') === '1';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();
    if (!$registrationEnabled) {
        $error = 'New user registrations are currently closed.';
    } elseif (!check_rate_limit('user_register', 8, 600)) {
        $error = 'Too many registration attempts. Please try again later.';
    } else {
        $name = sanitize_string($_POST['name'] ?? '', 120);
        $username = sanitize_string($_POST['username'] ?? '', 40);
        $email = sanitize_string($_POST['email'] ?? '', 190);
        $phone = sanitize_string($_POST['phone'] ?? '', 30);
        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['password_confirm'] ?? '');

        if ($name === '' || !is_valid_username($username) || !is_valid_email($email)) {
            $error = 'Provide a valid full name, alphanumeric username (3-40 chars), and email address.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters long.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            $res = Auth::registerUser($name, $username, $email, $password, $phone);
            if ($res['ok']) {
                Auth::loginUser($email, $password);
                set_flash('success', 'Welcome! Your account has been created.');
                redirect('dashboard.php');
            }
            $error = $res['error'];
        }
    }
}

$pageTitle = 'Create Account';
require __DIR__ . '/includes/public-header.php';
?>
<div class="max-w-md mx-auto px-4 py-14">
  <div class="bg-white border border-slate-200 rounded-2xl p-6 sm:p-8 shadow-sm">
    <h1 class="text-2xl font-bold text-[#151515]">Create Your Account</h1>
    <p class="mt-1 text-sm text-slate-600">Start placing and tracking social marketing orders.</p>

    <?php if (!$registrationEnabled): ?>
      <div class="mt-4 p-4 rounded-xl bg-amber-50 border border-amber-200 text-sm text-amber-800">
        Registrations are temporarily disabled by the administrator.
      </div>
    <?php else: ?>
      <?php if ($error): ?>
        <div class="mt-4 p-3 rounded-xl bg-rose-50 border border-rose-200 text-sm text-rose-700 font-medium"><?= e($error) ?></div>
      <?php endif; ?>

      <form method="POST" action="register.php" class="mt-6 space-y-4">
        <?= csrf_field() ?>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1.5">Full Name</label>
          <input type="text" name="name" value="<?= e($_POST['name'] ?? '') ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-[#7138D8]">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1.5">Username</label>
          <input type="text" name="username" value="<?= e($_POST['username'] ?? '') ?>" pattern="[A-Za-z0-9_]{3,40}" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-[#7138D8]">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1.5">Email Address</label>
          <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-[#7138D8]">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1.5">Phone (Optional)</label>
          <input type="text" name="phone" value="<?= e($_POST['phone'] ?? '') ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-[#7138D8]">
        </div>
        <div class="grid sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Password</label>
            <input type="password" name="password" minlength="8" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-[#7138D8]">
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Confirm</label>
            <input type="password" name="password_confirm" minlength="8" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-[#7138D8]">
          </div>
        </div>
        <p class="text-xs text-slate-500">
          By registering, you agree to our <a href="terms.php" class="text-[#7138D8] hover:underline">Terms</a> and <a href="privacy.php" class="text-[#7138D8] hover:underline">Privacy Policy</a>.
        </p>
        <button type="submit" class="w-full py-2.5 rounded-xl bg-[#7138D8] text-white text-sm font-semibold hover:bg-[#5B24C0] transition">
          Create Account
        </button>
      </form>
    <?php endif; ?>

    <p class="mt-6 text-center text-sm text-slate-600">
      Already registered? <a href="login.php" class="text-[#7138D8] font-semibold hover:underline">Sign in</a>
    </p>
  </div>
</div>
<?php require __DIR__ . '/includes/public-footer.php'; ?>
