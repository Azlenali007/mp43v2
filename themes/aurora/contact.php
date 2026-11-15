<?php
declare(strict_types=1);
if (!defined('APP_ROOT')) {
    http_response_code(403);
    exit('Forbidden');
}
$pageTitle = $pageTitle ?? 'Contact Us';
$isLogged = !empty($user);
ob_start();
?>
<div class="<?= $isLogged ? 'max-w-3xl' : 'max-w-[780px] mx-auto px-4 sm:px-6 lg:px-8 py-12' ?>">
  <div class="aurora-surface rounded-2xl p-6 sm:p-8">
    <h1 class="text-2xl font-bold text-white">Contact Support</h1>
    <p class="mt-1 text-sm text-slate-400">Need assistance with an order, payment, or integration? Send us a message below.</p>

    <?php if (!empty($successMsg)): ?>
      <div class="mt-5 p-4 rounded-xl bg-teal-950/70 border border-teal-500/40 text-sm text-teal-200 font-medium"><?= e($successMsg) ?></div>
    <?php endif; ?>
    <?php if (!empty($errorMsg)): ?>
      <div class="mt-5 p-4 rounded-xl bg-rose-950/70 border border-rose-500/40 text-sm text-rose-200 font-medium"><?= e($errorMsg) ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= e($rp) ?>contact.php" class="mt-6 space-y-4">
      <?= csrf_field() ?>
      <div class="grid sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1.5">Your Name</label>
          <input type="text" name="name" value="<?= e($_POST['name'] ?? ($user['name'] ?? '')) ?>" required class="w-full px-3.5 py-2.5 rounded-xl aurora-input border text-sm">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1.5">Email Address</label>
          <input type="email" name="email" value="<?= e($_POST['email'] ?? ($user['email'] ?? '')) ?>" required class="w-full px-3.5 py-2.5 rounded-xl aurora-input border text-sm">
        </div>
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Subject</label>
        <input type="text" name="subject" value="<?= e($_POST['subject'] ?? '') ?>" required class="w-full px-3.5 py-2.5 rounded-xl aurora-input border text-sm">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Message</label>
        <textarea name="message" rows="5" required class="w-full px-3.5 py-2.5 rounded-xl aurora-input border text-sm"><?= e($_POST['message'] ?? '') ?></textarea>
      </div>
      <div class="flex items-center justify-between pt-2">
        <span class="text-xs text-slate-400">Support email: <?= e(get_setting('support_email', 'support@example.com')) ?></span>
        <button type="submit" class="px-6 py-2.5 rounded-xl aurora-btn-primary text-sm font-semibold transition">
          Send Message
        </button>
      </div>
    </form>
  </div>
</div>
<?php
$pageContent = ob_get_clean();
require __DIR__ . ($isLogged ? '/layouts/dashboard.php' : '/layouts/main.php');
