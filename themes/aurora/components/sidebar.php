<?php
declare(strict_types=1);
if (!defined('APP_ROOT')) {
    http_response_code(403);
    exit('Forbidden');
}
$rp = $rootPrefix ?? app_root_prefix();
$currentScript = $currentScript ?? basename($_SERVER['SCRIPT_NAME'] ?? '');
$scriptPath = $_SERVER['SCRIPT_NAME'] ?? '';
$isToolsArea = str_contains($scriptPath, '/tools/');

$navItems = [
    ['href' => $rp . 'dashboard.php', 'match' => 'dashboard.php', 'label' => 'Command Center', 'icon' => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>'],
    ['href' => $rp . 'new-order.php', 'match' => 'new-order.php', 'label' => 'New Order', 'icon' => '<rect x="3" y="3" width="18" height="18" rx="2"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/>'],
    ['href' => $rp . 'bulk-order.php', 'match' => 'bulk-order.php', 'label' => 'Bulk Orders', 'icon' => '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>'],
    ['href' => $rp . 'services.php', 'match' => 'services.php', 'label' => 'Services', 'icon' => '<line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>'],
    ['href' => $rp . 'orders.php', 'match' => 'orders.php', 'label' => 'Orders', 'icon' => '<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>'],
    ['href' => $rp . 'add-funds.php', 'match' => 'add-funds.php', 'label' => 'Add Funds', 'icon' => '<rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/>'],
    ['href' => $rp . 'transactions.php', 'match' => 'transactions.php', 'label' => 'Ledger', 'icon' => '<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>'],
];

if (class_exists('Tools')) {
    try {
        if (!empty(Tools::all(true))) {
            $navItems[] = [
                'href'  => $rp . 'tools/index.php',
                'match' => 'tools',
                'label' => 'Creator Tools',
                'icon'  => '<path d="M12 3l1.912 5.813a2 2 0 0 0 1.275 1.275L21 12l-5.813 1.912a2 2 0 0 0-1.275 1.275L12 21l-1.912-5.813a2 2 0 0 0-1.275-1.275L3 12l5.813-1.912a2 2 0 0 0 1.275-1.275L12 3z"/>',
            ];
        }
    } catch (Throwable $e) {
    }
}

$navItems[] = ['href' => $rp . 'tickets.php', 'match' => 'tickets.php', 'label' => 'Support', 'icon' => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>'];
$navItems[] = ['href' => $rp . 'api-access.php', 'match' => 'api-access.php', 'label' => 'API v2', 'icon' => '<polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/>'];
$navItems[] = ['href' => $rp . 'profile.php', 'match' => 'profile.php', 'label' => 'Settings', 'icon' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>'];
$navItems[] = ['href' => $rp . 'faq.php', 'match' => 'faq.php', 'label' => 'FAQ', 'icon' => '<circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/>'];
$navItems[] = ['href' => $rp . 'contact.php', 'match' => 'contact.php', 'label' => 'Contact', 'icon' => '<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>'];
?>
<div x-show="mobileMenuOpen" x-cloak @click="mobileMenuOpen = false" class="fixed inset-0 bg-black/70 z-40 lg:hidden"></div>

<aside
  :class="mobileMenuOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
  class="fixed lg:sticky top-0 left-0 z-50 w-64 h-screen aurora-sidebar flex flex-col justify-between transition-transform duration-200 shrink-0 overflow-y-auto"
>
  <div>
    <div class="h-16 px-5 flex items-center justify-between border-b border-slate-800/80">
      <a href="<?= e($rp) ?>dashboard.php" class="flex items-center gap-2.5">
        <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-[#0D9488] to-[#4F46E5] flex items-center justify-center text-white shadow-sm">
          <svg viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5"><path d="M5 16L3 5l5.5 5L12 4l3.5 6L21 5l-2 11H5zm14 3c0 .6-.4 1-1 1H6c-.6 0-1-.4-1-1v-1h14v1z"/></svg>
        </span>
        <div>
          <span class="text-sm font-bold text-white tracking-tight block"><?= e($siteName) ?></span>
          <span class="text-[10px] uppercase tracking-widest text-teal-400 font-semibold block">Aurora</span>
        </div>
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
        <a href="<?= e($item['href']) ?>" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition <?= $active ? 'aurora-btn-primary shadow-sm' : 'text-slate-300 hover:bg-white/5 hover:text-teal-300' ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 shrink-0"><?= $item['icon'] ?></svg>
          <span class="whitespace-nowrap truncate"><?= e($item['label']) ?></span>
        </a>
      <?php endforeach; ?>
    </nav>
  </div>

  <div class="p-4">
    <div class="p-4 rounded-2xl bg-[#111A2E] border border-teal-500/20">
      <div class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-xl bg-teal-500/15 text-teal-300 flex items-center justify-center shrink-0">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
        </div>
        <div class="min-w-0">
          <p class="text-[11px] text-slate-400">Available Credit</p>
          <p class="text-base font-bold text-teal-300 font-mono-num truncate"><?= e(format_currency($user['wallet_balance'] ?? 0)) ?></p>
        </div>
      </div>
      <a href="<?= e($rp) ?>add-funds.php" class="mt-3 w-full inline-flex items-center justify-center gap-1.5 py-2 rounded-xl aurora-btn-primary text-xs font-semibold transition">
        <span>+ Top Up Wallet</span>
      </a>
    </div>
  </div>
</aside>
