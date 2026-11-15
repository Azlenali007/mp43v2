<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
$user = require_user();
$pdo = Database::getConnection();
$userId = (int) $user['id'];

$statusFilter = $_GET['status'] ?? '';
$sql = 'SELECT o.*, s.name AS service_name, c.name AS country_name FROM otp_orders o JOIN otp_services s ON s.id = o.service_id LEFT JOIN otp_countries c ON c.country_code = o.country_code WHERE o.user_id = ?';
$params = [$userId];

if ($statusFilter === 'active' || $statusFilter === 'waiting') {
    $sql .= ' AND o.status = "waiting"';
} elseif ($statusFilter !== '') {
    $sql .= ' AND o.status = ?';
    $params[] = $statusFilter;
}
$sql .= ' ORDER BY o.created_at DESC LIMIT 100';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$displayCurrency = Currency::getUserDisplay($user);
$currencySymbol = (string) ($displayCurrency['symbol'] ?? '₹');
$pageTitle = 'OTP Order History';
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
      <a href="otp-dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-purple-200 hover:bg-purple-900/30 font-medium text-sm transition">OTP Home</a>
      <a href="otp-countries.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-purple-200 hover:bg-purple-900/30 font-medium text-sm transition">Countries</a>
      <a href="otp-services.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-purple-200 hover:bg-purple-900/30 font-medium text-sm transition">Services & Pricing</a>
      <a href="otp-buy.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-purple-200 hover:bg-purple-900/30 font-medium text-sm transition">Buy Number</a>
      <a href="otp-orders.php?status=active" class="flex items-center gap-3 px-4 py-3 rounded-xl text-purple-200 hover:bg-purple-900/30 font-medium text-sm transition">Active OTP Orders</a>
      <a href="otp-orders.php" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-purple-600 text-white font-semibold text-sm shadow-lg shadow-purple-600/25">OTP History</a>
      <a href="../transactions.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-purple-200 hover:bg-purple-900/30 font-medium text-sm transition">Wallet & Transactions</a>
    </nav>
    <div class="pt-4 border-t border-purple-900/40">
      <a href="otp-dashboard.php?mode=smm" class="flex items-center justify-center gap-2 w-full py-3 rounded-xl bg-purple-950/80 hover:bg-purple-900 text-purple-200 border border-purple-800/50 font-bold text-xs transition">BACK TO SMM PANEL</a>
    </div>
  </aside>

  <div class="flex-1 flex flex-col min-w-0">
    <header class="h-20 bg-[#16102e]/90 backdrop-blur border-b border-purple-900/40 px-6 sm:px-8 flex items-center justify-between sticky top-0 z-30">
      <h2 class="text-lg font-bold text-white">OTP Order History</h2>
      <div class="px-4 py-2 rounded-2xl bg-purple-950/80 border border-purple-800/50">
        <p class="text-[10px] uppercase font-bold text-purple-400">Balance</p>
        <p class="text-sm font-extrabold text-white font-mono"><?= e($currencySymbol) ?><?= number_format((float)$user['wallet_balance'], 2) ?></p>
      </div>
    </header>

    <main class="flex-1 p-6 sm:p-8 space-y-6 max-w-[1400px] w-full mx-auto">
      <div class="flex items-center gap-2 flex-wrap">
        <a href="otp-orders.php" class="px-4 py-2 rounded-xl text-xs font-bold <?= $statusFilter === '' ? 'bg-purple-600 text-white' : 'bg-purple-950/60 text-purple-300 hover:bg-purple-900/40' ?>">All</a>
        <a href="otp-orders.php?status=waiting" class="px-4 py-2 rounded-xl text-xs font-bold <?= $statusFilter === 'waiting' ? 'bg-purple-600 text-white' : 'bg-purple-950/60 text-purple-300 hover:bg-purple-900/40' ?>">Waiting</a>
        <a href="otp-orders.php?status=received" class="px-4 py-2 rounded-xl text-xs font-bold <?= $statusFilter === 'received' ? 'bg-purple-600 text-white' : 'bg-purple-950/60 text-purple-300 hover:bg-purple-900/40' ?>">Completed</a>
        <a href="otp-orders.php?status=cancelled" class="px-4 py-2 rounded-xl text-xs font-bold <?= $statusFilter === 'cancelled' ? 'bg-purple-600 text-white' : 'bg-purple-950/60 text-purple-300 hover:bg-purple-900/40' ?>">Cancelled</a>
        <a href="otp-orders.php?status=expired" class="px-4 py-2 rounded-xl text-xs font-bold <?= $statusFilter === 'expired' ? 'bg-purple-600 text-white' : 'bg-purple-950/60 text-purple-300 hover:bg-purple-900/40' ?>">Expired</a>
      </div>

      <div class="rounded-2xl bg-[#16102e] border border-purple-950 overflow-hidden">
        <table class="w-full text-left text-sm">
          <thead>
            <tr class="text-xs font-semibold text-purple-400 border-b border-purple-900/40 bg-purple-950/20">
              <th class="p-4">Order ID</th>
              <th class="p-4">Country</th>
              <th class="p-4">Service</th>
              <th class="p-4">Phone Number</th>
              <th class="p-4">Status</th>
              <th class="p-4">SMS Code</th>
              <th class="p-4">Charge</th>
              <th class="p-4">Date</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-purple-900/20 text-purple-100 font-mono text-xs">
            <?php if (empty($orders)): ?>
              <tr><td colspan="8" class="py-12 text-center text-purple-400 font-sans">No OTP orders found.</td></tr>
            <?php else: ?>
              <?php foreach ($orders as $o): ?>
                <tr class="hover:bg-purple-900/10">
                  <td class="p-4 font-bold text-white">#<?= e($o['order_code']) ?></td>
                  <td class="p-4 font-sans"><?= e($o['country_name'] ?? $o['country_code']) ?></td>
                  <td class="p-4 font-sans font-medium"><?= e($o['service_name']) ?></td>
                  <td class="p-4"><?= e($o['phone_number']) ?></td>
                  <td class="p-4 font-sans">
                    <span class="px-2.5 py-1 rounded-md text-xs font-bold <?= $o['status'] === 'received' ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : ($o['status'] === 'waiting' ? 'bg-amber-500/20 text-amber-300 border border-amber-500/30' : 'bg-rose-500/20 text-rose-300 border border-rose-500/30') ?>">
                      <?= ucfirst($o['status']) ?>
                    </span>
                  </td>
                  <td class="p-4 font-extrabold text-purple-300"><?= e($o['sms_code'] ?? '—') ?></td>
                  <td class="p-4 font-extrabold text-white"><?= e($currencySymbol) ?><?= number_format((float)$o['charge'], 2) ?></td>
                  <td class="p-4 text-purple-400 font-sans text-[11px]"><?= e($o['created_at']) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </main>
  </div>
</body>
</html>
