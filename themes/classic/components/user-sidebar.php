<?php
declare(strict_types=1);
require_once __DIR__ . '/../layouts/user.php';
$rp = $rootPrefix ?? app_root_prefix();
$navItems = classic_user_nav_items($rp);
$scriptPath = $_SERVER['SCRIPT_NAME'] ?? '';
$isToolsArea = str_contains($scriptPath, '/tools/');
?>
<!-- Mobile Backdrop -->
<div x-show="mobileMenuOpen" x-cloak @click="mobileMenuOpen = false" class="fixed inset-0 bg-black/50 z-40 lg:hidden"></div>

<aside
  :class="mobileMenuOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
  class="fixed lg:sticky top-0 left-0 z-50 w-64 h-screen sidebar-dark flex flex-col justify-between transition-transform duration-200 shrink-0 overflow-y-auto"
>
  <div>
    <div class="h-16 px-5 flex items-center justify-between border-b border-white/10">
      <a href="<?= e($rp) ?>dashboard.php" class="flex items-center gap-2.5">
        <span class="w-9 h-9 rounded-xl bg-[#7138D8] flex items-center justify-center text-white shadow-sm">
          <svg viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5"><path d="M5 16L3 5l5.5 5L12 4l3.5 6L21 5l-2 11H5zm14 3c0 .6-.4 1-1 1H6c-.6 0-1-.4-1-1v-1h14v1z"/></svg>
        </span>
        <span class="text-base font-bold text-white tracking-tight whitespace-nowrap"><?= e($siteName) ?></span>
      </a>
      <button type="button" @click="mobileMenuOpen = false" class="lg:hidden p-1.5 text-slate-400 hover:text-white" aria-label="Close Menu">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>

    <nav class="p-3 space-y-1">
      <?php foreach ($navItems as $item): ?>
        <?php
        $matchKey = $item['match'] ?? $item['href'];
        if ($matchKey === 'tools') {
            $active = $isToolsArea;
        } else {
            $active = !$isToolsArea && (
                ($currentScript === $matchKey)
                || ($matchKey === 'orders.php' && $currentScript === 'order-details.php')
                || ($matchKey === 'tickets.php' && $currentScript === 'ticket-details.php')
            );
        }
        ?>
        <a href="<?= e($item['href']) ?>" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition <?= $active ? 'bg-[#7138D8] text-white shadow-sm' : 'text-slate-300 hover:bg-white/5 hover:text-white' ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 shrink-0"><?= $item['icon'] ?></svg>
          <span class="whitespace-nowrap truncate"><?= e($item['label']) ?></span>
        </a>
      <?php endforeach; ?>
    </nav>
  </div>

  <div class="p-4 space-y-4">
    <div class="p-4 rounded-2xl bg-white/[0.04] border border-white/10">
      <div class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-xl bg-emerald-500/15 text-[#16A36A] flex items-center justify-center shrink-0">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
        </div>
        <div class="min-w-0">
          <p class="text-[11px] text-slate-400">Wallet Balance</p>
          <p class="text-base font-bold text-[#16A36A] font-mono-num truncate"><?= e(format_currency($user['wallet_balance'] ?? 0)) ?></p>
        </div>
      </div>
      <a href="<?= e($rp) ?>add-funds.php" class="mt-3 w-full inline-flex items-center justify-center gap-1.5 py-2 rounded-xl bg-[#7138D8] hover:bg-[#5E28C2] text-white text-xs font-semibold transition">
        <span>+ Add Funds</span>
      </a>
    </div>
  </div>
</aside>
