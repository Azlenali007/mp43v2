<?php
declare(strict_types=1);
if (!defined('APP_ROOT')) {
    http_response_code(403);
    exit('Forbidden');
}
$pageTitle = $pageTitle ?? 'Support Tickets';
ob_start();
?>
<div class="grid lg:grid-cols-12 gap-6 items-start">
  <div class="lg:col-span-5 aurora-surface rounded-2xl p-6 space-y-4">
    <h1 class="text-lg font-bold text-white">Open New Ticket</h1>
    <?php if (!empty($error)): ?>
      <div class="p-3 rounded-xl bg-rose-950/70 border border-rose-500/40 text-xs text-rose-200 font-medium"><?= e($error) ?></div>
    <?php endif; ?>
    <form method="POST" action="<?= e($rp) ?>tickets.php" enctype="multipart/form-data" class="space-y-4">
      <?= csrf_field() ?>
      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Subject</label>
        <input type="text" name="subject" required class="w-full px-3.5 py-2.5 rounded-xl aurora-input border text-sm">
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1.5">Category</label>
          <select name="category" class="w-full px-3 py-2.5 rounded-xl aurora-input border text-sm">
            <option value="order">Order Issue</option>
            <option value="payment">Payment / Wallet</option>
            <option value="api">API Support</option>
            <option value="general">General</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1.5">Priority</label>
          <select name="priority" class="w-full px-3 py-2.5 rounded-xl aurora-input border text-sm">
            <option value="low">Low</option>
            <option value="medium" selected>Medium</option>
            <option value="high">High</option>
          </select>
        </div>
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Related Order ID (Optional)</label>
        <input type="number" name="order_id" placeholder="e.g. 12" class="w-full px-3.5 py-2.5 rounded-xl aurora-input border text-sm font-mono-num">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Message</label>
        <textarea name="message" rows="4" required class="w-full px-3.5 py-2.5 rounded-xl aurora-input border text-sm"></textarea>
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Attachment (Optional image, max 3MB)</label>
        <input type="file" name="attachment" accept="image/jpeg,image/png,image/webp" class="w-full text-xs text-slate-300">
      </div>
      <button type="submit" class="w-full py-2.5 rounded-xl aurora-btn-primary text-sm font-semibold transition">Submit Ticket</button>
    </form>
  </div>

  <div class="lg:col-span-7 aurora-surface rounded-2xl p-6">
    <h2 class="text-lg font-bold text-white mb-4">Your Support Tickets</h2>
    <?php if (empty($tickets)): ?>
      <p class="text-sm text-slate-400">You have not created any support tickets yet.</p>
    <?php else: ?>
      <div class="divide-y divide-slate-800">
        <?php foreach ($tickets as $t): ?>
          <a href="<?= e($rp) ?>ticket-details.php?id=<?= (int) $t['id'] ?>" class="py-3.5 flex items-center justify-between gap-4 hover:bg-white/[0.03] px-2 rounded-xl transition">
            <div class="min-w-0">
              <p class="text-sm font-semibold text-white truncate"><?= e($t['subject']) ?></p>
              <p class="text-xs text-slate-400 mt-0.5 font-mono-num">#<?= e($t['ticket_code']) ?> · <?= e(ucfirst($t['category'])) ?> · Updated <?= e(time_ago($t['updated_at'])) ?></p>
            </div>
            <span class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-800 text-teal-300 shrink-0"><?= e(TICKET_STATUSES[$t['status']] ?? $t['status']) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/layouts/dashboard.php';
