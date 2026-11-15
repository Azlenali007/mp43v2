<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
ensure_installed();

require_once __DIR__ . '/classes/Ticket.php';

$user = current_user();
$successMsg = '';
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();
    if (!check_rate_limit('contact_form', 6, 600)) {
        $errorMsg = 'Too many messages sent recently. Please wait a few minutes.';
    } else {
        $name = sanitize_string($_POST['name'] ?? ($user['name'] ?? ''), 120);
        $email = sanitize_string($_POST['email'] ?? ($user['email'] ?? ''), 190);
        $subject = sanitize_string($_POST['subject'] ?? '', 200);
        $message = sanitize_string($_POST['message'] ?? '', 2000);

        if ($name === '' || !is_valid_email($email) || $subject === '' || $message === '') {
            $errorMsg = 'Please fill in all required fields with a valid email address.';
        } else {
            if ($user) {
                $tRes = Ticket::create((int) $user['id'], $subject, 'general', 'medium', $message);
                if ($tRes['ok']) {
                    $successMsg = "Your message has been logged as Ticket #{$tRes['ticket_code']}. Our team will reply shortly.";
                } else {
                    $errorMsg = $tRes['error'];
                }
            } else {
                log_activity('system', null, 'public_contact_inquiry', null, null, "From: {$name} <{$email}> | Subject: {$subject} | Message: {$message}");
                $successMsg = 'Thank you for contacting us. Your inquiry has been received by our support team.';
            }
        }
    }
}

$pageTitle = 'Contact Us';
if ($user) {
    require __DIR__ . '/includes/user-header.php';
} else {
    require __DIR__ . '/includes/public-header.php';
}
?>
<div class="<?= $user ? 'max-w-3xl' : 'max-w-[780px] mx-auto px-4 sm:px-6 lg:px-8 py-12' ?>">
  <div class="bg-white border border-slate-200 rounded-2xl p-6 sm:p-8 shadow-sm">
    <h1 class="text-2xl font-bold text-[#151515]">Contact Support</h1>
    <p class="mt-1 text-sm text-slate-600">Need assistance with an order, payment, or integration? Send us a message below.</p>

    <?php if ($successMsg): ?>
      <div class="mt-5 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-sm text-[#16A36A] font-medium"><?= e($successMsg) ?></div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
      <div class="mt-5 p-4 rounded-xl bg-rose-50 border border-rose-200 text-sm text-rose-700 font-medium"><?= e($errorMsg) ?></div>
    <?php endif; ?>

    <form method="POST" action="contact.php" class="mt-6 space-y-4">
      <?= csrf_field() ?>
      <div class="grid sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1.5">Your Name</label>
          <input type="text" name="name" value="<?= e($_POST['name'] ?? ($user['name'] ?? '')) ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-[#7138D8]">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1.5">Email Address</label>
          <input type="email" name="email" value="<?= e($_POST['email'] ?? ($user['email'] ?? '')) ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-[#7138D8]">
        </div>
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Subject</label>
        <input type="text" name="subject" value="<?= e($_POST['subject'] ?? '') ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-[#7138D8]">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Message</label>
        <textarea name="message" rows="5" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-[#7138D8]"><?= e($_POST['message'] ?? '') ?></textarea>
      </div>
      <div class="flex items-center justify-between pt-2">
        <span class="text-xs text-slate-500">Support email: <?= e(get_setting('support_email', 'support@example.com')) ?></span>
        <button type="submit" class="px-6 py-2.5 rounded-xl bg-[#7138D8] text-white text-sm font-semibold hover:bg-[#5B24C0] transition">
          Send Message
        </button>
      </div>
    </form>
  </div>
</div>
<?php
if ($user) {
    require __DIR__ . '/includes/user-footer.php';
} else {
    require __DIR__ . '/includes/public-footer.php';
}
