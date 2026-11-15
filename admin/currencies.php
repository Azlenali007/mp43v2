<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
$admin = require_admin();

$pdo = Database::getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();
    $action = $_POST['action'] ?? '';

    if ($action === 'sync_rates') {
        $res = Currency::fetchAndUpdateRates('manual', (int) $admin['id']);
        set_flash($res['ok'] ? 'success' : 'error', $res['ok'] ? $res['message'] : $res['error']);
    } elseif ($action === 'save_api_config') {
        $provider = in_array($_POST['exchange_api_provider'] ?? '', ['open_er_api', 'exchangerate_api_v6', 'custom'], true)
            ? (string) $_POST['exchange_api_provider'] : 'open_er_api';
        $apiKey = trim((string) ($_POST['exchange_api_key'] ?? ''));
        $customUrl = trim((string) ($_POST['exchange_api_url'] ?? 'https://open.er-api.com/v6/latest/{BASE}'));
        $interval = in_array($_POST['exchange_sync_interval'] ?? '', ['6h', '12h', '24h'], true)
            ? (string) $_POST['exchange_sync_interval'] : '12h';

        set_setting('exchange_api_provider', $provider, 'currency');
        set_setting('exchange_api_url', $customUrl, 'currency');
        set_setting('exchange_sync_interval', $interval, 'currency');
        if ($apiKey !== '') {
            set_setting('exchange_api_key', encrypt_secret($apiKey), 'currency');
        }
        log_activity('admin', (int) $admin['id'], 'currency_api_updated', 'settings');
        set_flash('success', 'Exchange rate API configuration saved.');
    } elseif ($action === 'change_base') {
        $newBase = strtoupper(sanitize_string($_POST['base_currency'] ?? '', 10));
        $convertValues = !empty($_POST['convert_stored_values']);
        $res = Currency::changeBaseCurrency($newBase, $convertValues, (int) $admin['id']);
        set_flash($res['ok'] ? 'success' : 'error', $res['ok'] ? $res['message'] : $res['error']);
    } elseif ($action === 'set_default') {
        $defCode = strtoupper(sanitize_string($_POST['default_currency'] ?? '', 10));
        $cur = Currency::find($defCode);
        if (!$cur || (int) $cur['is_enabled'] !== 1 || (float) ($cur['exchange_rate'] ?? 0) <= 0) {
            set_flash('error', 'Default display currency must be enabled and have a valid positive exchange rate.');
        } else {
            $pdo->exec('UPDATE currencies SET is_default = 0');
            $pdo->prepare('UPDATE currencies SET is_default = 1 WHERE code = ?')->execute([$defCode]);
            Currency::clearCache();
            set_setting('default_display_currency', $defCode, 'currency');
            log_activity('admin', (int) $admin['id'], 'default_currency_updated', 'currency', (int) $cur['id'], $defCode);
            set_flash('success', "Default display currency set to {$defCode}.");
        }
    } elseif ($action === 'add_currency') {
        $code = strtoupper(preg_replace('/[^A-Z]/', '', (string) ($_POST['code'] ?? '')));
        $name = sanitize_string($_POST['name'] ?? '', 80);
        $symbol = sanitize_string($_POST['symbol'] ?? '', 12);
        $decimals = max(0, min(4, (int) ($_POST['decimal_places'] ?? 2)));
        $rateMode = ($_POST['rate_mode'] ?? 'auto') === 'manual' ? 'manual' : 'auto';
        $manualRate = trim((string) ($_POST['exchange_rate'] ?? ''));
        $rateVal = ($manualRate !== '' && is_numeric($manualRate) && (float) $manualRate > 0) ? round((float) $manualRate, 8) : null;
        $isEnabled = isset($_POST['is_enabled']) ? 1 : 0;

        if (strlen($code) !== 3 || $name === '' || $symbol === '') {
            set_flash('error', 'Valid 3-letter ISO currency code (e.g. CAD), name, and symbol are required.');
        } elseif (Currency::find($code)) {
            set_flash('error', "Currency {$code} already exists.");
        } else {
            $pdo->prepare(
                'INSERT INTO currencies (code, name, symbol, decimal_places, rate_mode, exchange_rate, rate_source, is_enabled, last_rate_updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                $code,
                $name,
                $symbol,
                $decimals,
                $rateMode,
                $rateVal,
                $rateVal ? ($rateMode === 'manual' ? 'manual' : 'admin_initial') : 'unconfigured',
                $isEnabled,
                $rateVal ? date('Y-m-d H:i:s') : null,
            ]);
            Currency::clearCache();
            log_activity('admin', (int) $admin['id'], 'currency_added', 'currency', null, $code);
            set_flash('success', "Added currency {$code}.");
        }
    } elseif ($action === 'update_currency') {
        $cid = (int) ($_POST['currency_id'] ?? 0);
        $rowStmt = $pdo->prepare('SELECT * FROM currencies WHERE id = ? LIMIT 1');
        $rowStmt->execute([$cid]);
        $curRow = $rowStmt->fetch();

        if ($curRow) {
            $name = sanitize_string($_POST['name'] ?? $curRow['name'], 80);
            $symbol = sanitize_string($_POST['symbol'] ?? $curRow['symbol'], 12);
            $decimals = max(0, min(4, (int) ($_POST['decimal_places'] ?? 2)));
            $isBase = (int) $curRow['is_base'] === 1;
            $rateMode = $isBase ? 'auto' : (($_POST['rate_mode'] ?? 'auto') === 'manual' ? 'manual' : 'auto');
            $isEnabled = $isBase ? 1 : (isset($_POST['is_enabled']) ? 1 : 0);

            $rateRaw = trim((string) ($_POST['exchange_rate'] ?? ''));
            $newRate = $curRow['exchange_rate'];
            $rateSource = $curRow['rate_source'];
            $updatedAt = $curRow['last_rate_updated_at'];

            if (!$isBase && $rateRaw !== '') {
                if (!is_numeric($rateRaw) || (float) $rateRaw <= 0) {
                    set_flash('error', "Exchange rate for {$curRow['code']} must be strictly greater than zero.");
                    redirect('currencies.php');
                }
                $newRate = number_format((float) $rateRaw, 8, '.', '');
                $rateSource = 'manual';
                $updatedAt = date('Y-m-d H:i:s');
                $rateMode = 'manual';
            }

            $pdo->prepare(
                'UPDATE currencies SET name = ?, symbol = ?, decimal_places = ?, rate_mode = ?, exchange_rate = ?, rate_source = ?, is_enabled = ?, last_rate_updated_at = ? WHERE id = ?'
            )->execute([$name, $symbol, $decimals, $rateMode, $newRate, $rateSource, $isEnabled, $updatedAt, $cid]);
            Currency::clearCache();
            log_activity('admin', (int) $admin['id'], 'currency_updated', 'currency', $cid, $curRow['code']);
            set_flash('success', "Updated {$curRow['code']}.");
        }
    }

    redirect('currencies.php');
}

