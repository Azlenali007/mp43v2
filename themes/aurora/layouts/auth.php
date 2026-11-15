<?php
declare(strict_types=1);
if (!defined('APP_ROOT')) {
    http_response_code(403);
    exit('Forbidden');
}
$rp = $rootPrefix ?? app_root_prefix();
$pageTitle = $pageTitle ?? 'Account';
$fullTitle = e($pageTitle) . ' — ' . e($siteName);
$loggedUser = $loggedUser ?? ($user ?? current_user());
?>
<!DOCTYPE html>
<html lang="en" class="theme-aurora">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $fullTitle ?></title>
  <meta name="csrf-token" content="<?= e($csrfToken ?? csrf_token()) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
  <link rel="stylesheet" href="<?= e($rp) ?>assets/css/style.css?v=2">
  <link rel="stylesheet" href="<?= e(Theme::assetUrl('css/style.css')) ?>">
  <script src="<?= e($rp) ?>assets/js/app.js?v=3"></script>
  <script src="<?= e(Theme::assetUrl('js/app.js')) ?>"></script>
</head>
<body class="bg-[#0B1220] text-[#F1F5F9] antialiased min-h-screen flex flex-col theme-aurora">
<?php if (!empty($isAdminPreview)): ?>
  <div class="bg-gradient-to-r from-teal-600 to-indigo-600 text-white text-xs font-medium py-1.5 px-4 text-center">
    Admin Theme Preview: <strong><?= e($activeTheme['name'] ?? 'Aurora Midnight') ?></strong>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/../components/navbar.php'; ?>
<main class="flex-1 flex items-center justify-center px-4 py-12">
  <div class="w-full max-w-4xl grid lg:grid-cols-12 gap-8 items-center">
    <div class="hidden lg:block lg:col-span-5 space-y-5 pr-4">
      <div class="inline-flex items-center gap-2 px-3 py-1 rounded-lg bg-teal-500/15 border border-teal-500/30 text-xs font-semibold text-teal-300">
        Aurora Command Console
      </div>
      <h2 class="text-3xl font-bold text-white leading-tight">Precision Social Fulfillment &amp; Multi-Currency Control</h2>
      <p class="text-sm text-slate-400 leading-relaxed">
        Atomic wallet ledger, live currency switching, automated API v2 endpoints, and instant online deposits.
      </p>
      <div class="space-y-3 pt-2 text-xs text-slate-300">
        <div class="flex items-center gap-2.5">
          <span class="w-2 h-2 rounded-full bg-teal-400"></span>
          <span>Live multi-currency rates synced automatically</span>
        </div>
        <div class="flex items-center gap-2.5">
          <span class="w-2 h-2 rounded-full bg-indigo-400"></span>
          <span>Real-time order tracking &amp; automated provider sync</span>
        </div>
      </div>
    </div>
    <div class="lg:col-span-7">
      <?php $notifMode = 'flash'; require __DIR__ . '/../components/notifications.php'; ?>
      <?= $pageContent ?? '' ?>
    </div>
  </div>
</main>
<?php require __DIR__ . '/../components/footer.php'; ?>
</body>
</html>
