<?php
declare(strict_types=1);
if (!defined('APP_ROOT')) {
    http_response_code(403);
    exit('Forbidden');
}
$rp = $rootPrefix ?? app_root_prefix();
$navLinks = [
    ['href' => $rp . 'index.php', 'label' => 'Home'],
    ['href' => $rp . 'services.php', 'label' => 'Services'],
    ['href' => $rp . 'faq.php', 'label' => 'FAQ'],
    ['href' => $rp . 'contact.php', 'label' => 'Contact'],
];
$loggedUser = $loggedUser ?? current_user();
$enabledCurrencies = $enabledCurrencies ?? Currency::all(true, false);
$displayCurrency = $displayCurrency ?? Currency::getUserDisplay($loggedUser);
?>
<header class="border-b border-slate-700/50 bg-[#090F1C]/90 backdrop-blur sticky top-0 z-40" x-data="{ pubMenu: false }">
  <div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-6">
    <a href="<?= e($rp) ?>index.php" class="flex items-center gap-2.5 whitespace-nowrap shrink-0">
      <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-[#0D9488] to-[#4F46E5] flex items-center justify-center text-white shadow-sm">
        <svg viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5"><path d="M5 16L3 5l5.5 5L12 4l3.5 6L21 5l-2 11H5zm14 3c0 .6-.4 1-1 1H6c-.6 0-1-.4-1-1v-1h14v1z"/></svg>
      </span>
      <span class="text-lg font-bold tracking-tight text-white"><?= e($siteName) ?></span>
    </a>

    <nav class="hidden md:flex items-center gap-7 text-sm font-medium text-slate-300">
      <?php foreach ($navLinks as $link): ?>
        <a href="<?= e($link['href']) ?>" class="hover:text-teal-300 transition whitespace-nowrap shrink-0"><?= e($link['label']) ?></a>
      <?php endforeach; ?>
    </nav>

    <div class="flex items-center gap-2.5 shrink-0">
      <?php if ($loggedUser && !empty($enabledCurrencies)): ?>
        <div class="relative hidden sm:block" x-data="{ curOpen: false }">
          <button type="button" @click="curOpen = !curOpen" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl border border-teal-500/35 bg-teal-500/10 text-xs font-semibold text-teal-200">
            <span class="font-mono-num text-teal-300"><?= e($displayCurrency['symbol']) ?></span>
            <span><?= e($displayCurrency['code']) ?></span>
          </button>
          <div x-show="curOpen" @click.outside="curOpen = false" x-cloak class="absolute right-0 mt-2 w-48 aurora-dropdown rounded-2xl py-1.5 z-50">
            <?php foreach ($enabledCurrencies as $rc): ?>
              <button type="button" @click="window.SMMApp.switchCurrency('<?= e($rc['code']) ?>', '<?= e($rp) ?>', $el)" class="w-full px-3.5 py-2 text-left text-xs flex items-center justify-between hover:bg-white/5 <?= $rc['code'] === $displayCurrency['code'] ? 'bg-teal-500/15 text-teal-300 font-bold' : 'text-slate-200' ?>">
                <span><?= e($rc['code']) ?> — <?= e($rc['name']) ?></span>
                <span class="font-mono-num"><?= e($rc['symbol']) ?></span>
              </button>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <?php if ($loggedUser): ?>
        <a href="<?= e($rp) ?>dashboard.php" class="px-5 py-2 rounded-xl aurora-btn-primary text-sm font-semibold transition whitespace-nowrap shrink-0">
          Console
        </a>
      <?php else: ?>
        <a href="<?= e($rp) ?>login.php" class="px-3.5 py-2 text-sm font-semibold text-slate-200 hover:text-teal-300 transition whitespace-nowrap shrink-0">Sign In</a>
        <a href="<?= e($rp) ?>register.php" class="px-5 py-2 rounded-xl aurora-btn-primary text-sm font-semibold transition whitespace-nowrap shrink-0">
          Launch App
        </a>
      <?php endif; ?>

      <button type="button" @click="pubMenu = !pubMenu" class="md:hidden p-2 rounded-xl border border-slate-700 text-slate-200" aria-label="Menu">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>
    </div>
  </div>
  <div x-show="pubMenu" x-cloak class="md:hidden border-t border-slate-800 bg-[#090F1C] px-4 py-3 space-y-1">
    <?php foreach ($navLinks as $link): ?>
      <a href="<?= e($link['href']) ?>" class="block px-3 py-2 rounded-xl text-sm font-medium text-slate-200 hover:bg-white/5"><?= e($link['label']) ?></a>
    <?php endforeach; ?>
    <?php if ($loggedUser && !empty($enabledCurrencies)): ?>
      <div class="pt-2 mt-2 border-t border-slate-800">
        <p class="px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">Currency (<?= e($displayCurrency['code']) ?>)</p>
        <div class="flex flex-wrap gap-1.5 px-3 py-1">
          <?php foreach ($enabledCurrencies as $rc): ?>
            <button type="button" @click="window.SMMApp.switchCurrency('<?= e($rc['code']) ?>', '<?= e($rp) ?>', $el)" class="px-2.5 py-1 rounded-lg border text-xs font-semibold <?= $rc['code'] === $displayCurrency['code'] ? 'bg-teal-500/20 border-teal-400 text-teal-200' : 'border-slate-700 text-slate-300 bg-[#111A2E]' ?>">
              <?= e($rc['symbol']) ?> <?= e($rc['code']) ?>
            </button>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
</header>
