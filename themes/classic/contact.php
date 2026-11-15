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
  <div class="bg-white border border-slate-200 rounded-2xl p-6 sm:p-8 shadow-sm">
    <h1 class="text-2xl font-bold text-[#151515]">Contact Support</h1>
    <p class="mt-1 text-sm text-slate-600">Need assistance with an order, payment, or integration? Send us a message below.</p>

    <?php if (!empty($successMsg)): ?>
      <div class="mt-5 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-sm text-[#16A36A] font-medium"><?= e($successMsg) ?></div>
    <?php endif; ?>
    <?php if (!empty($errorMsg)): ?>
      <div class="mt-5 p-4 rounded-xl bg-rose-50 border border-rose-200 text-sm text-rose-700 font-medium"><?= e($errorMsg) ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= e($rp) ?>contact.php" class="mt-6 space-y-4">
      <?= csrf_field() ?>
      <div class="grid sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1.5">Your Name</label>
          <input type="text" name="name" value="<?= e($_POST['name'] ?? ($user['name'] ?? '')) ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-[#7138D8]">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1.5">Email Address</label>
          <input type="email" name="email" value="<?= e($_POST['email'] ?? ($user['email'] ?? '')) ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-[#7138D8]">
        </div>
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Subject</label>
        <input type="text" name="subject" value="<?= e($_POST['subject'] ?? '') ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-[#7138D8]">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Message</label>
        <textarea name="message" rows="5" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-[#7138D8]"><?= e($_POST['message'] ?? '') ?></textarea>
      </div>
      <div class="flex items-center justify-between pt-2">
        <span class="text-xs text-slate-500">Support email: <?= e(get_setting('support_email', 'support@example.com')) ?></span>
        <button type="submit" class="px-6 py-2.5 rounded-xl bg-[#7138D8] text-white text-sm font-semibold hover:bg-[#5B24C0] transition">
          Send Message
        </button>
      </div>
    </form>
  </div>
</div>
<?php
$pageContent = ob_get_clean();
require __DIR__ . ($isLogged ? '/layouts/dashboard.php' : '/layouts/main.php');
