<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
$admin = require_admin('all');

$pdo = Database::getConnection();
$logs = $pdo->query('SELECT * FROM activity_logs ORDER BY created_at DESC, id DESC LIMIT 150')->fetchAll();

$pageTitle = 'Activity Logs';
require __DIR__ . '/../includes/admin-header.php';
?>
<div class="bg-white border border-slate-200 rounded-2xl overflow-x-auto">
  <?php if (empty($logs)): ?>
    <div class="p-10 text-center text-sm text-slate-500">No activity events logged yet.</div>
  <?php else: ?>
    <table class="w-full text-left border-collapse text-xs">
      <thead>
        <tr class="border-b border-slate-200 bg-slate-50/70 font-semibold text-slate-500">
          <th class="py-3 px-4">Actor</th>
          <th class="py-3 px-3">Action</th>
          <th class="py-3 px-3">Details</th>
          <th class="py-3 px-3">IP Address</th>
          <th class="py-3 px-4 text-right">Time</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        <?php foreach ($logs as $l): ?>
          <tr>
            <td class="py-2.5 px-4 uppercase font-semibold text-slate-700"><?= e($l['actor_type']) ?><?= $l['actor_id'] ? ' #' . (int) $l['actor_id'] : '' ?></td>
            <td class="py-2.5 px-3 font-mono-num text-[#7138D8]"><?= e($l['action']) ?></td>
            <td class="py-2.5 px-3 text-slate-600 max-w-md truncate"><?= e($l['details'] ?? '—') ?></td>
            <td class="py-2.5 px-3 font-mono-num text-slate-500"><?= e($l['ip_address'] ?? '') ?></td>
            <td class="py-2.5 px-4 text-right text-slate-500 whitespace-nowrap"><?= e(date('d M Y, H:i', strtotime($l['created_at']))) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
