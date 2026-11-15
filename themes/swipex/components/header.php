<?php
declare(strict_types=1);
$rp = $rootPrefix ?? app_root_prefix();
$user = $loggedUser ?? $user ?? null;
$unreadCount = $unreadCount ?? 0;
$notifications = $notifications ?? [];
$enabledCurrencies = $enabledCurrencies ?? [];
$displayCurrency = $displayCurrency ?? ['code' => 'INR', 'symbol' => '₹'];
$initials = $initials ?? 'U';
?>
<header class="sticky top-0 z-40 bg-white/90 backdrop-blur-md border-b border-slate-200 px-4 sm:px-6 py-3">
  <div class="max-w-7xl mx-auto flex items-center justify-between gap-4">
    <!-- Brand / Logo -->
    <div class="flex items-center gap-3">
      <a href="<?= e($rp) ?>dashboard.php" class="flex items-center gap-3 group">
        <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 flex items-center justify-center text-white shadow-md shadow-blue-500/20 group-hover:scale-105 transition">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="w-5 h-5"><path d="M23 6l-9.5 9.5-5-5L1 18"/><path d="M17 6h6v6"/></svg>
        </div>
        <div>
          <span class="text-base font-extrabold tracking-tight text-slate-900 block leading-none"><?= e($siteName ?? 'SMM Panel') ?></span>
          <span class="text-[11px] text-slate-500 font-medium block mt-0.5">Grow Your Social Media</span>
        </div>
      </a>
    </div>

    <!-- Right Controls: Currency, Notifications, Profile Avatar -->
    <div class="flex items-center gap-2.5 sm:gap-3.5">
      <!-- Currency Selector -->
      <?php if (!empty($enabledCurrencies)): ?>
        <form method="POST" action="<?= e($rp) ?>api/set-currency.php" class="relative">
          <input type="hidden" name="csrf_token" value="<?= e($csrfToken ?? '') ?>">
          <select name="currency" onchange="this.form.submit()" class="text-xs font-bold bg-slate-100 hover:bg-slate-200/70 border border-slate-200 rounded-xl px-2.5 py-2 text-slate-800 focus:outline-none focus:border-blue-600 transition cursor-pointer">
            <?php foreach ($enabledCurrencies as $cur): ?>
              <option value="<?= e($cur['code']) ?>" <?= $displayCurrency['code'] === $cur['code'] ? 'selected' : '' ?>>
                <?= e($cur['symbol'] . ' ' . $cur['code']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </form>
      <?php endif; ?>

      <!-- Notifications Dropdown (Alpine) -->
      <div class="relative" x-data="{ notifOpen: false }">
        <button @click="notifOpen = !notifOpen" class="w-10 h-10 rounded-full bg-slate-100 hover:bg-slate-200/80 border border-slate-200 flex items-center justify-center text-slate-700 relative transition">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
          <?php if ($unreadCount > 0): ?>
            <span class="absolute top-1 right-1 w-2.5 h-2.5 rounded-full bg-rose-500 ring-2 ring-white"></span>
          <?php endif; ?>
        </button>

        <div x-show="notifOpen" @click.outside="notifOpen = false" x-cloak class="absolute right-0 mt-2 w-80 bg-white rounded-2xl shadow-xl border border-slate-200 py-3 z-50">
          <div class="px-4 pb-2 border-b border-slate-100 flex items-center justify-between">
            <span class="text-xs font-bold text-slate-900">Notifications</span>
            <span class="text-[10px] bg-blue-50 text-blue-600 font-semibold px-2 py-0.5 rounded-full"><?= (int) $unreadCount ?> New</span>
          </div>
          <div class="max-h-64 overflow-y-auto divide-y divide-slate-100">
            <?php if (empty($notifications)): ?>
              <p class="py-6 text-center text-xs text-slate-500">No notifications found</p>
            <?php else: ?>
              <?php foreach ($notifications as $n): ?>
                <div class="px-4 py-2.5 hover:bg-slate-50 transition">
                  <p class="text-xs font-semibold text-slate-800"><?= e($n['title']) ?></p>
                  <p class="text-[11px] text-slate-500 mt-0.5 line-clamp-2"><?= e($n['message']) ?></p>
                  <span class="text-[10px] text-slate-400 mt-1 block"><?= e(time_ago($n['created_at'])) ?></span>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Support Icon Button -->
      <a href="<?= e($rp) ?>tickets.php" class="hidden sm:flex w-10 h-10 rounded-full bg-slate-100 hover:bg-slate-200/80 border border-slate-200 items-center justify-center text-slate-700 transition" title="Support">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
      </a>

      <!-- Profile Avatar -->
      <a href="<?= e($rp) ?>profile.php" class="w-10 h-10 rounded-full bg-gradient-to-tr from-blue-600 to-indigo-600 text-white font-bold text-xs flex items-center justify-center shadow-md shadow-blue-500/20 hover:scale-105 transition">
        <?= e($initials) ?>
      </a>
    </div>
  </div>
</header>