$currencies = Currency::all(false, false);
$baseCur = Currency::getBase();
$defaultCur = Currency::getDefault();
$baseUrl = app_base_url();
$cronToken = get_setting('cron_token', '');

$rateLogs = $pdo->query('SELECT * FROM exchange_rate_logs ORDER BY created_at DESC LIMIT 12')->fetchAll();
$convLogs = $pdo->query('SELECT * FROM currency_conversion_logs ORDER BY created_at DESC LIMIT 12')->fetchAll();

$pageTitle = 'Multi-Currency Management';
require __DIR__ . '/../includes/admin-header.php';
?>
<div class="space-y-6">
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h1 class="text-2xl font-bold text-[#151515]">Multi-Currency &amp; Exchange Rates</h1>
      <p class="text-sm text-slate-600 mt-1">Manage canonical Base Currency, user display currencies, manual overrides, and automated live FX rate syncing.</p>
    </div>
    <form method="POST">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="sync_rates">
      <button type="submit" class="px-5 py-2.5 rounded-xl bg-[#7138D8] hover:bg-[#5E28C2] text-white text-xs font-semibold shadow-sm transition">
        Sync Live Exchange Rates Now
      </button>
    </form>
  </div>

  <!-- Top Strategy Cards -->
  <div class="grid md:grid-cols-2 gap-6">
    <!-- Base Currency Card -->
    <form method="POST" class="bg-white border border-slate-200 rounded-2xl p-6 space-y-4">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="change_base">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-base font-bold text-[#151515]">Panel Base Currency</h2>
          <p class="text-xs text-slate-500 mt-0.5">Canonical accounting currency for wallets, costs, profits &amp; orders.</p>
        </div>
        <span class="px-3 py-1 rounded-xl bg-purple-50 border border-purple-200 text-xs font-bold font-mono-num text-[#7138D8]">
          <?= e($baseCur['code']) ?> (<?= e($baseCur['symbol']) ?>)
        </span>
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Switch Base Currency</label>
        <select name="base_currency" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm bg-white">
          <?php foreach ($currencies as $c): ?>
            <option value="<?= e($c['code']) ?>" <?= $c['code'] === $baseCur['code'] ? 'selected' : '' ?>>
              <?= e($c['code']) ?> — <?= e($c['name']) ?> (<?= (float)($c['exchange_rate'] ?? 0) > 0 ? number_format((float)$c['exchange_rate'], 4) : 'Rate Unconfigured' ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <label class="flex items-center gap-2 text-xs text-slate-600">
        <input type="checkbox" name="convert_stored_values" value="1" checked class="rounded border-slate-300 text-[#7138D8]">
        <span>Automatically rebase active wallet balances and service rates into the new base currency</span>
      </label>

      <button type="submit" class="px-4 py-2 rounded-xl bg-[#151515] text-white text-xs font-semibold hover:bg-black">
        Update Base Currency
      </button>
    </form>

    <!-- Default Display Currency Card -->
    <form method="POST" class="bg-white border border-slate-200 rounded-2xl p-6 space-y-4">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="set_default">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-base font-bold text-[#151515]">Default Visitor / User Currency</h2>
          <p class="text-xs text-slate-500 mt-0.5">Shown to logged-out guests and users without a saved preference.</p>
        </div>
        <span class="px-3 py-1 rounded-xl bg-emerald-50 border border-emerald-200 text-xs font-bold font-mono-num text-[#16A36A]">
          <?= e($defaultCur['code']) ?> (<?= e($defaultCur['symbol']) ?>)
        </span>
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Default Display Currency</label>
        <select name="default_currency" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm bg-white">
          <?php foreach ($currencies as $c): ?>
            <?php if ((int)$c['is_enabled'] === 1 && (float)($c['exchange_rate'] ?? 0) > 0): ?>
              <option value="<?= e($c['code']) ?>" <?= $c['code'] === $defaultCur['code'] ? 'selected' : '' ?>>
                <?= e($c['code']) ?> — <?= e($c['name']) ?> (<?= e($c['symbol']) ?>)
              </option>
            <?php endif; ?>
          <?php endforeach; ?>
        </select>
      </div>

      <p class="text-[11px] text-slate-500">Only enabled currencies with a positive exchange rate can serve as default display currency.</p>

      <button type="submit" class="px-4 py-2 rounded-xl bg-[#7138D8] text-white text-xs font-semibold hover:bg-[#5E28C2]">
        Save Default Display Currency
      </button>
    </form>
  </div>

  <!-- Exchange Rate API Configuration -->
  <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
      <div>
        <h2 class="text-base font-bold text-[#151515]">Exchange Rate API &amp; Cron Sync</h2>
        <p class="text-xs text-slate-500">Last successful sync: <strong class="font-mono-num"><?= e(get_setting('exchange_last_sync_at', 'Never')) ?></strong></p>
      </div>
      <div class="text-xs font-mono-num bg-[#F8F9FC] border border-slate-200 px-3 py-1.5 rounded-xl break-all">
        Cron: <?= e($baseUrl) ?>/cron/update-exchange-rates.php?token=<?= e($cronToken) ?>
      </div>
    </div>

    <form method="POST" class="grid md:grid-cols-4 gap-4 items-end">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save_api_config">
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">API Source</label>
        <select name="exchange_api_provider" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs bg-white">
          <option value="open_er_api" <?= get_setting('exchange_api_provider', 'open_er_api') === 'open_er_api' ? 'selected' : '' ?>>Open ER-API (No Key Required)</option>
          <option value="exchangerate_api_v6" <?= get_setting('exchange_api_provider') === 'exchangerate_api_v6' ? 'selected' : '' ?>>ExchangeRate-API v6 (API Key)</option>
          <option value="custom" <?= get_setting('exchange_api_provider') === 'custom' ? 'selected' : '' ?>>Custom JSON Endpoint</option>
        </select>
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Auto Sync Interval</label>
        <select name="exchange_sync_interval" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs bg-white">
          <option value="6h" <?= get_setting('exchange_sync_interval', '12h') === '6h' ? 'selected' : '' ?>>Every 6 Hours</option>
          <option value="12h" <?= get_setting('exchange_sync_interval', '12h') === '12h' ? 'selected' : '' ?>>Every 12 Hours</option>
          <option value="24h" <?= get_setting('exchange_sync_interval', '12h') === '24h' ? 'selected' : '' ?>>Every 24 Hours</option>
        </select>
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">API Key (optional / encrypted)</label>
        <input type="password" name="exchange_api_key" placeholder="Leave blank to retain" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-mono-num">
      </div>
      <div>
        <button type="submit" class="w-full py-2 rounded-xl bg-[#151515] text-white text-xs font-semibold hover:bg-black">
          Save API Config
        </button>
      </div>
      <div class="md:col-span-4">
        <label class="block text-xs font-semibold text-slate-700 mb-1">Custom URL Template ({BASE} &amp; {KEY} placeholders)</label>
        <input type="text" name="exchange_api_url" value="<?= e(get_setting('exchange_api_url', 'https://open.er-api.com/v6/latest/{BASE}')) ?>" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-mono-num">
      </div>
    </form>
  </div>

  <!-- Add New Currency -->
  <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-4">
    <h2 class="text-base font-bold text-[#151515]">Add ISO 4217 Currency</h2>
    <form method="POST" class="grid sm:grid-cols-6 gap-3 items-end">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add_currency">
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Code (3-letter)</label>
        <input type="text" name="code" maxlength="3" required placeholder="CAD" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs uppercase font-mono-num">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Name</label>
        <input type="text" name="name" required placeholder="Canadian Dollar" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Symbol</label>
        <input type="text" name="symbol" required placeholder="CA$" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Mode</label>
        <select name="rate_mode" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs bg-white">
          <option value="auto">Auto API</option>
          <option value="manual">Manual</option>
        </select>
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Rate (1 = ? <?= e($baseCur['code']) ?>)</label>
        <input type="number" step="0.00000001" min="0.00000001" name="exchange_rate" placeholder="Leave blank for API" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-mono-num">
      </div>
      <div class="flex items-center gap-2">
        <label class="flex items-center gap-1 text-xs"><input type="checkbox" name="is_enabled" value="1" checked> On</label>
        <button type="submit" class="flex-1 py-2 rounded-xl bg-[#7138D8] text-white text-xs font-semibold hover:bg-[#5E28C2]">Add</button>
      </div>
    </form>
  </div>

  <!-- Configured Currencies Table -->
  <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100">
      <h2 class="text-base font-bold text-[#151515]">Registered Currencies (Rate = <?= e($baseCur['code']) ?> per 1 unit)</h2>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-xs text-left">
        <thead class="bg-[#F8F9FC] text-slate-500 uppercase">
          <tr>
            <th class="px-4 py-3">Code</th>
            <th class="px-3 py-3">Name &amp; Symbol</th>
            <th class="px-3 py-3">Decimals</th>
            <th class="px-3 py-3">Mode</th>
            <th class="px-3 py-3">Rate (Base/Unit)</th>
            <th class="px-3 py-3">Source / Updated</th>
            <th class="px-3 py-3">Enabled</th>
            <th class="px-4 py-3 text-right">Save</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <?php foreach ($currencies as $c): ?>
            <?php $isBase = (int) $c['is_base'] === 1; ?>
            <tr class="hover:bg-slate-50/70">
              <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update_currency">
                <input type="hidden" name="currency_id" value="<?= (int) $c['id'] ?>">
                <td class="px-4 py-3 font-mono-num font-bold">
                  <?= e($c['code']) ?>
                  <?php if ($isBase): ?><span class="ml-1 px-1.5 py-0.5 rounded bg-purple-100 text-[#7138D8] text-[10px]">BASE</span><?php endif; ?>
                  <?php if ((int)$c['is_default'] === 1): ?><span class="ml-1 px-1.5 py-0.5 rounded bg-emerald-100 text-[#16A36A] text-[10px]">DEF</span><?php endif; ?>
                </td>
                <td class="px-3 py-3">
                  <div class="flex items-center gap-1.5">
                    <input type="text" name="name" value="<?= e($c['name']) ?>" class="w-32 px-2 py-1 rounded-lg border border-slate-200 text-xs">
                    <input type="text" name="symbol" value="<?= e($c['symbol']) ?>" class="w-14 px-2 py-1 rounded-lg border border-slate-200 text-xs text-center">
                  </div>
                </td>
                <td class="px-3 py-3">
                  <input type="number" name="decimal_places" min="0" max="4" value="<?= (int) $c['decimal_places'] ?>" class="w-14 px-2 py-1 rounded-lg border border-slate-200 text-xs font-mono-num">
                </td>
                <td class="px-3 py-3">
                  <?php if ($isBase): ?>
                    <span class="text-slate-500">Fixed Base</span>
                  <?php else: ?>
                    <select name="rate_mode" class="px-2 py-1 rounded-lg border border-slate-200 text-xs bg-white">
                      <option value="auto" <?= $c['rate_mode'] === 'auto' ? 'selected' : '' ?>>Auto API</option>
                      <option value="manual" <?= $c['rate_mode'] === 'manual' ? 'selected' : '' ?>>Manual</option>
                    </select>
                  <?php endif; ?>
                </td>
                <td class="px-3 py-3">
                  <?php if ($isBase): ?>
                    <span class="font-mono-num font-bold">1.00000000</span>
                  <?php else: ?>
                    <input type="number" step="0.00000001" min="0.00000001" name="exchange_rate" value="<?= $c['exchange_rate'] !== null ? e((string) $c['exchange_rate']) : '' ?>" placeholder="Not synced" class="w-32 px-2 py-1 rounded-lg border border-slate-200 text-xs font-mono-num">
                  <?php endif; ?>
                </td>
                <td class="px-3 py-3">
                  <span class="font-semibold <?= ($c['rate_source'] ?? '') === 'unconfigured' ? 'text-amber-600' : 'text-slate-600' ?>"><?= e($c['rate_source'] ?: 'unconfigured') ?></span>
                  <p class="text-[10px] text-slate-400"><?= $c['last_rate_updated_at'] ? e(time_ago($c['last_rate_updated_at'])) : 'Never updated' ?></p>
                </td>
                <td class="px-3 py-3">
                  <input type="checkbox" name="is_enabled" value="1" <?= (int)$c['is_enabled'] === 1 ? 'checked' : '' ?> <?= $isBase ? 'disabled checked' : '' ?>>
                </td>
                <td class="px-4 py-3 text-right">
                  <button type="submit" class="px-3 py-1.5 rounded-lg bg-[#151515] text-white font-semibold hover:bg-black">Save</button>
                </td>
              </form>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Sync & Conversion Audit Logs -->
  <div class="grid lg:grid-cols-2 gap-6">
    <div class="bg-white border border-slate-200 rounded-2xl p-5 space-y-3">
      <h3 class="text-sm font-bold text-[#151515]">Exchange Rate Sync Logs</h3>
      <div class="overflow-x-auto">
        <table class="w-full text-xs text-left">
          <thead class="text-slate-500 border-b border-slate-200">
            <tr><th class="py-2">Provider</th><th class="py-2">Status</th><th class="py-2">Updated</th><th class="py-2 text-right">Time</th></tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <?php if (empty($rateLogs)): ?>
              <tr><td colspan="4" class="py-4 text-center text-slate-400">No sync runs yet.</td></tr>
            <?php else: ?>
              <?php foreach ($rateLogs as $rl): ?>
                <tr>
                  <td class="py-2 font-mono-num"><?= e($rl['provider']) ?> (<?= e($rl['triggered_by']) ?>)</td>
                  <td class="py-2"><span class="font-semibold <?= $rl['status'] === 'success' ? 'text-[#16A36A]' : 'text-amber-700' ?>"><?= e($rl['status']) ?></span></td>
                  <td class="py-2 font-mono-num"><?= (int) $rl['updated_count'] ?></td>
                  <td class="py-2 text-right text-slate-400"><?= e(time_ago($rl['created_at'])) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl p-5 space-y-3">
      <h3 class="text-sm font-bold text-[#151515]">Recent Currency Conversions</h3>
      <div class="overflow-x-auto">
        <table class="w-full text-xs text-left">
          <thead class="text-slate-500 border-b border-slate-200">
            <tr><th class="py-2">Context</th><th class="py-2">From &rarr; To</th><th class="py-2 text-right">Rate</th><th class="py-2 text-right">Time</th></tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <?php if (empty($convLogs)): ?>
              <tr><td colspan="4" class="py-4 text-center text-slate-400">No conversion audit events yet.</td></tr>
            <?php else: ?>
              <?php foreach ($convLogs as $cl): ?>
                <tr>
                  <td class="py-2 font-semibold"><?= e($cl['context']) ?></td>
                  <td class="py-2 font-mono-num"><?= number_format((float)$cl['source_amount'], 2) ?> <?= e($cl['source_currency']) ?> &rarr; <?= number_format((float)$cl['target_amount'], 2) ?> <?= e($cl['target_currency']) ?></td>
                  <td class="py-2 font-mono-num text-right"><?= number_format((float)$cl['exchange_rate'], 6) ?></td>
                  <td class="py-2 text-right text-slate-400"><?= e(time_ago($cl['created_at'])) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
