<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
$admin = require_admin();

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verify_csrf()) {
        set_otp_setting('otp_enabled', isset($_POST['otp_enabled']) ? '1' : '0');
        set_otp_setting('otp_public_enabled', isset($_POST['otp_public_enabled']) ? '1' : '0');
        $message = 'OTP Marketplace settings updated successfully.';
    }
}

$otpEnabled = is_otp_enabled();
$publicEnabled = is_otp_public_enabled();
$pageTitle = 'OTP Marketplace Settings';
?>
<!DOCTYPE html>
<html lang="en" class="theme-classic">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?> — Admin OTP Manager</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-[#0f0b1e] text-slate-100 font-['Plus_Jakarta_Sans',sans-serif] antialiased min-h-screen flex">
  <aside class="hidden lg:flex flex-col w-72 bg-[#16102e] border-r border-purple-900/40 p-6 shrink-0">
    <div class="flex items-center gap-3 px-2 mb-8">
      <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-purple-600 to-indigo-500 flex items-center justify-center text-white shadow-lg shadow-purple-500/20">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
      </div>
      <div>
        <h1 class="font-extrabold text-white text-base tracking-tight">OTP MANAGER</h1>
        <p class="text-xs text-purple-300 font-mono">Admin Control Center</p>
      </div>
    </div>
    <nav class="space-y-1.5 flex-1">
      <a href="otp-dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-purple-200 hover:bg-purple-900/30 font-medium text-sm transition">Dashboard</a>
      <a href="otp-api.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-purple-200 hover:bg-purple-900/30 font-medium text-sm transition">API Settings</a>
      <a href="otp-services.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-purple-200 hover:bg-purple-900/30 font-medium text-sm transition">Countries & Services</a>
      <a href="otp-pricing.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-purple-200 hover:bg-purple-900/30 font-medium text-sm transition">Pricing Markup</a>
      <a href="otp-orders.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-purple-200 hover:bg-purple-900/30 font-medium text-sm transition">OTP Orders</a>
      <a href="otp-settings.php" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-purple-600 text-white font-semibold text-sm shadow-lg shadow-purple-600/25">Settings</a>
    </nav>
    <div class="pt-4 border-t border-purple-900/40">
      <a href="dashboard.php" class="flex items-center justify-center gap-2 w-full py-3 rounded-xl bg-purple-950/80 hover:bg-purple-900 text-purple-200 border border-purple-800/50 font-bold text-xs transition">Back to SMM Admin</a>
    </div>
  </aside>

  <div class="flex-1 flex flex-col min-w-0">
    <header class="h-20 bg-[#16102e]/90 backdrop-blur border-b border-purple-900/40 px-6 sm:px-8 flex items-center justify-between sticky top-0 z-30">
      <h2 class="text-lg font-bold text-white">OTP Marketplace Settings</h2>
    </header>

    <main class="flex-1 p-6 sm:p-8 space-y-6 max-w-[900px] w-full mx-auto">
      <?php if ($message): ?>
        <div class="p-4 rounded-xl bg-emerald-500/20 border border-emerald-500/30 text-emerald-300 text-sm font-semibold"><?= e($message) ?></div>
      <?php endif; ?>

      <form method="POST" class="rounded-3xl bg-[#16102e] border border-purple-950 p-8 space-y-6 shadow-xl">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div class="space-y-6">
          <label class="flex items-center gap-3 cursor-pointer">
            <input type="checkbox" name="otp_enabled" value="1" <?= $otpEnabled ? 'checked' : '' ?> class="w-5 h-5 rounded border-purple-900 bg-purple-950 text-purple-600 focus:ring-0">
            <div>
              <p class="font-bold text-white text-sm">Enable OTP Marketplace</p>
              <p class="text-xs text-purple-300">Allow users to access OTP marketplace features and purchase numbers.</p>
            </div>
          </label>

          <label class="flex items-center gap-3 cursor-pointer">
            <input type="checkbox" name="otp_public_enabled" value="1" <?= $publicEnabled ? 'checked' : '' ?> class="w-5 h-5 rounded border-purple-900 bg-purple-950 text-purple-600 focus:ring-0">
            <div>
              <p class="font-bold text-white text-sm">Enable Public Landing Page Switcher</p>
              <p class="text-xs text-purple-300">Display OTP Marketplace option and hero CTA on the public landing page navbar.</p>
            </div>
          </label>
        </div>
        <div class="flex items-center justify-end pt-4 border-t border-purple-900/40">
          <button type="submit" class="px-8 py-3 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-sm shadow-lg shadow-purple-600/30 transition">Save Settings</button>
        </div>
      </form>
    </main>
  </div>
</body>
</html>
