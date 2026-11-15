<?php
declare(strict_types=1);
if (!defined('APP_ROOT')) {
    http_response_code(403);
    exit('Forbidden');
}
$pageTitle = $pageTitle ?? 'Register';
$rp = $rootPrefix ?? app_root_prefix();
?>
<!DOCTYPE html>
<html lang="en" class="theme-swipex">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register — <?= e($siteName ?? 'SMM Panel') ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="<?= e(Theme::assetUrl('css/swipex.css')) ?>">
</head>
<body class="bg-[#F8FAFC] text-[#0F172A] antialiased min-h-screen flex items-center justify-center p-4">
  <div class="w-full max-w-md bg-white rounded-3xl p-8 shadow-xl border border-slate-200 space-y-6">
    <div class="text-center space-y-2">
      <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white flex items-center justify-center mx-auto shadow-lg shadow-blue-500/30">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="w-6 h-6"><path d="M23 6l-9.5 9.5-5-5L1 18"/><path d="M17 6h6v6"/></svg>
      </div>
      <h1 class="text-2xl font-black tracking-tight text-slate-900">Create Account</h1>
      <p class="text-xs text-slate-500">Join SwipeX SMM Panel today</p>
    </div>

    <?php $notifMode = 'flash'; require __DIR__ . '/components/notifications.php'; ?>

    <form method="POST" action="<?= e($rp) ?>register.php" class="space-y-4">
      <input type="hidden" name="csrf_token" value="<?= e($csrfToken ?? csrf_token()) ?>">
      <div>
        <label class="block text-xs font-bold text-slate-700 mb-1.5">Full Name</label>
        <input type="text" name="name" required class="w-full px-4 py-3 rounded-2xl bg-slate-50 border border-slate-200 text-xs font-bold text-slate-800 focus:outline-none focus:border-blue-600">
      </div>
      <div>
        <label class="block text-xs font-bold text-slate-700 mb-1.5">Email Address</label>
        <input type="email" name="email" required class="w-full px-4 py-3 rounded-2xl bg-slate-50 border border-slate-200 text-xs font-bold text-slate-800 focus:outline-none focus:border-blue-600">
      </div>
      <div>
        <label class="block text-xs font-bold text-slate-700 mb-1.5">Password</label>
        <input type="password" name="password" required class="w-full px-4 py-3 rounded-2xl bg-slate-50 border border-slate-200 text-xs font-bold text-slate-800 focus:outline-none focus:border-blue-600">
      </div>
      <button type="submit" class="w-full py-4 rounded-2xl bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs shadow-lg shadow-blue-500/25 transition">
        Create Account
      </button>
    </form>

    <div class="text-center text-xs text-slate-500 pt-2">
      Already have an account? <a href="<?= e($rp) ?>login.php" class="font-bold text-blue-600 hover:underline">Sign In</a>
    </div>
  </div>
</body>
</html>
