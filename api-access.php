<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$user = require_user();
require_once __DIR__ . '/classes/User.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();
    $newKey = User::regenerateApiKey((int) $user['id']);
    $user['api_key'] = $newKey;
    set_flash('success', 'A new API key has been generated.');
    redirect('api-access.php');
}

$endpointUrl = app_base_url() . '/api/v2.php';
$pageTitle = 'API Access';
require __DIR__ . '/includes/user-header.php';
?>
<div class="max-w-4xl space-y-6">
  <div>
    <h1 class="text-2xl font-bold text-[#151515]">API Access</h1>
    <p class="text-sm text-slate-600 mt-1">Automate service queries, order placement, and status checks with your personal API key.</p>
  </div>

  <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-4">
    <div class="grid sm:grid-cols-2 gap-4">
      <div>
        <p class="text-xs font-semibold text-slate-500">API Endpoint (POST)</p>
        <p class="mt-1 font-mono-num text-xs bg-[#F8F9FC] border border-slate-200 rounded-xl px-3.5 py-2.5 break-all"><?= e($endpointUrl) ?></p>
      </div>
      <div>
        <p class="text-xs font-semibold text-slate-500">Your Private API Key</p>
        <div class="mt-1 flex items-center gap-2">
          <input type="text" readonly value="<?= e($user['api_key']) ?>" class="flex-1 font-mono-num text-xs bg-[#F8F9FC] border border-slate-200 rounded-xl px-3.5 py-2.5">
          <button type="button" onclick="window.SMMApp.copyText(<?= json_encode($user['api_key']) ?>, this)" class="px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold hover:bg-slate-50">Copy</button>
        </div>
      </div>
    </div>

    <form method="POST" action="api-access.php" onsubmit="return confirm('Regenerating your API key will immediately invalidate your previous key. Continue?');">
      <?= csrf_field() ?>
      <button type="submit" class="px-4 py-2 rounded-xl bg-[#151515] text-white text-xs font-semibold hover:bg-black">Regenerate API Key</button>
    </form>
  </div>

  <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-4">
    <h2 class="text-base font-bold text-[#151515]">Supported Actions (application/x-www-form-urlencoded or JSON)</h2>
    <div class="grid sm:grid-cols-2 gap-4 text-xs">
      <div class="p-4 rounded-xl bg-[#F8F9FC] border border-slate-200 space-y-1.5">
        <p class="font-bold text-[#7138D8]">1. Services List</p>
        <pre class="font-mono-num text-slate-700 overflow-x-auto">key=YOUR_API_KEY&amp;action=services</pre>
      </div>
      <div class="p-4 rounded-xl bg-[#F8F9FC] border border-slate-200 space-y-1.5">
        <p class="font-bold text-[#7138D8]">2. Wallet Balance</p>
        <pre class="font-mono-num text-slate-700 overflow-x-auto">key=YOUR_API_KEY&amp;action=balance</pre>
      </div>
      <div class="p-4 rounded-xl bg-[#F8F9FC] border border-slate-200 space-y-1.5">
        <p class="font-bold text-[#7138D8]">3. Place Order</p>
        <pre class="font-mono-num text-slate-700 overflow-x-auto">key=YOUR_API_KEY&amp;action=add&amp;service=1&amp;link=https://...&amp;quantity=1000</pre>
      </div>
      <div class="p-4 rounded-xl bg-[#F8F9FC] border border-slate-200 space-y-1.5">
        <p class="font-bold text-[#7138D8]">4. Order Status</p>
        <pre class="font-mono-num text-slate-700 overflow-x-auto">key=YOUR_API_KEY&amp;action=status&amp;order=12</pre>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/includes/user-footer.php'; ?>
