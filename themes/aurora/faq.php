<?php
declare(strict_types=1);
if (!defined('APP_ROOT')) {
    http_response_code(403);
    exit('Forbidden');
}
$pageTitle = $pageTitle ?? 'Frequently Asked Questions';
$isLogged = !empty($user);
ob_start();
?>
<div class="<?= $isLogged ? 'max-w-4xl' : 'max-w-[960px] mx-auto px-4 sm:px-6 lg:px-8 py-12' ?>">
  <div class="mb-8">
    <h1 class="text-2xl sm:text-3xl font-bold text-white">Frequently Asked Questions</h1>
    <p class="mt-2 text-sm text-slate-400">Clear answers on ordering, wallet funding, refunds, and API usage.</p>
  </div>

  <div class="space-y-3" x-data="{ openIndex: 0 }">
    <?php foreach ($faqs as $idx => [$q, $a]): ?>
      <div class="aurora-surface rounded-2xl overflow-hidden">
        <button type="button" @click="openIndex = openIndex === <?= (int) $idx ?> ? -1 : <?= (int) $idx ?>" class="w-full px-5 py-4 text-left flex items-center justify-between gap-4 hover:bg-white/[0.02] transition">
          <span class="text-sm sm:text-base font-semibold text-white"><?= e($q) ?></span>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4 text-teal-300 shrink-0 transition-transform" :class="openIndex === <?= (int) $idx ?> ? 'rotate-180' : ''"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div x-show="openIndex === <?= (int) $idx ?>" x-cloak class="px-5 pb-4 text-sm text-slate-300 leading-relaxed border-t border-slate-800 pt-3">
          <?= e($a) ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php
$pageContent = ob_get_clean();
require __DIR__ . ($isLogged ? '/layouts/dashboard.php' : '/layouts/main.php');
