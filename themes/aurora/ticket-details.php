<?php
declare(strict_types=1);
if (!defined('APP_ROOT')) {
    http_response_code(403);
    exit('Forbidden');
}
$pageTitle = $pageTitle ?? ('Ticket #' . ($ticket['ticket_code'] ?? ''));
ob_start();
?>
<div class="max-w-3xl space-y-6">
  <div class="flex items-center justify-between gap-4">
    <div>
      <a href="<?= e($rp) ?>tickets.php" class="text-xs font-semibold text-teal-300 hover:underline">&larr; Back to Tickets</a>
      <h1 class="text-xl font-bold text-white mt-1"><?= e($ticket['subject']) ?></h1>
      <p class="text-xs text-slate-400 font-mono-num mt-0.5">#<?= e($ticket['ticket_code']) ?> · <?= e(ucfirst($ticket['category'])) ?></p>
    </div>
    <span class="px-3 py-1 rounded-xl text-xs font-semibold bg-slate-800 text-teal-300"><?= e(TICKET_STATUSES[$ticket['status']] ?? $ticket['status']) ?></span>
  </div>

  <div class="space-y-4">
    <?php foreach ($messages as $m): ?>
      <?php $isUser = $m['sender_type'] === 'user'; ?>
      <div class="p-4 rounded-2xl border <?= $isUser ? 'aurora-surface' : 'bg-teal-950/35 border-teal-500/35' ?>">
        <div class="flex items-center justify-between text-xs mb-2">
          <span class="font-bold <?= $isUser ? 'text-white' : 'text-teal-300' ?>"><?= $isUser ? e($user['name']) : 'Support Team' ?></span>
          <span class="text-slate-400"><?= e(date('d M Y, H:i', strtotime($m['created_at']))) ?></span>
        </div>
        <p class="text-sm text-slate-200 whitespace-pre-line"><?= e($m['message']) ?></p>
        <?php if (!empty($m['attachment_path'])): ?>
          <a href="<?= e($rp . $m['attachment_path']) ?>" target="_blank" class="inline-block mt-2 text-xs font-semibold text-teal-300 hover:underline">View Attachment</a>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if ($ticket['status'] !== 'closed'): ?>
    <div class="aurora-surface rounded-2xl p-5">
      <form method="POST" action="<?= e($rp) ?>ticket-details.php?id=<?= (int) $ticket['id'] ?>" class="space-y-3">
        <?= csrf_field() ?>
        <label class="block text-xs font-semibold text-slate-300">Reply to Ticket</label>
        <textarea name="message" rows="3" required class="w-full px-3.5 py-2.5 rounded-xl aurora-input border text-sm"></textarea>
        <button type="submit" class="px-5 py-2.5 rounded-xl aurora-btn-primary text-sm font-semibold transition">Send Reply</button>
      </form>
    </div>
  <?php endif; ?>
</div>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/layouts/dashboard.php';
