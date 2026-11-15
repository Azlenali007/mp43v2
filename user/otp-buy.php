<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
$user = require_user();
$pdo = Database::getConnection();

$selectedCountry = $_GET['country'] ?? '';
$selectedServiceId = isset($_GET['service_id']) ? (int)$_GET['service_id'] : 0;

$countries = $pdo->query('SELECT * FROM otp_countries WHERE is_enabled = 1 ORDER BY name ASC')->fetchAll();
$services = [];
if ($selectedCountry !== '') {
    $sStmt = $pdo->prepare('SELECT * FROM otp_services WHERE country_code = ? AND is_enabled = 1 ORDER BY price_per_number ASC');
    $sStmt->execute([$selectedCountry]);
    $services = $sStmt->fetchAll();
}

$displayCurrency = Currency::getUserDisplay($user);
$currencySymbol = (string) ($displayCurrency['symbol'] ?? '₹');
$pageTitle = 'Buy OTP Number';
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
<body class="bg-[#0f0b1e] text-slate-100 font-['Plus_Jakarta_Sans',sans-serif] antialiased min-h-screen flex" x-data="otpPurchase()">
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
      <a href="otp-buy.php" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-purple-600 text-white font-semibold text-sm shadow-lg shadow-purple-600/25">Buy Number</a>
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
      <h2 class="text-lg font-bold text-white">Buy OTP Number</h2>
      <div class="px-4 py-2 rounded-2xl bg-purple-950/80 border border-purple-800/50">
        <p class="text-[10px] uppercase font-bold text-purple-400">Wallet Balance</p>
        <p class="text-sm font-extrabold text-white font-mono"><?= e($currencySymbol) ?><span id="user-balance"><?= number_format((float)$user['wallet_balance'], 2) ?></span></p>
      </div>
    </header>

    <main class="flex-1 p-6 sm:p-8 space-y-6 max-w-[1000px] w-full mx-auto">
      <!-- Purchase Form (shown when no active order) -->
      <div x-show="!activeOrder" class="rounded-3xl bg-[#16102e] border border-purple-950 p-8 space-y-6 shadow-xl">
        <div class="space-y-2">
          <h3 class="text-xl font-extrabold text-white">Select Country & Service</h3>
          <p class="text-sm text-purple-300">Your wallet will only be debited once the number is successfully allocated.</p>
        </div>

        <div class="space-y-4">
          <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-purple-300 mb-2">1. Select Country</label>
            <select id="country-select" @change="window.location.href = 'otp-buy.php?country=' + $el.value" class="w-full px-4 py-3 rounded-xl bg-purple-950/60 border border-purple-900 text-white font-medium focus:outline-none focus:border-purple-600">
              <option value="">-- Choose Country --</option>
              <?php foreach ($countries as $c): ?>
                <option value="<?= e($c['country_code']) ?>" <?= $selectedCountry === $c['country_code'] ? 'selected' : '' ?>><?= e($c['name']) ?> (<?= e($c['country_code']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>

          <?php if ($selectedCountry !== ''): ?>
            <div>
              <label class="block text-xs font-bold uppercase tracking-wider text-purple-300 mb-2">2. Select Service</label>
              <select id="service-select" x-model="selectedServiceId" class="w-full px-4 py-3 rounded-xl bg-purple-950/60 border border-purple-900 text-white font-medium focus:outline-none focus:border-purple-600">
                <option value="">-- Choose Service --</option>
                <?php foreach ($services as $srv): ?>
                  <option value="<?= (int)$srv['id'] ?>" data-price="<?= e($srv['price_per_number']) ?>"><?= e($srv['name']) ?> — <?= e($currencySymbol) ?><?= number_format((float)$srv['price_per_number'], 2) ?> (<?= e($srv['stock_status']) ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="pt-4">
              <button type="button" @click="purchaseNumber()" :disabled="loading || !selectedServiceId" class="w-full py-4 rounded-2xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-extrabold text-base shadow-xl shadow-purple-600/30 transition disabled:opacity-50">
                <span x-show="!loading">PURCHASE NUMBER NOW</span>
                <span x-show="loading">Acquiring Number from Provider...</span>
              </button>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Live OTP Receiving Screen (shown when activeOrder is present) -->
      <div x-show="activeOrder" x-cloak class="rounded-3xl bg-[#16102e] border border-purple-900/60 p-8 space-y-6 shadow-2xl">
        <div class="flex items-center justify-between pb-6 border-b border-purple-900/40">
          <div>
            <span class="px-3 py-1 rounded-full bg-amber-500/20 text-amber-300 text-xs font-bold border border-amber-500/30">Live SMS Waiting</span>
            <h3 class="text-2xl font-extrabold text-white mt-1">Activation #<span x-text="activeOrder?.order_code"></span></h3>
          </div>
          <div class="text-right">
            <p class="text-xs text-purple-400 font-bold">Auto Refreshing in</p>
            <p class="text-lg font-mono font-extrabold text-white" x-text="countdown + 's'"></p>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="p-6 rounded-2xl bg-purple-950/50 border border-purple-900/60 space-y-2">
            <p class="text-xs font-bold text-purple-400 uppercase tracking-wider">Purchased Phone Number</p>
            <div class="flex items-center justify-between bg-purple-900/30 px-4 py-3 rounded-xl border border-purple-800/40">
              <span class="text-xl font-mono font-extrabold text-white" x-text="activeOrder?.phone_number"></span>
              <button type="button" @click="navigator.clipboard.writeText(activeOrder?.phone_number); alert('Number copied!');" class="px-3 py-1.5 rounded-lg bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold">Copy</button>
            </div>
          </div>

          <div class="p-6 rounded-2xl bg-purple-950/50 border border-purple-900/60 space-y-2">
            <p class="text-xs font-bold text-purple-400 uppercase tracking-wider">Received OTP Code</p>
            <div class="flex items-center justify-between bg-purple-900/30 px-4 py-3 rounded-xl border border-purple-800/40">
              <span class="text-xl font-mono font-extrabold text-emerald-400" x-text="activeOrder?.sms_code || 'Waiting for SMS...'"></span>
              <button type="button" x-show="activeOrder?.sms_code" @click="navigator.clipboard.writeText(activeOrder?.sms_code); alert('OTP code copied!');" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold">Copy OTP</button>
            </div>
          </div>
        </div>

        <div class="flex items-center justify-between pt-4 border-t border-purple-900/40">
          <button type="button" @click="cancelOrder()" class="px-6 py-3 rounded-xl bg-rose-500/20 hover:bg-rose-500/30 text-rose-300 border border-rose-500/30 font-bold text-xs transition">Cancel & Refund</button>
          <a href="otp-orders.php" class="px-6 py-3 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs transition">View in Order History</a>
        </div>
      </div>
    </main>
  </div>

  <script>
    function otpPurchase() {
      return {
        selectedServiceId: '',
        loading: false,
        activeOrder: null,
        countdown: 10,
        timer: null,
        pollTimer: null,
        async purchaseNumber() {
          if (!this.selectedServiceId) return;
          this.loading = true;
          try {
            let res = await fetch('../api/otp-buy.php', {
              method: 'POST',
              headers: {'Content-Type': 'application/json', 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content},
              body: JSON.stringify({ service_id: this.selectedServiceId })
            });
            let data = await res.json();
            if (data.ok) {
              this.activeOrder = data;
              this.startPolling();
            } else {
              alert(data.error || 'Failed to purchase number.');
            }
          } catch(e) {
            alert('Network error occurred.');
          } finally {
            this.loading = false;
          }
        },
        startPolling() {
          this.countdown = 10;
          this.timer = setInterval(() => {
            if (this.countdown > 0) {
              this.countdown--;
            } else {
              this.countdown = 10;
              this.checkStatus();
            }
          }, 1000);
        },
        async checkStatus() {
          if (!this.activeOrder) return;
          try {
            let res = await fetch('../api/otp-status.php?order_id=' + this.activeOrder.order_id);
            let data = await res.json();
            if (data.ok) {
              this.activeOrder.status = data.status;
              if (data.sms_code) {
                this.activeOrder.sms_code = data.sms_code;
                clearInterval(this.timer);
                alert('OTP Code Received: ' + data.sms_code);
              } else if (data.status === 'cancelled' || data.status === 'expired') {
                clearInterval(this.timer);
                alert('Order was ' + data.status + ' and refunded.');
                this.activeOrder = null;
              }
            }
          } catch(e) {}
        },
        async cancelOrder() {
          if (!this.activeOrder || !confirm('Are you sure you want to cancel this number and receive a refund?')) return;
          try {
            let res = await fetch('../api/otp-cancel.php', {
              method: 'POST',
              headers: {'Content-Type': 'application/json', 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content},
              body: JSON.stringify({ order_id: this.activeOrder.order_id })
            });
            let data = await res.json();
            if (data.ok) {
              clearInterval(this.timer);
              alert('Order cancelled and refunded.');
              window.location.reload();
            } else {
              alert(data.error || 'Failed to cancel.');
            }
          } catch(e) {
            alert('Network error.');
          }
        }
      }
    }
  </script>
</body>
</html>
