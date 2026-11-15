<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/classes/OtpProvider.php';
$user = require_user();

$pdo = Database::getConnection();
$userId = (int) $user['id'];

$stats = $pdo->prepare(
    'SELECT
        COUNT(*) AS total_purchases,
        SUM(CASE WHEN status = "waiting" THEN 1 ELSE 0 END) AS active_orders,
        SUM(CASE WHEN status = "received" THEN 1 ELSE 0 END) AS completed_orders,
        COALESCE(SUM(charge), 0) AS total_spent
     FROM otp_orders
     WHERE user_id = ?'
);
$stats->execute([$userId]);
$userStats = $stats->fetch() ?: [];

$popularCountries = $pdo->query('SELECT * FROM otp_countries WHERE is_enabled = 1 ORDER BY service_count DESC LIMIT 6')->fetchAll();
$recentOrdersStmt = $pdo->prepare('SELECT o.*, s.name AS service_name FROM otp_orders o JOIN otp_services s ON s.id = o.service_id WHERE o.user_id = ? ORDER BY o.created_at DESC LIMIT 5');
$recentOrdersStmt->execute([$userId]);
$recentOrders = $recentOrdersStmt->fetchAll();

$displayCurrency = Currency::getUserDisplay($user);
$currencySymbol = (string) ($displayCurrency['symbol'] ?? '₹');
$pageTitle = 'OTP Marketplace Dashboard';
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
<body class="bg-[#0f0b1e] text-slate-100 font-['Plus_Jakarta_Sans',sans-serif] antialiased min-h-screen flex">
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
      <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-purple-600 text-white font-semibold text-sm shadow-lg shadow-purple-600/25">OTP Home</a>
      <a href="countries.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-purple-200 hover:bg-purple-900/30 font-medium text-sm transition">Countries</a>
      <a href="services.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-purple-200 hover:bg-purple-900/30 font-medium text-sm transition">Services & Pricing</a>
      <a href="buy.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-purple-200 hover:bg-purple-900/30 font-medium text-sm transition">Buy Number</a>
      <a href="orders.php?status=active" class="flex items-center gap-3 px-4 py-3 rounded-xl text-purple-200 hover:bg-purple-900/30 font-medium text-sm transition">Active OTP Orders</a>
      <a href="orders.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-purple-200 hover:bg-purple-900/30 font-medium text-sm transition">OTP History</a>
      <a href="../transactions.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-purple-200 hover:bg-purple-900/30 font-medium text-sm transition">Wallet & Transactions</a>
    </nav>
    <div class="pt-4 border-t border-purple-900/40">
      <a href="../dashboard.php" class="flex items-center justify-center gap-2 w-full py-3 rounded-xl bg-purple-950/80 hover:bg-purple-900 text-purple-200 border border-purple-800/50 font-bold text-xs transition">BACK TO SMM PANEL</a>
    </div>
  </aside>

  <div class="flex-1 flex flex-col min-w-0">
    <header class="h-20 bg-[#16102e]/90 backdrop-blur border-b border-purple-900/40 px-6 sm:px-8 flex items-center justify-between sticky top-0 z-30">
      <div>
        <h2 class="text-lg font-bold text-white">Welcome back, <?= e($user['name']) ?>!</h2>
        <p class="text-xs text-purple-300">OTP Marketplace Mode</p>
      </div>
      <div class="px-4 py-2 rounded-2xl bg-purple-950/80 border border-purple-800/50 flex items-center gap-3">
        <div class="text-right">
          <p class="text-[10px] uppercase font-bold text-purple-400">Wallet Balance</p>
          <p class="text-sm font-extrabold text-white font-mono"><?= e($currencySymbol) ?><?= number_format((float)$user['wallet_balance'], 2) ?></p>
        </div>
        <a href="../add-funds.php" class="w-8 h-8 rounded-xl bg-purple-600 hover:bg-purple-500 text-white flex items-center justify-center font-bold text-lg">+</a>
      </div>
    </header>

    <main class="flex-1 p-6 sm:p-8 space-y-8 max-w-[1400px] w-full mx-auto">
      <div class="rounded-3xl bg-gradient-to-r from-purple-900 via-indigo-900 to-purple-950 p-8 border border-purple-700/30 shadow-2xl flex flex-col md:flex-row items-center justify-between gap-6">
        <div class="space-y-2">
          <span class="px-3 py-1 rounded-full bg-purple-500/20 text-purple-300 text-xs font-bold uppercase">Instant SMS Verification</span>
          <h2 class="text-2xl sm:text-3xl font-extrabold text-white">Need a phone number for OTP?</h2>
          <p class="text-purple-200 text-sm max-w-xl">Choose from global countries and services instantly with live SMS delivery.</p>
        </div>
        <a href="buy.php" class="px-8 py-4 rounded-2xl bg-white text-purple-950 font-extrabold text-sm hover:bg-purple-50 transition shadow-xl shrink-0 flex items-center gap-2">BUY NUMBER NOW</a>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="rounded-2xl bg-[#16102e] border border-purple-950 p-6 space-y-2">
          <p class="text-xs font-bold text-purple-400 uppercase">Total Purchases</p>
          <p class="text-3xl font-extrabold text-white font-mono"><?= number_format((int)($userStats['total_purchases'] ?? 0)) ?></p>
        </div>
        <div class="rounded-2xl bg-[#16102e] border border-purple-950 p-6 space-y-2">
          <p class="text-xs font-bold text-amber-400 uppercase">Active Orders</p>
          <p class="text-3xl font-extrabold text-white font-mono"><?= number_format((int)($userStats['active_orders'] ?? 0)) ?></p>
        </div>
        <div class="rounded-2xl bg-[#16102e] border border-purple-950 p-6 space-y-2">
          <p class="text-xs font-bold text-emerald-400 uppercase">Completed Orders</p>
          <p class="text-3xl font-extrabold text-white font-mono"><?= number_format((int)($userStats['completed_orders'] ?? 0)) ?></p>
        </div>
        <div class="rounded-2xl bg-[#16102e] border border-purple-950 p-6 space-y-2">
          <p class="text-xs font-bold text-purple-400 uppercase">Total Spending</p>
          <p class="text-3xl font-extrabold text-white font-mono"><?= e($currencySymbol) ?><?= number_format((float)($userStats['total_spent'] ?? 0), 2) ?></p>
        </div>
      </div>
    </main>
  </div>
</body>
</html>
