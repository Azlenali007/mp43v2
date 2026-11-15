<?php
declare(strict_types=1);
if (!defined('APP_ROOT')) {
    http_response_code(403);
    exit('Forbidden');
}
$pageTitle = $pageTitle ?? 'Privacy Policy';
ob_start();
?>
<div class="max-w-[860px] mx-auto px-4 sm:px-6 lg:px-8 py-12">
  <div class="bg-white border border-slate-200 rounded-2xl p-6 sm:p-10 space-y-6">
    <div>
      <h1 class="text-2xl sm:text-3xl font-bold text-[#151515]">Privacy Policy</h1>
      <p class="mt-1 text-xs text-slate-500">Effective Date: <?= date('F Y') ?></p>
    </div>
    <div class="space-y-4 text-sm text-slate-700 leading-relaxed">
      <p><?= e($siteName) ?> respects your privacy and protects account data using industry-standard security controls.</p>
      <h2 class="text-base font-semibold text-[#151515] pt-2">1. Data We Collect</h2>
      <p>We store your account name, email, username, encrypted password hash, order links, transaction history, and support communications solely to operate the platform.</p>
      <h2 class="text-base font-semibold text-[#151515] pt-2">2. Payment Security</h2>
      <p>Online card and UPI credentials are processed directly by Razorpay over encrypted TLS connections. We do not store card numbers or CVV codes on our servers.</p>
      <h2 class="text-base font-semibold text-[#151515] pt-2">3. Contact</h2>
      <p>For privacy inquiries, email <?= e(get_setting('support_email', 'support@example.com')) ?>.</p>
    </div>
  </div>
</div>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/layouts/main.php';
