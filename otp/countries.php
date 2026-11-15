<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
$user = require_user();
$pdo = Database::getConnection();

$countries = $pdo->query('SELECT * FROM otp_countries WHERE is_enabled = 1 ORDER BY name ASC')->fetchAll();
$displayCurrency = Currency::getUserDisplay($user);
$currencySymbol = (string) ($displayCurrency['symbol'] ?? '₹');
$pageTitle = 'OTP Countries';
?>
<!DOCTYPE html>
<html lang="en" class="theme-classic">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?> — OTP Marketplace</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-[#0f0b1e] text-slate-100 font-['Plus_Jakarta_Sans',sans-serif] antialiased min-h-screen flex" x-data="{ search: '' }">
  <aside class="hidden lg:flex flex-col w-72 bg-[#16102e] border-r border-purple-900/40 p-6 shrink-0">
    <div class="flex items-center gap-3 px-2 mb-8">
      <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-purple-600 to-indigo-500 flex items-center justify-center text-white shadow-lg shadow-purple-500/20">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
      </div>
      <div>
        <h1 class="font-extrabold text-white text-base tracking-tight">OTP MARKETPLACE</h1>
        <p class="text-xs text-purple-300 font-mono">Live SMS Verification</p>
      </div>
    </div>
    <nav class="space-y-1.5 flex-1">
      <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-purple-200 hover:bg-purple-900/30 font-medium text-sm transition">OTP Home</a>
      <a href="countries.php" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-purple-600 text-white font-semibold text-sm shadow-lg shadow-purple-600/25">Countries</a>
      <a href="services.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-purple-200 hover:bg-purple-900/30 font-medium text-sm transition">Services & Pricing</a>
      <a href="buy.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-purple-200 hover:bg-purple-900/30 font-medium text-sm transition">Buy Number</a>
      <a href="orders.php?status=active" class="flex items-center gap-3 px-4 py-3 rounded-xl text-purple-200 hover:bg-purple-900/30 font-medium text-sm transition">Active OTP Orders</a>
      <a href="orders.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-purple-200 hover:bg-purple-900/30 font-medium text-sm transition">OTP History</a>
    </nav>
    <div class="pt-4 border-t border-purple-900/40">
      <a href="../dashboard.php" class="flex items-center justify-center gap-2 w-full py-3 rounded-xl bg-purple-950/80 hover:bg-purple-900 text-purple-200 border border-purple-800/50 font-bold text-xs transition">BACK TO SMM PANEL</a>
    </div>
  </aside>

  <div class="flex-1 flex flex-col min-w-0">
    <header class="h-20 bg-[#16102e]/90 backdrop-blur border-b border-purple-900/40 px-6 sm:px-8 flex items-center justify-between sticky top-0 z-30">
      <h2 class="text-lg font-bold text-white">Supported Countries</h2>
      <div class="px-4 py-2 rounded-2xl bg-purple-950/80 border border-purple-800/50">
        <p class="text-[10px] uppercase font-bold text-purple-400">Balance</p>
        <p class="text-sm font-extrabold text-white font-mono"><?= e($currencySymbol) ?><?= number_format((float)$user['wallet_balance'], 2) ?></p>
      </div>
    </header>

    <main class="flex-1 p-6 sm:p-8 space-y-6 max-w-[1400px] w-full mx-auto">
      <div class="flex items-center justify-between gap-4 flex-wrap">
        <p class="text-sm text-purple-200">Select a country to instantly browse available services.</p>
        <input type="text" x-model="search" placeholder="Search countries..." class="px-4 py-2.5 rounded-xl bg-[#16102e] border border-purple-900 text-sm text-white placeholder-purple-400 focus:outline-none focus:border-purple-600 w-full sm:w-72">
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        <?php foreach ($countries as $c): ?>
          <div x-show="search === '' || '<?= strtolower(e($c['name'])) ?>'.includes(search.toLowerCase())" class="rounded-2xl bg-[#16102e] border border-purple-950 p-5 flex items-center justify-between hover:border-purple-600 transition group">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-purple-900/40 border border-purple-800/40 flex items-center justify-center font-bold text-sm text-purple-200">
                <?= e(strtoupper(substr($c['country_code'], 0, 2))) ?>
              </div>
              <div>
                <h4 class="font-bold text-white text-sm group-hover:text-purple-300 transition"><?= e($c['name']) ?></h4>
                <p class="text-xs text-purple-400"><?= (int)$c['service_count'] ?> services</p>
              </div>
            </div>
            <a href="buy.php?country=<?= e($c['country_code']) ?>" class="px-3 py-1.5 rounded-lg bg-purple-600 hover:bg-purple-500 text-white text-xs font-semibold transition">Select</a>
          </div>
        <?php endforeach; ?>
      </div>
    </main>
  </div>
</body>
</html>
