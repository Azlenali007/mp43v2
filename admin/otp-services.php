<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
$admin = require_admin();
$pdo = Database::getConnection();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sync_services'])) {
    if (!verify_csrf()) {
        $error = 'Invalid security token.';
    } else {
        $res = OtpProvider::fetchCountriesAndServices();
        if ($res['ok']) {
            $message = 'Successfully synced ' . $res['updated_count'] . ' services and countries from provider API.';
        } else {
            $error = $res['error'] ?? 'Sync failed.';
        }
    }
}

$services = $pdo->query('SELECT s.*, c.name AS country_name FROM otp_services s LEFT JOIN otp_countries c ON c.country_code = s.country_code ORDER BY s.id DESC LIMIT 100')->fetchAll();
$pageTitle = 'OTP Countries & Services';
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
      <a href="otp-services.php" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-purple-600 text-white font-semibold text-sm shadow-lg shadow-purple-600/25">Countries & Services</a>
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
      <h2 class="text-lg font-bold text-white">Import & Sync OTP Services</h2>
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="sync_services" value="1">
        <button type="submit" class="px-5 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs transition shadow-md shadow-purple-600/30">Fetch & Sync from API</button>
      </form>
    </header>

    <main class="flex-1 p-6 sm:p-8 space-y-6 max-w-[1400px] w-full mx-auto">
      <?php if ($message): ?>
        <div class="p-4 rounded-xl bg-emerald-500/20 border border-emerald-500/30 text-emerald-300 text-sm font-semibold"><?= e($message) ?></div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="p-4 rounded-xl bg-rose-500/20 border border-rose-500/30 text-rose-300 text-sm font-semibold"><?= e($error) ?></div>
      <?php endif; ?>

      <div class="rounded-2xl bg-[#16102e] border border-purple-950 overflow-hidden">
        <table class="w-full text-left text-sm">
          <thead>
            <tr class="text-xs font-semibold text-purple-400 border-b border-purple-900/40 bg-purple-950/20">
              <th class="p-4">Service ID</th>
              <th class="p-4">Name</th>
              <th class="p-4">Country</th>
              <th class="p-4">Provider Cost</th>
              <th class="p-4">Selling Price</th>
              <th class="p-4">Stock</th>
              <th class="p-4">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-purple-900/20 text-purple-100 font-mono text-xs">
            <?php if (empty($services)): ?>
              <tr><td colspan="7" class="py-12 text-center text-purple-400 font-sans">No services synced yet. Click "Fetch & Sync from API".</td></tr>
            <?php else: ?>
              <?php foreach ($services as $s): ?>
                <tr class="hover:bg-purple-900/10">
                  <td class="p-4 font-bold text-white">#<?= e($s['external_service_id']) ?></td>
                  <td class="p-4 font-sans font-medium"><?= e($s['name']) ?></td>
                  <td class="p-4 font-sans"><?= e($s['country_name'] ?? $s['country_code']) ?></td>
                  <td class="p-4">₹<?= number_format((float)$s['original_provider_rate'], 2) ?></td>
                  <td class="p-4 font-extrabold text-white">₹<?= number_format((float)$s['price_per_number'], 2) ?></td>
                  <td class="p-4 font-sans"><?= e($s['stock_status']) ?></td>
                  <td class="p-4 font-sans">
                    <span class="px-2.5 py-1 rounded-md text-xs font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">Active</span>
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
