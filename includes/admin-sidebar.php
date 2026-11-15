<?php
declare(strict_types=1);
$adminNav = [
    ['href' => 'dashboard.php', 'label' => 'Dashboard'],
    ['href' => 'users.php', 'label' => 'Users'],
    ['href' => 'categories.php', 'label' => 'Categories'],
    ['href' => 'services.php', 'label' => 'Services'],
    ['href' => 'providers.php', 'label' => 'Providers'],
    ['href' => 'import-services.php', 'label' => 'Import Services'],
    ['href' => 'orders.php', 'label' => 'Orders'],
    ['href' => 'payment-methods.php', 'label' => 'Payment Methods'],
    ['href' => 'payments.php', 'label' => 'Payments'],
    ['href' => 'transactions.php', 'label' => 'Transactions'],
    ['href' => 'currencies.php', 'label' => 'Currencies'],
    ['href' => 'themes.php', 'label' => 'Themes'],
    ['href' => 'tools.php', 'label' => 'Creator Tools'],
    ['href' => 'tickets.php', 'label' => 'Support Tickets'],
    ['href' => 'announcements.php', 'label' => 'Announcements'],
    ['href' => 'reports.php', 'label' => 'Reports'],
    ['href' => 'settings.php', 'label' => 'Settings'],
    ['href' => 'admins.php', 'label' => 'Admins & Roles'],
    ['href' => 'activity-logs.php', 'label' => 'Activity Logs'],
    ['href' => 'security.php', 'label' => 'Security & Abuse'],
    ['href' => 'backup.php', 'label' => 'Database Backup'],
];
?>
<div x-show="mobileMenuOpen" x-cloak @click="mobileMenuOpen = false" class="fixed inset-0 bg-black/50 z-40 lg:hidden"></div>

<aside
  :class="mobileMenuOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
  class="fixed lg:sticky top-0 left-0 z-50 w-64 h-screen sidebar-dark flex flex-col transition-transform duration-200 shrink-0 overflow-y-auto"
>
  <div class="h-16 px-5 flex items-center justify-between border-b border-white/10 shrink-0">
    <a href="dashboard.php" class="flex items-center gap-2.5">
      <span class="w-9 h-9 rounded-xl bg-[#7138D8] flex items-center justify-center text-white">
        <svg viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5"><path d="M5 16L3 5l5.5 5L12 4l3.5 6L21 5l-2 11H5zm14 3c0 .6-.4 1-1 1H6c-.6 0-1-.4-1-1v-1h14v1z"/></svg>
      </span>
      <span class="text-base font-bold text-white tracking-tight whitespace-nowrap"><?= e($siteName) ?> Admin</span>
    </a>
    <button type="button" @click="mobileMenuOpen = false" class="lg:hidden p-1.5 text-slate-400 hover:text-white">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
  </div>

  <nav class="p-3 space-y-1 flex-1">
    <?php foreach ($adminNav as $item): ?>
      <?php
      $active = $currentScript === $item['href']
          || ($item['href'] === 'users.php' && in_array($currentScript, ['user-add.php', 'user-edit.php', 'user-details.php'], true))
          || ($item['href'] === 'services.php' && in_array($currentScript, ['service-add.php', 'service-edit.php'], true))
          || ($item['href'] === 'providers.php' && in_array($currentScript, ['provider-add.php', 'provider-edit.php'], true))
          || ($item['href'] === 'orders.php' && $currentScript === 'order-details.php')
          || ($item['href'] === 'tickets.php' && $currentScript === 'ticket-details.php');
      ?>
      <a href="<?= e($item['href']) ?>" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-medium transition <?= $active ? 'bg-[#7138D8] text-white shadow-sm' : 'text-slate-300 hover:bg-white/5 hover:text-white' ?>">
        <span class="whitespace-nowrap truncate"><?= e($item['label']) ?></span>
      </a>
    <?php endforeach; ?>
  </nav>
</aside>
