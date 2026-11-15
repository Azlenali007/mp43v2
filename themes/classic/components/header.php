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
<header class="h-16 bg-white border-b border-slate-200 px-4 lg:px-8 flex items-center justify-between gap-4 sticky top-0 z-30">
  <div class="flex items-center gap-3 flex-1 min-w-0">
    <button type="button" @click="mobileMenuOpen = true" class="lg:hidden p-2 rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-50" aria-label="Open Menu">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>

    <form action="<?= e($rp) ?>services.php" method="GET" class="hidden sm:flex items-center flex-1 max-w-md relative">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4 text-slate-400 absolute left-3.5 pointer-events-none"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input type="text" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Search services..." class="w-full pl-10 pr-4 py-2 rounded-xl bg-[#F8F9FC] border border-slate-200 text-sm focus:outline-none focus:border-[#7138D8] focus:bg-white transition">
    </form>
  </div>

  <div class="flex items-center gap-2.5 sm:gap-3 shrink-0">
    <?php if (!empty($user) && !empty($enabledCurrencies)): ?>
      <!-- Multi-Currency Display Switcher (Always visible for logged-in users) -->
      <div class="relative" x-data="{ curOpen: false, switching: false }">
        <button
          type="button"
          @click="curOpen = !curOpen"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 text-xs font-semibold text-[#151515] transition"
          title="Switch Display Currency"
          aria-expanded="false"
        >
          <span class="font-mono-num text-[#7138D8]"><?= e($displayCurrency['symbol']) ?></span>
          <span><?= e($displayCurrency['code']) ?></span>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-3.5 h-3.5 text-slate-400"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div x-show="curOpen" @click.outside="curOpen = false" x-cloak class="absolute right-0 mt-2 w-56 bg-white border border-slate-200 rounded-2xl shadow-lg py-1.5 z-40">
          <p class="px-3.5 py-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-400">Display Currency</p>
          <div class="max-h-64 overflow-y-auto">
            <?php foreach ($enabledCurrencies as $rc): ?>
              <?php $rateReady = (float) ($rc['exchange_rate'] ?? 0) > 0; ?>
              <button
                type="button"
                data-currency-code="<?= e($rc['code']) ?>"
                @click="switching = true; window.SMMApp.switchCurrency('<?= e($rc['code']) ?>', '<?= e($rp) ?>', $el).finally(() => { switching = false; curOpen = false; })"
                class="w-full px-3.5 py-2 text-left text-xs flex items-center justify-between gap-2 hover:bg-slate-50 transition <?= $rc['code'] === $displayCurrency['code'] ? 'bg-purple-50 text-[#7138D8] font-bold' : 'text-slate-700' ?>"
              >
                <span class="truncate"><?= e($rc['code']) ?> — <?= e($rc['name']) ?></span>
                <span class="font-mono-num shrink-0 flex items-center gap-1.5">
                  <?php if (!$rateReady): ?>
                    <span class="text-[10px] text-amber-600 font-sans">Sync needed</span>
                  <?php endif; ?>
                  <span><?= e($rc['symbol']) ?></span>
                </span>
              </button>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <a href="<?= e($rp) ?>add-funds.php" class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-50 border border-emerald-200 text-xs font-semibold text-[#16A36A] font-mono-num">
      <span><?= e(format_currency($user['wallet_balance'] ?? 0)) ?></span>
    </a>

    <?php $notifMode = 'bell'; require __DIR__ . '/notifications.php'; ?>

    <!-- Profile Menu -->
    <div class="relative" x-data="{ open: false }">
      <button type="button" @click="open = !open" class="flex items-center gap-2.5 pl-1.5 pr-3 py-1.5 rounded-xl border border-slate-200 hover:bg-slate-50 transition">
        <span class="w-8 h-8 rounded-lg bg-[#151515] text-white text-xs font-bold flex items-center justify-center"><?= e($initials ?: 'U') ?></span>
        <span class="hidden sm:block text-sm font-semibold text-[#151515] whitespace-nowrap"><?= e($user['name'] ?? 'Account') ?></span>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4 text-slate-400"><polyline points="6 9 12 15 18 9"/></svg>
      </button>
      <div x-show="open" @click.outside="open = false" x-cloak class="absolute right-0 mt-2 w-48 bg-white border border-slate-200 rounded-2xl shadow-lg py-1.5 z-40">
        <a href="<?= e($rp) ?>profile.php" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Account Settings</a>
        <a href="<?= e($rp) ?>transactions.php" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Transactions</a>
        <a href="<?= e($rp) ?>api-access.php" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">API Access</a>
        <div class="my-1 border-t border-slate-100"></div>
        <a href="<?= e($rp) ?>logout.php" class="block px-4 py-2 text-sm text-rose-600 font-medium hover:bg-rose-50">Sign Out</a>
      </div>
    </div>
  </div>
</header>
