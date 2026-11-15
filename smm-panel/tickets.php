<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$user = require_user();
require_once __DIR__ . '/classes/Ticket.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();
    $subject = sanitize_string($_POST['subject'] ?? '', 200);
    $category = in_array($_POST['category'] ?? '', ['order', 'payment', 'api', 'general'], true) ? $_POST['category'] : 'general';
    $priority = in_array($_POST['priority'] ?? '', ['low', 'medium', 'high'], true) ? $_POST['priority'] : 'medium';
    $orderId = validate_positive_int($_POST['order_id'] ?? null);
    $message = sanitize_string($_POST['message'] ?? '', 3000);

    if ($subject === '' || $message === '') {
        $error = 'Subject and message are required.';
    } else {
        $attachment = null;
        if (!empty($_FILES['attachment']['name'])) {
            $up = validate_and_store_upload($_FILES['attachment'], 'tickets');
            if (!$up['ok']) {
                $error = $up['error'];
            } else {
                $attachment = $up['path'];
            }
        }
        if ($error === '') {
            $res = Ticket::create((int) $user['id'], $subject, $category, $priority, $message, $orderId, $attachment);
            if ($res['ok']) {
                set_flash('success', "Support ticket #{$res['ticket_code']} opened.");
                redirect('ticket-details.php?id=' . $res['ticket_id']);
            }
            $error = $res['error'];
        }
    }
}

$pdo = Database::getConnection();
$stmt = $pdo->prepare('SELECT * FROM support_tickets WHERE user_id = ? ORDER BY updated_at DESC');
$stmt->execute([(int) $user['id']]);
$tickets = $stmt->fetchAll();

$pageTitle = 'Support Tickets';
require __DIR__ . '/includes/user-header.php';
?>
<div class="grid lg:grid-cols-12 gap-6 items-start">
  <div class="lg:col-span-5 bg-white border border-slate-200 rounded-2xl p-6 space-y-4">
    <h1 class="text-lg font-bold text-[#151515]">Open New Ticket</h1>
    <?php if ($error): ?>
      <div class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-700 font-medium"><?= e($error) ?></div>
    <?php endif; ?>
    <form method="POST" action="tickets.php" enctype="multipart/form-data" class="space-y-4">
      <?= csrf_field() ?>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Subject</label>
        <input type="text" name="subject" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-[#7138D8]">
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1.5">Category</label>
          <select name="category" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm bg-white">
            <option value="order">Order Issue</option>
            <option value="payment">Payment / Wallet</option>
            <option value="api">API Support</option>
            <option value="general">General</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1.5">Priority</label>
          <select name="priority" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm bg-white">
            <option value="low">Low</option>
            <option value="medium" selected>Medium</option>
            <option value="high">High</option>
          </select>
        </div>
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Related Order ID (Optional)</label>
        <input type="number" name="order_id" placeholder="e.g. 12" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-mono-num">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Message</label>
        <textarea name="message" rows="4" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-[#7138D8]"></textarea>
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Attachment (Optional image, max 3MB)</label>
        <input type="file" name="attachment" accept="image/jpeg,image/png,image/webp" class="w-full text-xs">
      </div>
      <button type="submit" class="w-full py-2.5 rounded-xl bg-[#7138D8] text-white text-sm font-semibold hover:bg-[#5B24C0] transition">Submit Ticket</button>
    </form>
  </div>

  <div class="lg:col-span-7 bg-white border border-slate-200 rounded-2xl p-6">
    <h2 class="text-lg font-bold text-[#151515] mb-4">Your Support Tickets</h2>
    <?php if (empty($tickets)): ?>
      <p class="text-sm text-slate-500">You have not created any support tickets yet.</p>
    <?php else: ?>
      <div class="divide-y divide-slate-100">
        <?php foreach ($tickets as $t): ?>
          <a href="ticket-details.php?id=<?= (int) $t['id'] ?>" class="py-3.5 flex items-center justify-between gap-4 hover:bg-slate-50 px-2 rounded-xl transition">
            <div class="min-w-0">
              <p class="text-sm font-semibold text-[#151515] truncate"><?= e($t['subject']) ?></p>
              <p class="text-xs text-slate-500 mt-0.5 font-mono-num">#<?= e($t['ticket_code']) ?> · <?= e(ucfirst($t['category'])) ?> · Updated <?= e(time_ago($t['updated_at'])) ?></p>
            </div>
            <span class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 text-slate-700 shrink-0"><?= e(TICKET_STATUSES[$t['status']] ?? $t['status']) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/includes/user-footer.php'; ?>
