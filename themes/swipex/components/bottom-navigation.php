<?php
declare(strict_types=1);
$rp = $rootPrefix ?? app_root_prefix();
$current = basename($_SERVER['SCRIPT_NAME'] ?? '');
$isHome = ($current === 'dashboard.php' || $current === 'index.php');
$isServices = ($current === 'services.php');
$isNewOrder = ($current === 'new-order.php' || $current === 'bulk-order.php');
$isOrders = ($current === 'orders.php' || $current === 'order-details.php');
$isProfile = ($current === 'profile.php' || $current === 'add-funds.php' || $current === 'transactions.php' || $current === 'tickets.php');
?>
<div class="fixed bottom-4 left-4 right-4 z-40 max-w-md mx-auto md:hidden">
  <nav class="floating-bottom-nav rounded-2xl px-4 py-2.5 flex items-center justify-around border border-slate-200/80">
    <!-- Home -->
    <a href="<?= e($rp) ?>dashboard.php" class="flex flex-col items-center gap-1 group py-1">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5 <?= $isHome ? 'text-blue-600 stroke-[2.5]' : 'text-slate-500 group-hover:text-slate-800' ?>"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
      <span class="text-[10px] font-semibold <?= $isHome ? 'text-blue-600' : 'text-slate-500' ?>">Home</span>
    </a>

    <!-- Services -->
    <a href="<?= e($rp) ?>services.php" class="flex flex-col items-center gap-1 group py-1">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5 <?= $isServices ? 'text-blue-600 stroke-[2.5]' : 'text-slate-500 group-hover:text-slate-800' ?>"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
      <span class="text-[10px] font-semibold <?= $isServices ? 'text-blue-600' : 'text-slate-500' ?>">Services</span>
    </a>

    <!-- Central Floating New Order Button -->
    <div class="relative -top-5">
      <a href="<?= e($rp) ?>new-order.php" class="w-14 h-14 rounded-full bg-gradient-to-tr from-blue-600 to-indigo-600 text-white flex items-center justify-center shadow-lg shadow-blue-500/40 hover:scale-110 active:scale-95 transition">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" class="w-6 h-6"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      </a>
    </div>

    <!-- Orders -->
    <a href="<?= e($rp) ?>orders.php" class="flex flex-col items-center gap-1 group py-1">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5 <?= $isOrders ? 'text-blue-600 stroke-[2.5]' : 'text-slate-500 group-hover:text-slate-800' ?>"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
      <span class="text-[10px] font-semibold <?= $isOrders ? 'text-blue-600' : 'text-slate-500' ?>">Orders</span>
    </a>

    <!-- Profile -->
    <a href="<?= e($rp) ?>profile.php" class="flex flex-col items-center gap-1 group py-1">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5 <?= $isProfile ? 'text-blue-600 stroke-[2.5]' : 'text-slate-500 group-hover:text-slate-800' ?>"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      <span class="text-[10px] font-semibold <?= $isProfile ? 'text-blue-600' : 'text-slate-500' ?>">Profile</span>
    </a>
  </nav>
</div>
