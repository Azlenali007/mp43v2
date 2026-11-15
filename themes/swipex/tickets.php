<?php
declare(strict_types=1);
if (!defined('APP_ROOT')) {
    http_response_code(403);
    exit('Forbidden');
}
$pageTitle = $pageTitle ?? 'Support Center';
ob_start();
?>
<div class="space-y-6">
  <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <span class="text-xs font-semibold text-blue-600 uppercase tracking-wider">Helpdesk</span>
      <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Support Tickets</h1>
      <p class="text-xs text-slate-500 mt-0.5">Need assistance? Open a support ticket and our team will respond promptly.</p>
    </div>
    <a href="<?= e($rp) ?>tickets.php?action=new" class="inline-flex items-center justify-center px-4 py-2.5 rounded-2xl bg-blue-600 hover:bg-blue-700 text-xs font-bold text-white shadow-md shadow-blue-500/20 transition">+ Open Ticket</a>
  </div>

  <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200/80">
    <?php if (empty($tickets ?? [])): ?>
      <div class="py-12 text-center">
        <p class="text-sm font-bold text-slate-700">No support tickets found</p>
      </div>
    <?php else: ?>
      <div class="divide-y divide-slate-100">
        <?php foreach ($tickets as $t): ?>
          <div class="py-4 flex items-center justify-between gap-4">
            <div>
              <a href="<?= e($rp) ?>ticket-details.php?id=<?= (int) $t['id'] ?>" class="text-sm font-extrabold text-blue-600 hover:underline">#<?= (int) $t['id'] ?> — <?= e($t['subject']) ?></a>
              <p class="text-xs text-slate-400 mt-0.5">Status: <span class="uppercase font-bold text-slate-700"><?= e($t['status']) ?></span> &bull; <?= e(time_ago($t['created_at'])) ?></p>
            </div>
            <a href="<?= e($rp) ?>ticket-details.php?id=<?= (int) $t['id'] ?>" class="px-3.5 py-2 rounded-xl bg-slate-100 text-slate-800 text-xs font-bold hover:bg-slate-200 transition">View</a>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/layouts/dashboard.php';
