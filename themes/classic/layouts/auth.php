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
  <link rel="stylesheet" href="<?= e($rp) ?>assets/css/style.css?v=2">
  <link rel="stylesheet" href="<?= e(Theme::assetUrl('css/style.css')) ?>">
  <script src="<?= e($rp) ?>assets/js/app.js?v=3"></script>
  <script src="<?= e(Theme::assetUrl('js/app.js')) ?>"></script>
</head>
<body class="bg-[#F8F9FC] text-[#151515] antialiased min-h-screen flex flex-col theme-classic">
<?php if (!empty($isAdminPreview)): ?>
  <div class="bg-[#7138D8] text-white text-xs font-medium py-1.5 px-4 text-center">
    Admin Theme Preview: <strong><?= e($activeTheme['name'] ?? 'Classic Studio') ?></strong>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/../components/navbar.php'; ?>
<main class="flex-1 flex items-center justify-center px-4 py-12">
  <div class="w-full max-w-md">
    <?php $notifMode = 'flash'; require __DIR__ . '/../components/notifications.php'; ?>
    <?= $pageContent ?? '' ?>
  </div>
</main>
<?php require __DIR__ . '/../components/footer.php'; ?>
</body>
</html>
