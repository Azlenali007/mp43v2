<?php
declare(strict_types=1);
if (!defined('APP_ROOT')) {
    http_response_code(403);
    exit('Forbidden');
}
$pageTitle = $pageTitle ?? 'Create Account';
ob_start();
?>
<div class="aurora-surface rounded-2xl p-6 sm:p-8">
  <h1 class="text-2xl font-bold text-white">Create Your Account</h1>
  <p class="mt-1 text-sm text-slate-400">Launch automated social campaigns in seconds.</p>

  <?php if (empty($registrationEnabled)): ?>
    <div class="mt-4 p-4 rounded-xl bg-amber-950/60 border border-amber-500/40 text-sm text-amber-200">
      Registrations are temporarily disabled by the administrator.
    </div>
  <?php else: ?>
    <?php if (!empty($error)): ?>
      <div class="mt-4 p-3 rounded-xl bg-rose-950/70 border border-rose-500/40 text-sm text-rose-200 font-medium"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= e($rp) ?>register.php" class="mt-6 space-y-4">
      <?= csrf_field() ?>
      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Full Name</label>
        <input type="text" name="name" value="<?= e($_POST['name'] ?? '') ?>" required class="w-full px-3.5 py-2.5 rounded-xl aurora-input border text-sm transition">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Username</label>
        <input type="text" name="username" value="<?= e($_POST['username'] ?? '') ?>" pattern="[A-Za-z0-9_]{3,40}" required class="w-full px-3.5 py-2.5 rounded-xl aurora-input border text-sm transition">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Email Address</label>
        <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required class="w-full px-3.5 py-2.5 rounded-xl aurora-input border text-sm transition">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Phone (Optional)</label>
        <input type="text" name="phone" value="<?= e($_POST['phone'] ?? '') ?>" class="w-full px-3.5 py-2.5 rounded-xl aurora-input border text-sm transition">
      </div>
      <div class="grid sm:grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1.5">Password</label>
          <input type="password" name="password" minlength="8" required class="w-full px-3.5 py-2.5 rounded-xl aurora-input border text-sm transition">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1.5">Confirm</label>
          <input type="password" name="password_confirm" minlength="8" required class="w-full px-3.5 py-2.5 rounded-xl aurora-input border text-sm transition">
        </div>
      </div>
      <p class="text-xs text-slate-400">
        By registering, you agree to our <a href="<?= e($rp) ?>terms.php" class="text-teal-300 hover:underline">Terms</a> and <a href="<?= e($rp) ?>privacy.php" class="text-teal-300 hover:underline">Privacy Policy</a>.
      </p>
      <button type="submit" class="w-full py-2.5 rounded-xl aurora-btn-primary text-sm font-semibold transition">
        Create Account
      </button>
    </form>
  <?php endif; ?>

  <p class="mt-6 text-center text-sm text-slate-400">
    Already registered? <a href="<?= e($rp) ?>login.php" class="text-teal-300 font-semibold hover:underline">Sign in</a>
  </p>
</div>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/layouts/auth.php';
