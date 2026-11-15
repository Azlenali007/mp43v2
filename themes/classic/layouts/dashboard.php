<?php
declare(strict_types=1);
if (!defined('APP_ROOT')) {
    http_response_code(403);
    exit('Forbidden');
}
$mode = $layoutMode ?? 'full';
$rp = $rootPrefix ?? app_root_prefix();
$pageTitle = $pageTitle ?? 'Dashboard';
$fullTitle = e($pageTitle) . ' — ' . e($siteName);
?>
<?php if ($mode === 'full' || $mode === 'header'): ?>
<!DOCTYPE html>
<html lang="en" class="theme-classic">
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
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
  <link rel="stylesheet" href="<?= e($rp) ?>assets/css/style.css?v=2">
  <link rel="stylesheet" href="<?= e(Theme::assetUrl('css/style.css')) ?>">
  <script src="<?= e($rp) ?>assets/js/app.js?v=3"></script>
  <script src="<?= e(Theme::assetUrl('js/app.js')) ?>"></script>
  <script src="<?= e($rp) ?>assets/js/dashboard.js?v=2"></script>
</head>
<body class="bg-[#F8F9FC] text-[#151515] antialiased min-h-screen theme-classic" x-data="{ mobileMenuOpen: false }">
<?php if (!empty($isAdminPreview)): ?>
  <div class="bg-[#7138D8] text-white text-xs font-medium py-1.5 px-4 text-center">
    Admin Theme Preview: <strong><?= e($activeTheme['name'] ?? 'Classic Studio') ?></strong>
  </div>
<?php endif; ?>
<div class="min-h-screen flex">
  <?php require __DIR__ . '/../components/sidebar.php'; ?>
  <div class="flex-1 flex flex-col min-w-0">
    <?php require __DIR__ . '/../components/header.php'; ?>
    <main class="flex-1 p-4 sm:p-6 lg:p-8 pb-20 md:pb-8">
      <?php $notifMode = 'flash'; require __DIR__ . '/../components/notifications.php'; ?>
<?php endif; ?>
<?php if ($mode === 'full'): ?>
      <?= $pageContent ?? '' ?>
<?php endif; ?>
<?php if ($mode === 'full' || $mode === 'footer'): ?>
    </main>
  </div>
</div>
<?php require __DIR__ . '/../components/mobile-nav.php'; ?>
<?php if (!empty($pageScripts) && is_array($pageScripts)): ?>
  <?php foreach ($pageScripts as $src): ?>
    <script src="<?= e($rp . ltrim($src, '/')) ?>"></script>
  <?php endforeach; ?>
<?php endif; ?>
</body>
</html>
<?php endif; ?>
