<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
ensure_installed();

require_once __DIR__ . '/classes/Auth.php';

if (current_user()) {
    redirect('dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();
    if (!check_rate_limit('user_login', 12, 300)) {
        $error = 'Too many login attempts. Please wait a few minutes before trying again.';
    } else {
        $identifier = sanitize_string($_POST['identifier'] ?? '', 190);
        $password = (string) ($_POST['password'] ?? '');
        if ($identifier === '' || $password === '') {
            $error = 'Please enter your email/username and password.';
        } else {
            $res = Auth::loginUser($identifier, $password);
            if ($res['ok']) {
                redirect('dashboard.php');
            }
            $error = $res['error'];
        }
    }
}

$pageTitle = 'Sign In';
require __DIR__ . '/includes/public-header.php';
?>
<div class="max-w-md mx-auto px-4 py-16">
  <div class="bg-white border border-slate-200 rounded-2xl p-6 sm:p-8 shadow-sm">
    <h1 class="text-2xl font-bold text-[#151515]">Sign In to Your Account</h1>
    <p class="mt-1 text-sm text-slate-600">Access your orders, wallet balance, and services.</p>

    <?php if ($error): ?>
      <div class="mt-4 p-3 rounded-xl bg-rose-50 border border-rose-200 text-sm text-rose-700 font-medium"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php" class="mt-6 space-y-4">
      <?= csrf_field() ?>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Email or Username</label>
        <input type="text" name="identifier" value="<?= e($_POST['identifier'] ?? '') ?>" required autofocus class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-[#7138D8]">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Password</label>
        <input type="password" name="password" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-[#7138D8]">
      </div>
      <button type="submit" class="w-full py-2.5 rounded-xl bg-[#7138D8] text-white text-sm font-semibold hover:bg-[#5B24C0] transition">
        Sign In
      </button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-600">
      Don't have an account? <a href="register.php" class="text-[#7138D8] font-semibold hover:underline">Create one</a>
    </p>
  </div>
</div>
<?php require __DIR__ . '/includes/public-footer.php'; ?>
