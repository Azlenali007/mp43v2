<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
$admin = require_admin('manage_tickets');
require_once __DIR__ . '/../classes/Ticket.php';

$ticketId = validate_positive_int($_GET['id'] ?? null);
if (!$ticketId) {
    redirect('tickets.php');
}

$pdo = Database::getConnection();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();
    $action = $_POST['action'] ?? 'reply';
    if ($action === 'close') {
        $pdo->prepare('UPDATE support_tickets SET status = "closed" WHERE id = ?')->execute([$ticketId]);
        set_flash('success', 'Ticket closed.');
    } else {
        $msg = sanitize_string($_POST['message'] ?? '', 3000);
        if ($msg !== '') {
            Ticket::reply($ticketId, 'admin', (int) $admin['id'], $msg);
            set_flash('success', 'Reply posted.');
        }
    }
    redirect('ticket-details.php?id=' . $ticketId);
}

$stmt = $pdo->prepare('SELECT st.*, u.name AS user_name, u.username FROM support_tickets st INNER JOIN users u ON u.id = st.user_id WHERE st.id = ? LIMIT 1');
$stmt->execute([$ticketId]);
$ticket = $stmt->fetch();
if (!$ticket) {
    redirect('tickets.php');
}

$msgStmt = $pdo->prepare('SELECT * FROM ticket_messages WHERE ticket_id = ? ORDER BY created_at ASC');
$msgStmt->execute([$ticketId]);
$messages = $msgStmt->fetchAll();

$pageTitle = 'Ticket #' . $ticket['ticket_code'];
require __DIR__ . '/../includes/admin-header.php';
?>
<div class="max-w-3xl space-y-6">
  <div class="flex items-center justify-between gap-4">
    <div>
      <a href="tickets.php" class="text-xs font-semibold text-[#7138D8] hover:underline">&larr; Back to Tickets</a>
      <h2 class="text-xl font-bold text-[#151515] mt-1"><?= e($ticket['subject']) ?></h2>
      <p class="text-xs text-slate-500">Customer: <?= e($ticket['user_name']) ?> (@<?= e($ticket['username']) ?>)</p>
    </div>
    <?php if ($ticket['status'] !== 'closed'): ?>
      <form method="POST" action="ticket-details.php?id=<?= $ticketId ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="close">
        <button type="submit" class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-rose-600 hover:bg-rose-50">Close Ticket</button>
      </form>
    <?php endif; ?>
  </div>

  <div class="space-y-3">
    <?php foreach ($messages as $m): ?>
      <?php $isAdmin = $m['sender_type'] === 'admin'; ?>
      <div class="p-4 rounded-2xl border <?= $isAdmin ? 'bg-purple-50/50 border-purple-200' : 'bg-white border-slate-200' ?>">
        <div class="flex items-center justify-between text-xs mb-2">
          <span class="font-bold <?= $isAdmin ? 'text-[#7138D8]' : 'text-[#151515]' ?>"><?= $isAdmin ? 'Administrator' : e($ticket['user_name']) ?></span>
          <span class="text-slate-400"><?= e(date('d M Y, H:i', strtotime($m['created_at']))) ?></span>
        </div>
        <p class="text-sm text-slate-700 whitespace-pre-line"><?= e($m['message']) ?></p>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if ($ticket['status'] !== 'closed'): ?>
    <div class="bg-white border border-slate-200 rounded-2xl p-5">
      <form method="POST" action="ticket-details.php?id=<?= $ticketId ?>" class="space-y-3">
        <?= csrf_field() ?>
        <textarea name="message" rows="4" required placeholder="Write administrator reply..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm"></textarea>
        <button type="submit" class="px-5 py-2.5 rounded-xl bg-[#7138D8] text-white text-sm font-semibold hover:bg-[#5B24C0]">Send Reply</button>
      </form>
    </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
