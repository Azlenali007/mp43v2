<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
$admin = require_admin();
$pdo = Database::getConnection();

$orders = $pdo->query('SELECT o.*, u.username, s.name AS service_name FROM otp_orders o JOIN users u ON u.id = o.user_id JOIN otp_services s ON s.id = o.service_id ORDER BY o.created_at DESC LIMIT 100')->fetchAll();
$pageTitle = '4. OTP Orders';
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
      <a href="api.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-purple-200 hover:bg-purple-900/30 font-medium text-sm transition">1. API Settings</a>
      <a href="services.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-purple-200 hover:bg-purple-900/30 font-medium text-sm transition">2. Countries & Services</a>
      <a href="pricing.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-purple-200 hover:bg-purple-900/30 font-medium text-sm transition">3. Pricing</a>
      <a href="orders.php" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-purple-600 text-white font-semibold text-sm shadow-lg shadow-purple-600/25">4. OTP Orders</a>
      <a href="settings.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-purple-200 hover:bg-purple-900/30 font-medium text-sm transition">5. Settings</a>
    </nav>
    <div class="pt-4 border-t border-purple-900/40">
      <a href="../../admin/dashboard.php" class="flex items-center justify-center gap-2 w-full py-3 rounded-xl bg-purple-950/80 hover:bg-purple-900 text-purple-200 border border-purple-800/50 font-bold text-xs transition">Back to SMM Admin</a>
    </div>
  </aside>

  <div class="flex-1 flex flex-col min-w-0">
    <header class="h-20 bg-[#16102e]/90 backdrop-blur border-b border-purple-900/40 px-6 sm:px-8 flex items-center justify-between sticky top-0 z-30">
      <h2 class="text-lg font-bold text-white">4. All OTP Orders</h2>
    </header>

    <main class="flex-1 p-6 sm:p-8 space-y-6 max-w-[1400px] w-full mx-auto">
      <div class="rounded-2xl bg-[#16102e] border border-purple-950 overflow-hidden">
        <table class="w-full text-left text-sm">
          <thead>
            <tr class="text-xs font-semibold text-purple-400 border-b border-purple-900/40 bg-purple-950/20">
              <th class="p-4">Order Code</th>
              <th class="p-4">User</th>
              <th class="p-4">Service</th>
              <th class="p-4">Number</th>
              <th class="p-4">Status</th>
              <th class="p-4">SMS Code</th>
              <th class="p-4">Charge / Profit</th>
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
                  <td class="p-4 font-sans font-medium"><?= e($o['username']) ?></td>
                  <td class="p-4 font-sans"><?= e($o['service_name']) ?></td>
                  <td class="p-4"><?= e($o['phone_number']) ?></td>
                  <td class="p-4 font-sans"><span class="px-2.5 py-1 rounded-md text-xs font-bold bg-purple-500/20 text-purple-300"><?= ucfirst($o['status']) ?></span></td>
                  <td class="p-4 font-extrabold text-emerald-400"><?= e($o['sms_code'] ?? '—') ?></td>
                  <td class="p-4">₹<?= number_format((float)$o['charge'], 2) ?> <span class="text-purple-400 text-[10px]">(+₹<?= number_format((float)$o['profit'], 2) ?>)</span></td>
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
