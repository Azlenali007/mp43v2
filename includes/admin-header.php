<?php
declare(strict_types=1);
$siteName = get_setting('site_name', 'CrownSMM');
$currentScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
$flash = get_flash();
$adminInitials = strtoupper(substr($admin['name'] ?? 'AD', 0, 2));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
  <title><?= e($pageTitle ?? 'Admin Console') ?> — <?= e($siteName) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@500;600&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
   <?php $rp = app_root_prefix(); ?>
   <link rel="stylesheet" href="<?= e($rp) ?>assets/css/style.css">
   <link rel="stylesheet" href="<?= e($rp) ?>assets/css/custom.css">
 </head>
 <body class="bg-[#F8F9FC] text-[#151515] min-h-screen" x-data="{ mobileMenuOpen: false }">
   <div class="min-h-screen flex">
     <?php require __DIR__ . '/admin-sidebar.php'; ?>
 
     <div class="flex-1 flex flex-col min-w-0">
       <header class="h-16 bg-white border-b border-slate-200 px-4 lg:px-8 flex items-center justify-between gap-4 sticky top-0 z-30">
         <div class="flex items-center gap-3">
           <button type="button" @click="mobileMenuOpen = true" class="lg:hidden p-2 rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-50" aria-label="Open Menu">
             <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
           </button>
           <div>
             <h1 class="text-base sm:text-lg font-bold text-[#151515]"><?= e($pageTitle ?? 'Dashboard') ?></h1>
           </div>
         </div>
 
         <div class="flex items-center gap-3 shrink-0">
           <a href="<?= e($rp) ?>" target="_blank" class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 hover:bg-slate-50">
             View Site
           </a>
          <div class="relative" x-data="{ open: false }">
            <button type="button" @click="open = !open" class="flex items-center gap-2.5 pl-1.5 pr-3 py-1.5 rounded-xl border border-slate-200 hover:bg-slate-50">
              <span class="w-8 h-8 rounded-lg bg-[#7138D8] text-white text-xs font-bold flex items-center justify-center"><?= e($adminInitials) ?></span>
              <div class="hidden sm:block text-left">
                <p class="text-xs font-semibold text-[#151515] leading-tight"><?= e($admin['name']) ?></p>
                <p class="text-[11px] text-slate-500 leading-tight"><?= e($admin['role_name']) ?></p>
              </div>
            </button>
            <div x-show="open" @click.outside="open = false" x-cloak class="absolute right-0 mt-2 w-44 bg-white border border-slate-200 rounded-2xl shadow-lg py-1.5 z-40">
              <a href="settings.php" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Panel Settings</a>
              <a href="admins.php" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Administrators</a>
              <div class="my-1 border-t border-slate-100"></div>
              <a href="logout.php" class="block px-4 py-2 text-sm text-rose-600 font-medium hover:bg-rose-50">Sign Out</a>
            </div>
          </div>
        </div>
      </header>

      <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-[1440px] w-full mx-auto">
        <?php if ($flash): ?>
          <?php
          $flashCls = match ($flash['type']) {
              'success' => 'bg-emerald-50 border-emerald-200 text-[#16A36A]',
              'error'   => 'bg-rose-50 border-rose-200 text-rose-700',
              'warning' => 'bg-amber-50 border-amber-200 text-amber-800',
              default   => 'bg-purple-50 border-purple-200 text-[#7138D8]',
          };
          ?>
          <div class="mb-6 px-4 py-3 rounded-xl border text-sm font-medium <?= $flashCls ?>">
            <?= e($flash['message']) ?>
          </div>
        <?php endif; ?>
