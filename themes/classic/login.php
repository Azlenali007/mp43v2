<?php
declare(strict_types=1);
if (!defined('APP_ROOT')) {
    http_response_code(403);
    exit('Forbidden');
}
$pageTitle = $pageTitle ?? 'Sign In';
ob_start();
?>
<div class="bg-white border border-slate-200 rounded-2xl p-6 sm:p-8 shadow-sm">
  <h1 class="text-2xl font-bold text-[#151515]">Sign In to Your Account</h1>
  <p class="mt-1 text-sm text-slate-600">Access your orders, wallet balance, and services.</p>

  <?php if (!empty($error)): ?>
    <div class="mt-4 p-3 rounded-xl bg-rose-50 border border-rose-200 text-sm text-rose-700 font-medium"><?= e($error) ?></div>
  <?php endif; ?>

  <form method="POST" action="<?= e($rp) ?>login.php" class="mt-6 space-y-4">
    <?= csrf_field() ?>
    <div>
      <label class="block text-xs font-semibold text-slate-700 mb-1.5">Email or Username</label>
      <input type="text" name="identifier" value="<?= e($oldIdentifier ?? ($_POST['identifier'] ?? '')) ?>" required autofocus class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-[#7138D8]">
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
    Don't have an account? <a href="<?= e($rp) ?>register.php" class="text-[#7138D8] font-semibold hover:underline">Create one</a>
  </p>
</div>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/layouts/auth.php';
