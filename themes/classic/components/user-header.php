<?php
declare(strict_types=1);
$siteName = get_setting('site_name', 'CrownSMM');
$currentScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
$rootPrefix = app_root_prefix();
$flash = get_flash();
$activeThemeSlug = Theme::activeSlug();

$unreadCount = 0;
$notifications = [];
if (!empty($user)) {
    try {
        $pdo = Database::getConnection();
        $nStmt = $pdo->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 6');
        $nStmt->execute([(int) $user['id']]);
        $notifications = $nStmt->fetchAll();
        foreach ($notifications as $n) {
            if ((int) $n['is_read'] === 0) {
                $unreadCount++;
            }
        }
    } catch (Throwable $e) {
    }
}
$initials = '';
if (!empty($user['name'])) {
    $parts = preg_split('/\s+/', trim((string) $user['name']));
    $initials = strtoupper(substr($parts[0] ?? 'U', 0, 1) . substr($parts[1] ?? '', 0, 1));
}
$displayCurrency = Currency::getUserDisplay($user ?? null);
$readyCurrencies = Currency::all(true, true);
$isAdminPreview = !empty($_GET['preview_theme']) && function_exists('current_admin') && current_admin() !== null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
  <title><?= e($pageTitle ?? 'Dashboard') ?> — <?= e($siteName) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@500;600&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
  <link rel="stylesheet" href="<?= e($rootPrefix) ?>assets/css/style.css">
  <link rel="stylesheet" href="<?= e($rootPrefix) ?>assets/css/custom.css">
  <link rel="stylesheet" href="<?= e(Theme::assetUrl('css/theme.css')) ?>">
