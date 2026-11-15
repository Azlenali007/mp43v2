<?php
declare(strict_types=1);
if (!defined('APP_ROOT')) {
    http_response_code(403);
    exit('Forbidden');
}
$pageTitle = $pageTitle ?? 'Sign In';
ob_start();
?>
<div class="aurora-surface rounded-2xl p-6 sm:p-8">
  <h1 class="text-2xl font-bold text-white">Sign In to Aurora</h1>
  <p class="mt-1 text-sm text-slate-400">Access your command dashboard, wallet balance, and live orders.</p>

  <?php if (!empty($error)): ?>
    <div class="mt-4 p-3 rounded-xl bg-rose-950/70 border border-rose-500/40 text-sm text-rose-200 font-medium"><?= e($error) ?></div>
  <?php endif; ?>

  <form method="POST" action="<?= e($rp) ?>login.php" class="mt-6 space-y-4">
    <?= csrf_field() ?>
    <div>
      <label class="block text-xs font-semibold text-slate-300 mb-1.5">Email or Username</label>
      <input type="text" name="identifier" value="<?= e($oldIdentifier ?? ($_POST['identifier'] ?? '')) ?>" required autofocus class="w-full px-3.5 py-2.5 rounded-xl aurora-input border text-sm transition">
    </div>
    <div>
      <label class="block text-xs font-semibold text-slate-300 mb-1.5">Password</label>
      <input type="password" name="password" required class="w-full px-3.5 py-2.5 rounded-xl aurora-input border text-sm transition">
    </div>
    <button type="submit" class="w-full py-2.5 rounded-xl aurora-btn-primary text-sm font-semibold transition">
      Sign In
    </button>
  </form>

  <p class="mt-6 text-center text-sm text-slate-400">
    New here? <a href="<?= e($rp) ?>register.php" class="text-teal-300 font-semibold hover:underline">Create an account</a>
  </p>
</div>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/layouts/auth.php';
