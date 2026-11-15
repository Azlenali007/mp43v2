<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
$admin = require_admin('manage_providers');
require_once __DIR__ . '/../classes/Provider.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();
    $action = $_POST['action'] ?? '';
    $pid = validate_positive_int($_POST['provider_id'] ?? null);
    if ($pid) {
        if ($action === 'check_balance') {
            $res = Provider::fetchBalance($pid);
            set_flash($res['ok'] ? 'success' : 'error', $res['ok'] ? "Live provider balance: {$res['balance']} {$res['currency']}" : $res['error']);
        } elseif ($action === 'delete') {
            $pdo = Database::getConnection();
            $pdo->prepare('DELETE FROM providers WHERE id = ?')->execute([$pid]);
            set_flash('success', 'Provider deleted.');
        }
    }
    redirect('providers.php');
}

$providers = Provider::all(false);
$pdo = Database::getConnection();
$logs = $pdo->query(
    'SELECT al.*, p.name AS provider_name
     FROM api_logs al
     LEFT JOIN providers p ON p.id = al.provider_id
     ORDER BY al.created_at DESC
     LIMIT 15'
)->fetchAll();

$pageTitle = 'Providers Management';
require __DIR__ . '/../includes/admin-header.php';
?>
<div class="space-y-6">
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h2 class="text-xl font-bold text-[#151515]">API Providers</h2>
      <p class="text-sm text-slate-600">Connect upstream SMM providers, verify API connections, and inspect live balances.</p>
    </div>
    <div class="flex items-center gap-2 self-start">
      <a href="import-services.php" class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-xs font-semibold">Import Services</a>
      <a href="provider-add.php" class="px-5 py-2.5 rounded-xl bg-[#7138D8] text-white text-xs font-semibold hover:bg-[#5B24C0]">+ Add Provider</a>
    </div>
  </div>

  <?php if (empty($providers)): ?>
    <div class="bg-white border border-slate-200 rounded-2xl p-10 text-center">
      <p class="text-base font-semibold text-[#151515]">No Providers Configured</p>
      <p class="text-sm text-slate-500 mt-1">Add a provider API URL and key to import services and automate order fulfillment.</p>
      <a href="provider-add.php" class="mt-4 inline-flex px-5 py-2.5 rounded-xl bg-[#7138D8] text-white text-xs font-semibold">Add First Provider</a>
    </div>
  <?php else: ?>
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
      <?php foreach ($providers as $p): ?>
        <div class="bg-white border border-slate-200 rounded-2xl p-5 space-y-4">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <h3 class="text-base font-bold text-[#151515] truncate"><?= e($p['name']) ?></h3>
              <p class="text-xs text-slate-500 truncate font-mono-num"><?= e($p['api_url']) ?></p>
            </div>
            <span class="px-2 py-0.5 rounded-md text-xs font-semibold <?= $p['status'] === 'active' ? 'bg-emerald-50 text-[#16A36A]' : 'bg-slate-100 text-slate-600' ?>"><?= e(ucfirst($p['status'])) ?></span>
          </div>

          <div class="p-3 rounded-xl bg-[#F8F9FC] border border-slate-200 flex items-center justify-between">
            <span class="text-xs text-slate-500">Cached Balance</span>
            <span class="text-sm font-bold font-mono-num text-[#151515]"><?= number_format((float) $p['balance'], 2) ?> <?= e($p['currency']) ?></span>
          </div>

          <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-slate-100 text-xs">
            <form method="POST" action="providers.php" class="inline">
              <?= csrf_field() ?>
              <input type="hidden" name="provider_id" value="<?= (int) $p['id'] ?>">
              <input type="hidden" name="action" value="check_balance">
              <button type="submit" class="px-3 py-1.5 rounded-lg bg-[#151515] text-white font-semibold hover:bg-black">Test &amp; Check Balance</button>
            </form>
            <div class="flex items-center gap-3">
              <a href="import-services.php?provider_id=<?= (int) $p['id'] ?>" class="font-semibold text-[#16A36A] hover:underline">Import</a>
              <a href="provider-edit.php?id=<?= (int) $p['id'] ?>" class="font-semibold text-[#7138D8] hover:underline">Edit</a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- Outbound API Logs -->
  <div class="bg-white border border-slate-200 rounded-2xl p-6">
    <h3 class="text-base font-bold text-[#151515] mb-3">Recent Provider API Logs</h3>
    <?php if (empty($logs)): ?>
      <p class="text-xs text-slate-500">No outbound provider API requests logged yet.</p>
    <?php else: ?>
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead><tr class="border-b border-slate-200 text-slate-500"><th class="py-2 pr-3">Provider</th><th class="py-2 px-3">Endpoint</th><th class="py-2 px-3">HTTP</th><th class="py-2 px-3 text-right">Latency</th><th class="py-2 pl-3 text-right">Time</th></tr></thead>
          <tbody class="divide-y divide-slate-100 font-mono-num">
            <?php foreach ($logs as $l): ?>
              <tr>
                <td class="py-2.5 pr-3 font-sans font-semibold"><?= e($l['provider_name'] ?: 'System') ?></td>
                <td class="py-2.5 px-3 truncate max-w-[260px]"><?= e($l['endpoint']) ?></td>
                <td class="py-2.5 px-3"><?= (int) $l['response_code'] ?></td>
                <td class="py-2.5 px-3 text-right"><?= (int) $l['duration_ms'] ?>ms</td>
                <td class="py-2.5 pl-3 text-right text-slate-500 font-sans"><?= e(time_ago($l['created_at'])) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
