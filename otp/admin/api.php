<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../classes/OtpProvider.php';
$admin = require_admin();
$pdo = Database::getConnection();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Invalid security token.';
    } else {
        $apiUrl = trim($_POST['api_url'] ?? '');
        $apiKey = trim($_POST['api_key'] ?? '');
        $providerName = trim($_POST['name'] ?? 'SMS-Activate');

        $pdo->beginTransaction();
        try {
            $pdo->exec('DELETE FROM otp_providers');
            $stmt = $pdo->prepare('INSERT INTO otp_providers (name, api_url, api_key, status) VALUES (?, ?, ?, "active")');
            $stmt->execute([$providerName, $apiUrl, $apiKey]);
            $pdo->commit();
            $message = 'API settings saved successfully.';
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = $e->getMessage();
        }
    }
}

$provider = OtpProvider::getActive();
$pageTitle = '1. API Settings';
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
  <link rel="stylesheet" href="../../assets/css/style.css">
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
      <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-purple-200 hover:bg-purple-900/30 font-medium text-sm transition">Dashboard</a>
      <a href="api.php" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-purple-600 text-white font-semibold text-sm shadow-lg shadow-purple-600/25">1. API Settings</a>
      <a href="services.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-purple-200 hover:bg-purple-900/30 font-medium text-sm transition">2. Countries & Services</a>
      <a href="pricing.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-purple-200 hover:bg-purple-900/30 font-medium text-sm transition">3. Pricing</a>
      <a href="orders.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-purple-200 hover:bg-purple-900/30 font-medium text-sm transition">4. OTP Orders</a>
      <a href="settings.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-purple-200 hover:bg-purple-900/30 font-medium text-sm transition">5. Settings</a>
    </nav>
    <div class="pt-4 border-t border-purple-900/40">
      <a href="../../admin/dashboard.php" class="flex items-center justify-center gap-2 w-full py-3 rounded-xl bg-purple-950/80 hover:bg-purple-900 text-purple-200 border border-purple-800/50 font-bold text-xs transition">Back to SMM Admin</a>
    </div>
  </aside>

  <div class="flex-1 flex flex-col min-w-0">
    <header class="h-20 bg-[#16102e]/90 backdrop-blur border-b border-purple-900/40 px-6 sm:px-8 flex items-center justify-between sticky top-0 z-30">
      <h2 class="text-lg font-bold text-white">1. API Settings</h2>
    </header>

    <main class="flex-1 p-6 sm:p-8 space-y-6 max-w-[900px] w-full mx-auto">
      <?php if ($message): ?>
        <div class="p-4 rounded-xl bg-emerald-500/20 border border-emerald-500/30 text-emerald-300 text-sm font-semibold"><?= e($message) ?></div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="p-4 rounded-xl bg-rose-500/20 border border-rose-500/30 text-rose-300 text-sm font-semibold"><?= e($error) ?></div>
      <?php endif; ?>

      <form method="POST" class="rounded-3xl bg-[#16102e] border border-purple-950 p-8 space-y-6 shadow-xl">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div class="space-y-4">
          <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-purple-300 mb-2">Provider Name</label>
            <input type="text" name="name" value="<?= e($provider['name'] ?? 'SMS-Activate') ?>" required class="w-full px-4 py-3 rounded-xl bg-purple-950/60 border border-purple-900 text-white font-medium focus:outline-none focus:border-purple-600">
          </div>
          <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-purple-300 mb-2">API Base URL</label>
            <input type="text" name="api_url" value="<?= e($provider['api_url'] ?? 'https://api.sms-activate.org/stg/handler.php') ?>" required class="w-full px-4 py-3 rounded-xl bg-purple-950/60 border border-purple-900 text-white font-medium focus:outline-none focus:border-purple-600">
          </div>
          <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-purple-300 mb-2">API Key / Secret Token</label>
            <input type="password" name="api_key" value="<?= e($provider['api_key'] ?? '') ?>" required class="w-full px-4 py-3 rounded-xl bg-purple-950/60 border border-purple-900 text-white font-medium focus:outline-none focus:border-purple-600">
          </div>
        </div>
        <div class="flex items-center justify-end pt-4 border-t border-purple-900/40">
          <button type="submit" class="px-8 py-3 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-sm shadow-lg transition">Save API Configuration</button>
        </div>
      </form>
    </main>
  </div>
</body>
</html>
