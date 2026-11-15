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
      <a href="<?= e($rp) ?>tickets.php" class="text-xs font-semibold text-[#7138D8] hover:underline">&larr; Back to Tickets</a>
      <h1 class="text-xl font-bold text-[#151515] mt-1"><?= e($ticket['subject']) ?></h1>
      <p class="text-xs text-slate-500 font-mono-num mt-0.5">#<?= e($ticket['ticket_code']) ?> · <?= e(ucfirst($ticket['category'])) ?></p>
    </div>
    <span class="px-3 py-1 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700"><?= e(TICKET_STATUSES[$ticket['status']] ?? $ticket['status']) ?></span>
  </div>

  <div class="space-y-4">
    <?php foreach ($messages as $m): ?>
      <?php $isUser = $m['sender_type'] === 'user'; ?>
      <div class="p-4 rounded-2xl border <?= $isUser ? 'bg-white border-slate-200' : 'bg-purple-50/60 border-purple-200' ?>">
        <div class="flex items-center justify-between text-xs mb-2">
          <span class="font-bold <?= $isUser ? 'text-[#151515]' : 'text-[#7138D8]' ?>"><?= $isUser ? e($user['name']) : 'Support Team' ?></span>
          <span class="text-slate-400"><?= e(date('d M Y, H:i', strtotime($m['created_at']))) ?></span>
        </div>
        <p class="text-sm text-slate-700 whitespace-pre-line"><?= e($m['message']) ?></p>
        <?php if (!empty($m['attachment_path'])): ?>
          <a href="<?= e($rp . $m['attachment_path']) ?>" target="_blank" class="inline-block mt-2 text-xs font-semibold text-[#7138D8] hover:underline">View Attachment</a>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if ($ticket['status'] !== 'closed'): ?>
    <div class="bg-white border border-slate-200 rounded-2xl p-5">
      <form method="POST" action="<?= e($rp) ?>ticket-details.php?id=<?= (int) $ticket['id'] ?>" class="space-y-3">
        <?= csrf_field() ?>
        <label class="block text-xs font-semibold text-slate-700">Reply to Ticket</label>
        <textarea name="message" rows="3" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-[#7138D8]"></textarea>
        <button type="submit" class="px-5 py-2.5 rounded-xl bg-[#7138D8] text-white text-sm font-semibold hover:bg-[#5B24C0] transition">Send Reply</button>
      </form>
    </div>
  <?php endif; ?>
</div>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/layouts/dashboard.php';
