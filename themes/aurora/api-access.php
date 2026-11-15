<?php
declare(strict_types=1);
if (!defined('APP_ROOT')) {
    http_response_code(403);
    exit('Forbidden');
}
$pageTitle = $pageTitle ?? 'API Access';
ob_start();
?>
<div class="max-w-4xl space-y-6">
  <div>
    <h1 class="text-2xl font-bold text-white">API v2 Credentials</h1>
    <p class="text-sm text-slate-400 mt-1">Automate service discovery, order placement, and status polling via JSON API.</p>
  </div>

  <div class="aurora-surface rounded-2xl p-6 space-y-4">
    <div class="grid sm:grid-cols-2 gap-4">
      <div>
        <p class="text-xs font-semibold text-slate-400">API Endpoint (POST)</p>
        <p class="mt-1 font-mono-num text-xs bg-[#0B1220] border border-slate-800 text-teal-300 rounded-xl px-3.5 py-2.5 break-all"><?= e($endpointUrl) ?></p>
      </div>
      <div>
        <p class="text-xs font-semibold text-slate-400">Your Private API Key</p>
        <div class="mt-1 flex items-center gap-2">
          <input type="text" readonly value="<?= e($user['api_key']) ?>" class="flex-1 font-mono-num text-xs aurora-input border rounded-xl px-3.5 py-2.5">
          <button type="button" onclick="window.SMMApp.copyText(<?= json_encode($user['api_key']) ?>, this)" class="px-3.5 py-2.5 rounded-xl border border-slate-700 bg-[#0B1220] text-xs font-semibold text-teal-300 hover:border-teal-400/50">Copy</button>
        </div>
      </div>
    </div>

    <form method="POST" action="<?= e($rp) ?>api-access.php" onsubmit="return confirm('Regenerating your API key will immediately invalidate your previous key. Continue?');">
      <?= csrf_field() ?>
      <button type="submit" class="px-4 py-2 rounded-xl bg-slate-800 border border-slate-700 text-white text-xs font-semibold hover:bg-slate-700">Regenerate API Key</button>
    </form>
  </div>

  <div class="aurora-surface rounded-2xl p-6 space-y-4">
    <h2 class="text-base font-bold text-white">Supported Actions (application/x-www-form-urlencoded or JSON)</h2>
    <div class="grid sm:grid-cols-2 gap-4 text-xs">
      <div class="p-4 rounded-xl bg-[#0B1220] border border-slate-800 space-y-1.5">
        <p class="font-bold text-teal-300">1. Services List</p>
        <pre class="font-mono-num text-slate-300 overflow-x-auto">key=YOUR_API_KEY&amp;action=services</pre>
      </div>
      <div class="p-4 rounded-xl bg-[#0B1220] border border-slate-800 space-y-1.5">
        <p class="font-bold text-teal-300">2. Wallet Balance</p>
        <pre class="font-mono-num text-slate-300 overflow-x-auto">key=YOUR_API_KEY&amp;action=balance</pre>
      </div>
      <div class="p-4 rounded-xl bg-[#0B1220] border border-slate-800 space-y-1.5">
        <p class="font-bold text-teal-300">3. Place Order</p>
        <pre class="font-mono-num text-slate-300 overflow-x-auto">key=YOUR_API_KEY&amp;action=add&amp;service=1&amp;link=https://...&amp;quantity=1000</pre>
      </div>
      <div class="p-4 rounded-xl bg-[#0B1220] border border-slate-800 space-y-1.5">
        <p class="font-bold text-teal-300">4. Order Status</p>
        <pre class="font-mono-num text-slate-300 overflow-x-auto">key=YOUR_API_KEY&amp;action=status&amp;order=12</pre>
      </div>
    </div>
  </div>
</div>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/layouts/dashboard.php';
