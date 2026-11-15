<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
$user = require_user();

if (isset($_GET['mode'])) {
    app_start_session();
    $_SESSION['user_mode'] = $_GET['mode'] === 'otp' ? 'otp' : 'smm';
    redirect($_SESSION['user_mode'] === 'otp' ? 'otp-dashboard.php' : '../dashboard.php');
}

$pdo = Database::getConnection();
$userId = (int) $user['id'];

$statsStmt = $pdo->prepare(
    'SELECT
        COUNT(*) AS total_purchases,
        SUM(CASE WHEN status = "waiting" THEN 1 ELSE 0 END) AS active_orders,
        SUM(CASE WHEN status = "received" THEN 1 ELSE 0 END) AS completed_orders,
        COALESCE(SUM(charge), 0) AS total_spent
     FROM otp_orders
     WHERE user_id = ?'
);
$statsStmt->execute([$userId]);
$stats = $statsStmt->fetch() ?: [];

$popularCountries = $pdo->query('SELECT * FROM otp_countries WHERE is_enabled = 1 ORDER BY service_count DESC LIMIT 6')->fetchAll();
$recentOtpOrders = $pdo->prepare('SELECT o.*, s.name AS service_name FROM otp_orders o JOIN otp_services s ON s.id = o.service_id WHERE o.user_id = ? ORDER BY o.created_at DESC LIMIT 5');
$recentOtpOrders->execute([$userId]);
$recentOrders = $recentOtpOrders->fetchAll();

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
  <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-[#0f0b1e] text-slate-100 font-['Plus_Jakarta_Sans',sans-serif] antialiased min-h-screen flex" x-data="{ mobileMenu: false }">

  <!-- Sidebar -->
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
      <a href="otp-dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-purple-600 text-white font-semibold text-sm shadow-lg shadow-purple-600/25">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        OTP Home
      </a>
      <a href="otp-countries.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-purple-200 hover:bg-purple-900/30 font-medium text-sm transition">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/></svg>
        Countries
      </a>
      <a href="otp-services.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-purple-200 hover:bg-purple-900/30 font-medium text-sm transition">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg>
        Services & Pricing
      </a>
      <a href="otp-buy.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-purple-200 hover:bg-purple-900/30 font-medium text-sm transition">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4"><path d="M12 5v14M5 12h14"/></svg>
        Buy Number
      </a>
      <a href="otp-orders.php?status=active" class="flex items-center gap-3 px-4 py-3 rounded-xl text-purple-200 hover:bg-purple-900/30 font-medium text-sm transition">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        Active OTP Orders
      </a>
      <a href="otp-orders.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-purple-200 hover:bg-purple-900/30 font-medium text-sm transition">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        OTP History
      </a>
      <a href="../transactions.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-purple-200 hover:bg-purple-900/30 font-medium text-sm transition">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
        Wallet & Transactions
      </a>
      <a href="../tickets.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-purple-200 hover:bg-purple-900/30 font-medium text-sm transition">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
        Support Tickets
      </a>
    </nav>

    <div class="pt-4 border-t border-purple-900/40">
      <a href="otp-dashboard.php?mode=smm" class="flex items-center justify-center gap-2 w-full py-3 rounded-xl bg-purple-950/80 hover:bg-purple-900 text-purple-200 border border-purple-800/50 font-bold text-xs transition shadow-inner">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        BACK TO SMM PANEL
      </a>
    </div>
  </aside>

  <!-- Main Content Wrapper -->
  <div class="flex-1 flex flex-col min-w-0">
    <!-- Topbar -->
    <header class="h-20 bg-[#16102e]/90 backdrop-blur border-b border-purple-900/40 px-6 sm:px-8 flex items-center justify-between sticky top-0 z-30">
      <div class="flex items-center gap-4">
        <button type="button" @click="mobileMenu = !mobileMenu" class="lg:hidden p-2 rounded-xl bg-purple-900/40 text-purple-200">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>
        <div>
          <h2 class="text-lg font-bold text-white">Welcome back, <?= e($user['name']) ?>!</h2>
          <p class="text-xs text-purple-300">OTP Marketplace Mode Active</p>
        </div>
      </div>

      <div class="flex items-center gap-4">
        <div class="px-4 py-2 rounded-2xl bg-purple-950/80 border border-purple-800/50 flex items-center gap-3">
          <div class="text-right">
            <p class="text-[10px] uppercase font-bold text-purple-400 tracking-wider">Wallet Balance</p>
            <p class="text-sm font-extrabold text-white font-mono"><?= e($currencySymbol) ?><?= number_format((float)$user['wallet_balance'], 2) ?></p>
          </div>
          <a href="../add-funds.php" class="w-8 h-8 rounded-xl bg-purple-600 hover:bg-purple-500 text-white flex items-center justify-center font-bold text-lg shadow-md shadow-purple-600/30 transition">+</a>
        </div>
      </div>
    </header>

    <!-- Main View -->
    <main class="flex-1 p-6 sm:p-8 space-y-8 max-w-[1400px] w-full mx-auto">
      <!-- Quick Action Banner -->
      <div class="rounded-3xl bg-gradient-to-r from-purple-900 via-indigo-900 to-purple-950 p-8 border border-purple-700/30 shadow-2xl relative overflow-hidden flex flex-col md:flex-row items-center justify-between gap-6">
        <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-purple-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="space-y-2 z-10">
          <span class="inline-block px-3 py-1 rounded-full bg-purple-500/20 border border-purple-400/30 text-purple-300 text-xs font-bold uppercase tracking-wider">Instant SMS Verification</span>
          <h2 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">Need a phone number for OTP?</h2>
          <p class="text-purple-200 text-sm max-w-xl">Choose from 150+ countries and thousands of services instantly with live SMS delivery and zero delay.</p>
        </div>
        <div class="z-10 shrink-0">
          <a href="otp-buy.php" class="px-8 py-4 rounded-2xl bg-white text-purple-950 font-extrabold text-sm hover:bg-purple-50 transition shadow-xl shadow-white/10 flex items-center gap-2.5">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="w-5 h-5"><path d="M12 5v14M5 12h14"/></svg>
            BUY NUMBER NOW
          </a>
        </div>
      </div>

      <!-- Statistics Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="rounded-2xl bg-[#16102e] border border-purple-950 p-6 space-y-2 shadow-sm">
          <p class="text-xs font-bold text-purple-400 uppercase tracking-wider">Total OTP Purchases</p>
          <p class="text-3xl font-extrabold text-white font-mono"><?= number_format((int)($stats['total_purchases'] ?? 0)) ?></p>
        </div>
        <div class="rounded-2xl bg-[#16102e] border border-purple-950 p-6 space-y-2 shadow-sm">
          <p class="text-xs font-bold text-amber-400 uppercase tracking-wider">Active Orders (Waiting)</p>
          <p class="text-3xl font-extrabold text-white font-mono"><?= number_format((int)($stats['active_orders'] ?? 0)) ?></p>
        </div>
        <div class="rounded-2xl bg-[#16102e] border border-purple-950 p-6 space-y-2 shadow-sm">
          <p class="text-xs font-bold text-emerald-400 uppercase tracking-wider">Completed Orders</p>
          <p class="text-3xl font-extrabold text-white font-mono"><?= number_format((int)($stats['completed_orders'] ?? 0)) ?></p>
        </div>
        <div class="rounded-2xl bg-[#16102e] border border-purple-950 p-6 space-y-2 shadow-sm">
          <p class="text-xs font-bold text-purple-400 uppercase tracking-wider">Total OTP Spending</p>
          <p class="text-3xl font-extrabold text-white font-mono"><?= e($currencySymbol) ?><?= number_format((float)($stats['total_spent'] ?? 0), 2) ?></p>
        </div>
      </div>

      <!-- Recent Orders & Popular Countries -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 rounded-2xl bg-[#16102e] border border-purple-950 p-6 space-y-6">
          <div class="flex items-center justify-between">
            <h3 class="font-bold text-white text-base">Recent OTP Orders</h3>
            <a href="otp-orders.php" class="text-xs font-bold text-purple-400 hover:text-purple-300">View All →</a>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
              <thead>
                <tr class="text-xs font-semibold text-purple-400 border-b border-purple-900/40">
                  <th class="pb-3">Order ID</th>
                  <th class="pb-3">Service</th>
                  <th class="pb-3">Number</th>
                  <th class="pb-3">Status</th>
                  <th class="pb-3">SMS Code</th>
                  <th class="pb-3">Time</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-purple-900/20 text-purple-100 font-mono text-xs">
                <?php if (empty($recentOrders)): ?>
                  <tr><td colspan="6" class="py-8 text-center text-purple-400 font-sans">No OTP orders found yet. Buy your first number!</td></tr>
                <?php else: ?>
                  <?php foreach ($recentOrders as $ro): ?>
                    <tr class="hover:bg-purple-900/10">
                      <td class="py-3 font-bold text-white">#<?= e($ro['order_code']) ?></td>
                      <td class="py-3 font-sans font-medium"><?= e($ro['service_name']) ?></td>
                      <td class="py-3"><?= e($ro['phone_number']) ?></td>
                      <td class="py-3 font-sans">
                        <span class="px-2.5 py-1 rounded-md text-xs font-bold <?= $ro['status'] === 'received' ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : ($ro['status'] === 'waiting' ? 'bg-amber-500/20 text-amber-300 border border-amber-500/30' : 'bg-rose-500/20 text-rose-300 border border-rose-500/30') ?>">
                          <?= ucfirst($ro['status']) ?>
                        </span>
                      </td>
                      <td class="py-3 font-extrabold text-purple-300"><?= e($ro['sms_code'] ?? 'Waiting...') ?></td>
                      <td class="py-3 text-purple-400 font-sans text-[11px]"><?= time_ago($ro['created_at']) ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <div class="rounded-2xl bg-[#16102e] border border-purple-950 p-6 space-y-6">
          <div class="flex items-center justify-between">
            <h3 class="font-bold text-white text-base">Popular Countries</h3>
            <a href="otp-countries.php" class="text-xs font-bold text-purple-400 hover:text-purple-300">View All →</a>
          </div>
          <div class="space-y-3">
            <?php foreach ($popularCountries as $pc): ?>
              <div class="flex items-center justify-between p-3 rounded-xl bg-purple-950/40 border border-purple-900/40">
                <div class="flex items-center gap-3">
                  <span class="w-8 h-8 rounded-lg bg-purple-900/50 flex items-center justify-center font-bold text-xs text-purple-200"><?= e(strtoupper(substr($pc['country_code'], 0, 2))) ?></span>
                  <div>
                    <p class="font-bold text-white text-sm"><?= e($pc['name']) ?></p>
                    <p class="text-xs text-purple-400"><?= (int)$pc['service_count'] ?> services available</p>
                  </div>
                </div>
                <a href="otp-buy.php?country=<?= e($pc['country_code']) ?>" class="px-3 py-1.5 rounded-lg bg-purple-600 hover:bg-purple-500 text-white text-xs font-semibold transition">Select</a>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </main>
  </div>
</body>
</html>