</head>
<body class="theme-classic bg-[#F8F9FC] text-[#151515] min-h-screen" x-data="{ mobileMenuOpen: false }">
  <?php if ($isAdminPreview): ?>
    <div class="bg-[#7138D8] text-white text-xs font-semibold px-4 py-2 text-center">
      Admin Theme Preview Mode: <strong><?= e($activeThemeSlug) ?></strong> — <a href="<?= e($rootPrefix) ?>admin/themes.php" class="underline ml-2">Return to Theme Manager</a>
    </div>
  <?php endif; ?>
  <div class="min-h-screen flex">
    <?php require __DIR__ . '/user-sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 pb-16 md:pb-0">
      <header class="h-16 bg-white border-b border-slate-200 px-4 lg:px-8 flex items-center justify-between gap-4 sticky top-0 z-30">
        <div class="flex items-center gap-3 flex-1 min-w-0">
          <button type="button" @click="mobileMenuOpen = true" class="lg:hidden p-2 rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-50" aria-label="Open Menu">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
          </button>

          <form action="<?= e($rootPrefix) ?>services.php" method="GET" class="hidden sm:flex items-center flex-1 max-w-md relative">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4 text-slate-400 absolute left-3.5 pointer-events-none"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Search services..." class="w-full pl-10 pr-4 py-2 rounded-xl bg-[#F8F9FC] border border-slate-200 text-sm focus:outline-none focus:border-[#7138D8] focus:bg-white transition">
          </form>
        </div>

        <div class="flex items-center gap-2.5 sm:gap-3 shrink-0">
          <?php if (!empty($user) && count($readyCurrencies) > 1): ?>
            <!-- Logged-in User Display Currency Switcher -->
            <div class="relative" x-data="{ curOpen: false }">
              <button
                type="button"
                @click="curOpen = !curOpen"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 text-xs font-semibold text-[#151515] transition"
                title="Switch Display Currency"
              >
                <span class="font-mono-num text-[#7138D8]"><?= e($displayCurrency['symbol']) ?></span>
                <span><?= e($displayCurrency['code']) ?></span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-3.5 h-3.5 text-slate-400"><polyline points="6 9 12 15 18 9"/></svg>
              </button>
              <div x-show="curOpen" @click.outside="curOpen = false" x-cloak class="absolute right-0 mt-2 w-48 bg-white border border-slate-200 rounded-2xl shadow-lg py-1.5 z-40">
                <p class="px-3.5 py-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-400">Display Currency</p>
                <?php foreach ($readyCurrencies as $rc): ?>
                  <button
                    type="button"
                    onclick="window.ThemeManager && window.ThemeManager.switchCurrency('<?= e($rc['code']) ?>', '<?= e($rootPrefix) ?>')"
                    class="w-full px-3.5 py-2 text-left text-xs flex items-center justify-between hover:bg-slate-50 <?= $rc['code'] === $displayCurrency['code'] ? 'bg-purple-50 text-[#7138D8] font-bold' : 'text-slate-700' ?>"
                  >
                    <span><?= e($rc['code']) ?> — <?= e($rc['name']) ?></span>
                    <span class="font-mono-num"><?= e($rc['symbol']) ?></span>
                  </button>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>

          <a href="<?= e($rootPrefix) ?>add-funds.php" class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-50 border border-emerald-200 text-xs font-semibold text-[#16A36A] font-mono-num">
            <span><?= e(format_currency($user['wallet_balance'] ?? 0)) ?></span>
          </a>

          <!-- Notifications -->
          <div class="relative" x-data="{ open: false }">
            <button type="button" @click="open = !open" class="relative p-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition" aria-label="Notifications">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
              <?php if ($unreadCount > 0): ?>
                <span class="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-rose-600 text-white text-[10px] font-bold flex items-center justify-center"><?= $unreadCount ?></span>
              <?php endif; ?>
            </button>
            <div x-show="open" @click.outside="open = false" x-cloak class="absolute right-0 mt-2 w-80 bg-white border border-slate-200 rounded-2xl shadow-lg overflow-hidden z-40">
              <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
                <span class="text-sm font-semibold text-[#151515]">Notifications</span>
                <?php if ($unreadCount > 0): ?>
                  <form method="POST" action="<?= e($rootPrefix) ?>api/wallet.php" class="inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="mark_notifications_read">
                    <button type="submit" class="text-xs font-medium text-[#7138D8] hover:underline">Mark all read</button>
                  </form>
                <?php endif; ?>
              </div>
              <div class="max-h-72 overflow-y-auto divide-y divide-slate-100">
                <?php if (empty($notifications)): ?>
                  <div class="p-4 text-xs text-slate-500 text-center">No notifications yet.</div>
                <?php else: ?>
                  <?php foreach ($notifications as $notif): ?>
                    <a href="<?= e($notif['link'] ?: ($rootPrefix . 'dashboard.php')) ?>" class="block px-4 py-3 hover:bg-slate-50 transition <?= (int)$notif['is_read'] === 0 ? 'bg-purple-50/40' : '' ?>">
                      <p class="text-xs font-semibold text-[#151515]"><?= e($notif['title']) ?></p>
                      <p class="text-xs text-slate-600 mt-0.5"><?= e($notif['message']) ?></p>
                      <p class="text-[11px] text-slate-400 mt-1"><?= e(time_ago($notif['created_at'])) ?></p>
                    </a>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <!-- Profile Menu -->
          <div class="relative" x-data="{ open: false }">
            <button type="button" @click="open = !open" class="flex items-center gap-2.5 pl-1.5 pr-3 py-1.5 rounded-xl border border-slate-200 hover:bg-slate-50 transition">
              <span class="w-8 h-8 rounded-lg bg-[#151515] text-white text-xs font-bold flex items-center justify-center"><?= e($initials ?: 'U') ?></span>
              <span class="hidden sm:block text-sm font-semibold text-[#151515] whitespace-nowrap"><?= e($user['name'] ?? 'Account') ?></span>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4 text-slate-400"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div x-show="open" @click.outside="open = false" x-cloak class="absolute right-0 mt-2 w-48 bg-white border border-slate-200 rounded-2xl shadow-lg py-1.5 z-40">
              <a href="<?= e($rootPrefix) ?>profile.php" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Account Settings</a>
              <a href="<?= e($rootPrefix) ?>transactions.php" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Transactions</a>
              <a href="<?= e($rootPrefix) ?>api-access.php" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">API Access</a>
              <div class="my-1 border-t border-slate-100"></div>
              <a href="<?= e($rootPrefix) ?>logout.php" class="block px-4 py-2 text-sm text-rose-600 font-medium hover:bg-rose-50">Sign Out</a>
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
