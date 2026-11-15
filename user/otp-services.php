<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
$user = require_user();
$pdo = Database::getConnection();

$countryFilter = $_GET['country'] ?? '';
$searchQuery = $_GET['search'] ?? '';

$sql = 'SELECT s.*, c.name AS country_name FROM otp_services s LEFT JOIN otp_countries c ON c.country_code = s.country_code WHERE s.is_enabled = 1';
$params = [];
if ($countryFilter !== '') {
    $sql .= ' AND s.country_code = ?';
    $params[] = $countryFilter;
}
if ($searchQuery !== '') {
    $sql .= ' AND s.name LIKE ?';
    $params[] = '%' . $searchQuery . '%';
}
$sql .= ' ORDER BY s.price_per_number ASC LIMIT 100';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$services = $stmt->fetchAll();

$countries = $pdo->query('SELECT country_code, name FROM otp_countries WHERE is_enabled = 1 ORDER BY name ASC')->fetchAll();
$displayCurrency = Currency::getUserDisplay($user);
$currencySymbol = (string) ($displayCurrency['symbol'] ?? '₹');
$pageTitle = 'OTP Services & Pricing';
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
      <a href="otp-services.php" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-purple-600 text-white font-semibold text-sm shadow-lg shadow-purple-600/25">Services & Pricing</a>
      <a href="otp-buy.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-purple-200 hover:bg-purple-900/30 font-medium text-sm transition">Buy Number</a>
      <a href="otp-orders.php?status=active" class="flex items-center gap-3 px-4 py-3 rounded-xl text-purple-200 hover:bg-purple-900/30 font-medium text-sm transition">Active OTP Orders</a>
      <a href="otp-orders.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-purple-200 hover:bg-purple-900/30 font-medium text-sm transition">OTP History</a>
      <a href="../transactions.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-purple-200 hover:bg-purple-900/30 font-medium text-sm transition">Wallet & Transactions</a>
    </nav>
    <div class="pt-4 border-t border-purple-900/40">
      <a href="otp-dashboard.php?mode=smm" class="flex items-center justify-center gap-2 w-full py-3 rounded-xl bg-purple-950/80 hover:bg-purple-900 text-purple-200 border border-purple-800/50 font-bold text-xs transition">BACK TO SMM PANEL</a>
    </div>
  </aside>

  <div class="flex-1 flex flex-col min-w-0">
    <header class="h-20 bg-[#16102e]/90 backdrop-blur border-b border-purple-900/40 px-6 sm:px-8 flex items-center justify-between sticky top-0 z-30">
      <h2 class="text-lg font-bold text-white">Services & Pricing</h2>
      <div class="px-4 py-2 rounded-2xl bg-purple-950/80 border border-purple-800/50">
        <p class="text-[10px] uppercase font-bold text-purple-400">Balance</p>
        <p class="text-sm font-extrabold text-white font-mono"><?= e($currencySymbol) ?><?= number_format((float)$user['wallet_balance'], 2) ?></p>
      </div>
    </header>

    <main class="flex-1 p-6 sm:p-8 space-y-6 max-w-[1400px] w-full mx-auto">
      <form method="GET" class="flex items-center gap-4 flex-wrap">
        <input type="text" name="search" value="<?= e($searchQuery) ?>" placeholder="Search service name..." class="px-4 py-2.5 rounded-xl bg-[#16102e] border border-purple-900 text-sm text-white placeholder-purple-400 focus:outline-none focus:border-purple-600 flex-1 min-w-[240px]">
        <select name="country" class="px-4 py-2.5 rounded-xl bg-[#16102e] border border-purple-900 text-sm text-white focus:outline-none focus:border-purple-600">
          <option value="">All Countries</option>
          <?php foreach ($countries as $c): ?>
            <option value="<?= e($c['country_code']) ?>" <?= $countryFilter === $c['country_code'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="px-6 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-semibold text-sm transition">Filter</button>
      </form>

      <div class="rounded-2xl bg-[#16102e] border border-purple-950 overflow-hidden">
        <table class="w-full text-left text-sm">
          <thead>
            <tr class="text-xs font-semibold text-purple-400 border-b border-purple-900/40 bg-purple-950/20">
              <th class="p-4">Service</th>
              <th class="p-4">Country</th>
              <th class="p-4">Price / Number</th>
              <th class="p-4">Stock Status</th>
              <th class="p-4 text-right">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-purple-900/20 text-purple-100">
            <?php if (empty($services)): ?>
              <tr><td colspan="5" class="py-12 text-center text-purple-400">No services found. Try importing services in the admin panel.</td></tr>
            <?php else: ?>
              <?php foreach ($services as $s): ?>
                <tr class="hover:bg-purple-900/10">
                  <td class="p-4 font-bold text-white"><?= e($s['name']) ?></td>
                  <td class="p-4 text-purple-300"><?= e($s['country_name'] ?? $s['country_code']) ?></td>
                  <td class="p-4 font-mono font-extrabold text-purple-200"><?= e($currencySymbol) ?><?= number_format((float)$s['price_per_number'], 2) ?></td>
                  <td class="p-4">
                    <span class="px-2.5 py-1 rounded-md text-xs font-semibold bg-purple-500/20 text-purple-300 border border-purple-500/30"><?= e($s['stock_status']) ?></span>
                  </td>
                  <td class="p-4 text-right">
                    <a href="otp-buy.php?service_id=<?= (int)$s['id'] ?>" class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold transition shadow-md shadow-purple-600/30">Buy Number</a>
                  </td>
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
