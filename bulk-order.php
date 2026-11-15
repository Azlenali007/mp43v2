<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$user = require_user();

require_once __DIR__ . '/classes/Order.php';
require_once __DIR__ . '/classes/Service.php';
require_once __DIR__ . '/classes/Wallet.php';
require_once __DIR__ . '/classes/Provider.php';

$results = [];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();
    $rawLines = trim((string) ($_POST['bulk_lines'] ?? ''));
    if ($rawLines === '') {
        $error = 'Enter at least one order line in the format: service_id | link | quantity';
    } else {
        $lines = preg_split('/\R/', $rawLines);
        $lines = array_slice(array_filter(array_map('trim', $lines)), 0, 50);
        foreach ($lines as $idx => $line) {
            $parts = array_map('trim', explode('|', $line));
            if (count($parts) !== 3) {
                $results[] = ['line' => $idx + 1, 'ok' => false, 'message' => 'Invalid syntax. Expected service_id | link | quantity'];
                continue;
            }
            $svcId = validate_positive_int($parts[0]);
            $link = $parts[1];
            $qty = validate_positive_int($parts[2]);
            if (!$svcId || !$qty) {
                $results[] = ['line' => $idx + 1, 'ok' => false, 'message' => 'Invalid service ID or quantity.'];
                continue;
            }
            $res = Order::placeOrder((int) $user['id'], $svcId, $link, $qty, 'bulk');
            if ($res['ok']) {
                $results[] = ['line' => $idx + 1, 'ok' => true, 'message' => "Order #{$res['order_code']} placed (" . format_currency($res['charge']) . ")"];
            } else {
                $results[] = ['line' => $idx + 1, 'ok' => false, 'message' => $res['error']];
            }
        }
        $user = current_user() ?: $user;
    }
}

$pageTitle = 'Bulk Orders';
require __DIR__ . '/includes/user-header.php';
?>
<div class="max-w-4xl space-y-6">
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h1 class="text-2xl font-bold text-[#151515]">Bulk Order</h1>
      <p class="text-sm text-slate-600 mt-1">Submit up to 50 orders in one batch using pipe-separated lines.</p>
    </div>
    <a href="<?= e(app_root_prefix()) ?>services.php" class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-[#151515] hover:bg-slate-50 self-start">View Service IDs</a>
  </div>

  <?php if ($error): ?>
    <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-sm text-rose-700 font-medium"><?= e($error) ?></div>
  <?php endif; ?>

  <?php if (!empty($results)): ?>
    <div class="bg-white border border-slate-200 rounded-2xl p-5 space-y-2">
      <h2 class="text-sm font-bold text-[#151515]">Batch Processing Results</h2>
      <div class="divide-y divide-slate-100 text-xs">
        <?php foreach ($results as $r): ?>
          <div class="py-2 flex items-center justify-between gap-3">
            <span class="font-mono-num text-slate-500">Line <?= (int) $r['line'] ?></span>
            <span class="flex-1 <?= $r['ok'] ? 'text-[#16A36A] font-semibold' : 'text-rose-600 font-medium' ?>"><?= e($r['message']) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <div class="bg-white border border-slate-200 rounded-2xl p-6">
    <form method="POST" action="bulk-order.php" class="space-y-4">
      <?= csrf_field() ?>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">One order per line: <code class="font-mono-num text-[#7138D8]">service_id | link | quantity</code></label>
        <textarea name="bulk_lines" rows="8" placeholder="1 | https://instagram.com/example | 1000&#10;2 | https://youtube.com/watch?v=example | 500" class="w-full p-4 rounded-xl border border-slate-200 font-mono-num text-xs focus:outline-none focus:border-[#7138D8]"><?= e($_POST['bulk_lines'] ?? '') ?></textarea>
      </div>
      <div class="flex items-center justify-between">
        <span class="text-xs text-slate-500">Wallet Balance: <strong class="text-[#16A36A] font-mono-num"><?= e(format_currency($user['wallet_balance'])) ?></strong></span>
        <button type="submit" class="px-6 py-2.5 rounded-xl bg-[#7138D8] text-white text-sm font-semibold hover:bg-[#5B24C0] transition">
          Submit Bulk Orders
        </button>
      </div>
    </form>
  </div>
</div>
<?php require __DIR__ . '/includes/user-footer.php'; ?>
