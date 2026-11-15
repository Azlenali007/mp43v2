<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
$admin = require_admin();
$pdo = Database::getConnection();

$stats = $pdo->query(
    'SELECT
        COUNT(*) AS total_orders,
        SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) AS today_orders,
        SUM(CASE WHEN status = "waiting" THEN 1 ELSE 0 END) AS active_orders,
        SUM(CASE WHEN status = "received" THEN 1 ELSE 0 END) AS completed_orders,
        COALESCE(SUM(charge), 0) AS total_sales,
        COALESCE(SUM(cost), 0) AS total_cost,
        COALESCE(SUM(profit), 0) AS total_profit
     FROM otp_orders'
)->fetch() ?: [];

$provider = OtpProvider::getActive();
$providerBalance = $provider ? (float)$provider['balance'] : 0.00;

$pageTitle = 'Manage OTP Marketplace';
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
  <!-- Admin Sidebar -->
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
      <a href="otp-dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-purple-600 text-white font-semibold text-sm shadow-lg shadow-purple-600/25">Dashboard</a>
      <a href="otp-api.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-purple-200 hover:bg-purple-900/30 font-medium text-sm transition">API Settings</a>
      <a href="otp-services.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-purple-200 hover:bg-purple-900/30 font-medium text-sm transition">Countries & Services</a>
      <a href="otp-pricing.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-purple-200 hover:bg-purple-900/30 font-medium text-sm transition">Pricing Markup</a>
      <a href="otp-orders.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-purple-200 hover:bg-purple-900/30 font-medium text-sm transition">OTP Orders</a>
      <a href="otp-settings.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-purple-200 hover:bg-purple-900/30 font-medium text-sm transition">Settings</a>
    </nav>
    <div class="pt-4 border-t border-purple-900/40">
      <a href="dashboard.php" class="flex items-center justify-center gap-2 w-full py-3 rounded-xl bg-purple-950/80 hover:bg-purple-900 text-purple-200 border border-purple-800/50 font-bold text-xs transition">Back to SMM Admin</a>
    </div>
  </aside>

  <div class="flex-1 flex flex-col min-w-0">
    <header class="h-20 bg-[#16102e]/90 backdrop-blur border-b border-purple-900/40 px-6 sm:px-8 flex items-center justify-between sticky top-0 z-30">
      <h2 class="text-lg font-bold text-white">OTP Marketplace Admin Overview</h2>
      <a href="otp-services.php" class="px-5 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs transition shadow-md shadow-purple-600/30">Sync Services & Prices</a>
    </header>

    <main class="flex-1 p-6 sm:p-8 space-y-8 max-w-[1400px] w-full mx-auto">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="rounded-2xl bg-[#16102e] border border-purple-950 p-6 space-y-2">
          <p class="text-xs font-bold text-purple-400 uppercase tracking-wider">Total OTP Orders</p>
          <p class="text-3xl font-extrabold text-white font-mono"><?= number_format((int)($stats['total_orders'] ?? 0)) ?></p>
        </div>
        <div class="rounded-2xl bg-[#16102e] border border-purple-950 p-6 space-y-2">
          <p class="text-xs font-bold text-amber-400 uppercase tracking-wider">Today's Orders</p>
          <p class="text-3xl font-extrabold text-white font-mono"><?= number_format((int)($stats['today_orders'] ?? 0)) ?></p>
        </div>
        <div class="rounded-2xl bg-[#16102e] border border-purple-950 p-6 space-y-2">
          <p class="text-xs font-bold text-emerald-400 uppercase tracking-wider">Total Profit</p>
          <p class="text-3xl font-extrabold text-white font-mono">₹<?= number_format((float)($stats['total_profit'] ?? 0), 2) ?></p>
        </div>
        <div class="rounded-2xl bg-[#16102e] border border-purple-950 p-6 space-y-2">
          <p class="text-xs font-bold text-purple-400 uppercase tracking-wider">Provider Balance</p>
          <p class="text-3xl font-extrabold text-white font-mono">$<?= number_format($providerBalance, 2) ?></p>
        </div>
      </div>

      <div class="rounded-2xl bg-[#16102e] border border-purple-950 p-8 space-y-6">
        <h3 class="font-bold text-white text-lg">Quick Management Links</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <a href="otp-api.php" class="p-6 rounded-2xl bg-purple-950/40 border border-purple-900/60 hover:border-purple-600 transition space-y-2">
            <h4 class="font-bold text-white">1. API Configuration</h4>
            <p class="text-xs text-purple-300">Manage provider credentials, base URL and test API connection.</p>
          </a>
          <a href="otp-services.php" class="p-6 rounded-2xl bg-purple-950/40 border border-purple-900/60 hover:border-purple-600 transition space-y-2">
            <h4 class="font-bold text-white">2. Countries & Services Sync</h4>
            <p class="text-xs text-purple-300">Import and sync supported countries and SMS verification services.</p>
          </a>
          <a href="otp-orders.php" class="p-6 rounded-2xl bg-purple-950/40 border border-purple-900/60 hover:border-purple-600 transition space-y-2">
            <h4 class="font-bold text-white">3. OTP Orders & Refunds</h4>
            <p class="text-xs text-purple-300">Monitor all customer orders, activation logs, and process refunds.</p>
          </a>
        </div>
      </div>
    </main>
  </div>
</body>
</html>
