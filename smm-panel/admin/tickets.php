<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
$admin = require_admin('manage_tickets');

$pdo = Database::getConnection();
$statusFilter = sanitize_string($_GET['status'] ?? '', 30);
$where = ($statusFilter !== '' && array_key_exists($statusFilter, TICKET_STATUSES)) ? 'WHERE st.status = ?' : '';

$stmt = $pdo->prepare(
    "SELECT st.*, u.name AS user_name, u.username
     FROM support_tickets st
     INNER JOIN users u ON u.id = st.user_id
     $where
     ORDER BY st.updated_at DESC
     LIMIT 100"
);
$stmt->execute($where ? [$statusFilter] : []);
$tickets = $stmt->fetchAll();

$pageTitle = 'Support Tickets';
require __DIR__ . '/../includes/admin-header.php';
?>
<div class="space-y-6">
  <div class="flex flex-wrap gap-1.5">
    <a href="tickets.php" class="px-3 py-1.5 rounded-lg text-xs font-semibold <?= $statusFilter === '' ? 'bg-[#7138D8] text-white' : 'bg-white border border-slate-200 text-slate-600' ?>">All</a>
    <?php foreach (TICKET_STATUSES as $k => $lbl): ?>
      <a href="tickets.php?status=<?= e($k) ?>" class="px-3 py-1.5 rounded-lg text-xs font-semibold <?= $statusFilter === $k ? 'bg-[#7138D8] text-white' : 'bg-white border border-slate-200 text-slate-600' ?>"><?= e($lbl) ?></a>
    <?php endforeach; ?>
  </div>

  <?php if (empty($tickets)): ?>
    <div class="bg-white border border-slate-200 rounded-2xl p-10 text-center text-sm text-slate-500">No support tickets found.</div>
  <?php else: ?>
    <div class="bg-white border border-slate-200 rounded-2xl overflow-x-auto">
      <table class="w-full text-left border-collapse text-sm">
        <thead>
          <tr class="border-b border-slate-200 bg-slate-50/70 text-xs font-semibold text-slate-500">
            <th class="py-3 px-4">Code</th>
            <th class="py-3 px-3">User</th>
            <th class="py-3 px-3">Subject</th>
            <th class="py-3 px-3">Category</th>
            <th class="py-3 px-3">Status</th>
            <th class="py-3 px-4 text-right">Updated</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-xs">
          <?php foreach ($tickets as $t): ?>
            <tr class="hover:bg-slate-50/60">
              <td class="py-3 px-4 font-mono-num font-bold"><a href="ticket-details.php?id=<?= (int) $t['id'] ?>" class="text-[#7138D8] hover:underline">#<?= e($t['ticket_code']) ?></a></td>
              <td class="py-3 px-3 font-semibold">@<?= e($t['username']) ?></td>
              <td class="py-3 px-3"><a href="ticket-details.php?id=<?= (int) $t['id'] ?>" class="font-semibold text-[#151515] hover:underline"><?= e($t['subject']) ?></a></td>
              <td class="py-3 px-3 uppercase text-slate-500"><?= e($t['category']) ?></td>
              <td class="py-3 px-3"><?= e(TICKET_STATUSES[$t['status']] ?? $t['status']) ?></td>
              <td class="py-3 px-4 text-right text-slate-500"><?= e(time_ago($t['updated_at'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
