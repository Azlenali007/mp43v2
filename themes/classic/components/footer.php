<?php
declare(strict_types=1);
if (!defined('APP_ROOT')) {
    http_response_code(403);
    exit('Forbidden');
}
$rp = $rootPrefix ?? app_root_prefix();
?>
<footer class="bg-[#151515] text-slate-300 border-t border-white/10 mt-16">
  <div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="grid md:grid-cols-4 gap-8">
      <div class="md:col-span-2">
        <div class="flex items-center gap-2.5">
          <span class="w-8 h-8 rounded-xl bg-[#7138D8] flex items-center justify-center text-white">
            <svg viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4"><path d="M5 16L3 5l5.5 5L12 4l3.5 6L21 5l-2 11H5zm14 3c0 .6-.4 1-1 1H6c-.6 0-1-.4-1-1v-1h14v1z"/></svg>
          </span>
          <span class="text-base font-bold text-white"><?= e($siteName) ?></span>
        </div>
        <p class="mt-3 text-sm text-slate-400 max-w-md leading-relaxed">
          <?= e(get_setting('site_tagline', 'Automated Social Media Marketing Panel')) ?>. Real-time order tracking, transparent pricing, and instant wallet top-ups.
        </p>
      </div>

      <div>
        <h3 class="text-sm font-semibold text-white mb-3">Platform</h3>
        <ul class="space-y-2 text-sm text-slate-400">
          <li><a href="<?= e($rp) ?>services.php" class="hover:text-white transition">Services</a></li>
          <li><a href="<?= e($rp) ?>faq.php" class="hover:text-white transition">FAQ</a></li>
          <li><a href="<?= e($rp) ?>contact.php" class="hover:text-white transition">Contact Support</a></li>
        </ul>
      </div>

      <div>
        <h3 class="text-sm font-semibold text-white mb-3">Legal</h3>
        <ul class="space-y-2 text-sm text-slate-400">
          <li><a href="<?= e($rp) ?>terms.php" class="hover:text-white transition">Terms &amp; Conditions</a></li>
          <li><a href="<?= e($rp) ?>privacy.php" class="hover:text-white transition">Privacy Policy</a></li>
        </ul>
      </div>
    </div>

    <div class="mt-10 pt-6 border-t border-white/10 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500">
      <p>&copy; <?= date('Y') ?> <?= e($siteName) ?>. All rights reserved.</p>
      <p>Support: <?= e(get_setting('support_email', 'support@example.com')) ?></p>
    </div>
  </div>
</footer>
