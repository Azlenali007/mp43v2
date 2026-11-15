<?php
declare(strict_types=1);
if (!defined('APP_ROOT')) {
    http_response_code(403);
    exit('Forbidden');
}
$rp = $rootPrefix ?? app_root_prefix();
$enabledCurrencies = $enabledCurrencies ?? Currency::all(true, false);
$displayCurrency = $displayCurrency ?? Currency::getUserDisplay($user ?? null);
?>
<header class="h-16 bg-[#0D1526]/90 backdrop-blur border-b border-slate-700/50 px-4 lg:px-8 flex items-center justify-between gap-4 sticky top-0 z-30">
  <div class="flex items-center gap-3 flex-1 min-w-0">
    <button type="button" @click="mobileMenuOpen = true" class="lg:hidden p-2 rounded-xl border border-slate-700/70 text-slate-200 hover:bg-white/5" aria-label="Open Menu">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>

    <form action="<?= e($rp) ?>services.php" method="GET" class="hidden sm:flex items-center flex-1 max-w-md relative">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4 text-slate-400 absolute left-3.5 pointer-events-none"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input type="text" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Command search: services, platforms, IDs..." class="w-full pl-10 pr-4 py-2 rounded-xl aurora-input border text-sm transition">
    </form>
  </div>

  <div class="flex items-center gap-2.5 sm:gap-3 shrink-0">
    <?php if (!empty($user) && !empty($enabledCurrencies)): ?>
      <div class="relative" x-data="{ curOpen: false }">
        <button
          type="button"
          @click="curOpen = !curOpen"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-teal-500/35 bg-teal-500/10 hover:bg-teal-500/20 text-xs font-semibold text-teal-200 transition"
          title="Switch Display Currency"
        >
          <span class="font-mono-num text-teal-300"><?= e($displayCurrency['symbol']) ?></span>
          <span><?= e($displayCurrency['code']) ?></span>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-3.5 h-3.5 text-teal-300/70"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div x-show="curOpen" @click.outside="curOpen = false" x-cloak class="absolute right-0 mt-2 w-56 aurora-dropdown rounded-2xl py-1.5 z-40">
          <p class="px-3.5 py-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-400">Display Currency</p>
          <div class="max-h-64 overflow-y-auto">
            <?php foreach ($enabledCurrencies as $rc): ?>
              <?php $rateReady = (float) ($rc['exchange_rate'] ?? 0) > 0; ?>
              <button
                type="button"
                data-currency-code="<?= e($rc['code']) ?>"
                @click="window.SMMApp.switchCurrency('<?= e($rc['code']) ?>', '<?= e($rp) ?>', $el).finally(() => curOpen = false)"
                class="w-full px-3.5 py-2 text-left text-xs flex items-center justify-between gap-2 hover:bg-white/5 transition <?= $rc['code'] === $displayCurrency['code'] ? 'bg-teal-500/15 text-teal-300 font-bold' : 'text-slate-200' ?>"
              >
                <span class="truncate"><?= e($rc['code']) ?> — <?= e($rc['name']) ?></span>
                <span class="font-mono-num shrink-0 flex items-center gap-1.5">
                  <?php if (!$rateReady): ?>
                    <span class="text-[10px] text-amber-400 font-sans">Sync needed</span>
                  <?php endif; ?>
                  <span><?= e($rc['symbol']) ?></span>
                </span>
              </button>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <a href="<?= e($rp) ?>add-funds.php" class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-teal-500/10 border border-teal-500/30 text-xs font-semibold text-teal-300 font-mono-num hover:bg-teal-500/20 transition">
      <span><?= e(format_currency($user['wallet_balance'] ?? 0)) ?></span>
    </a>

    <?php $notifMode = 'bell'; require __DIR__ . '/notifications.php'; ?>

    <div class="relative" x-data="{ open: false }">
      <button type="button" @click="open = !open" class="flex items-center gap-2.5 pl-1.5 pr-3 py-1.5 rounded-xl border border-slate-700/70 bg-[#111A2E] hover:border-teal-400/50 transition">
        <span class="w-8 h-8 rounded-lg bg-gradient-to-br from-teal-500 to-indigo-600 text-white text-xs font-bold flex items-center justify-center"><?= e($initials ?: 'U') ?></span>
        <span class="hidden sm:block text-sm font-semibold text-white whitespace-nowrap"><?= e($user['name'] ?? 'Account') ?></span>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4 text-slate-400"><polyline points="6 9 12 15 18 9"/></svg>
      </button>
      <div x-show="open" @click.outside="open = false" x-cloak class="absolute right-0 mt-2 w-48 aurora-dropdown rounded-2xl py-1.5 z-40">
        <a href="<?= e($rp) ?>profile.php" class="block px-4 py-2 text-sm text-slate-200 hover:bg-white/5">Account Settings</a>
        <a href="<?= e($rp) ?>transactions.php" class="block px-4 py-2 text-sm text-slate-200 hover:bg-white/5">Transactions</a>
        <a href="<?= e($rp) ?>api-access.php" class="block px-4 py-2 text-sm text-slate-200 hover:bg-white/5">API Access</a>
        <div class="my-1 border-t border-slate-800"></div>
        <a href="<?= e($rp) ?>logout.php" class="block px-4 py-2 text-sm text-rose-400 font-medium hover:bg-rose-500/10">Sign Out</a>
      </div>
    </div>
  </div>
</header>
