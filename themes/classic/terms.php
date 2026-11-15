<?php
declare(strict_types=1);
if (!defined('APP_ROOT')) {
    http_response_code(403);
    exit('Forbidden');
}
$pageTitle = $pageTitle ?? 'Terms & Conditions';
ob_start();
?>
<div class="max-w-[860px] mx-auto px-4 sm:px-6 lg:px-8 py-12">
  <div class="bg-white border border-slate-200 rounded-2xl p-6 sm:p-10 space-y-6">
    <div>
      <h1 class="text-2xl sm:text-3xl font-bold text-[#151515]">Terms &amp; Conditions</h1>
      <p class="mt-1 text-xs text-slate-500">Effective Date: <?= date('F Y') ?></p>
    </div>
    <div class="space-y-4 text-sm text-slate-700 leading-relaxed">
      <p>By creating an account or placing an order on <?= e($siteName) ?>, you agree to comply with these Terms &amp; Conditions.</p>
      <h2 class="text-base font-semibold text-[#151515] pt-2">1. Service Usage</h2>
      <p>All services must be used strictly for lawful marketing and promotional purposes. Target links must remain public and accessible for the duration of order fulfillment.</p>
      <h2 class="text-base font-semibold text-[#151515] pt-2">2. Wallet Deposits &amp; Payments</h2>
      <p>Funds added to your wallet are credited upon payment verification and are intended for ordering services on the platform. Duplicate or fraudulent payment claims will result in account suspension.</p>
      <h2 class="text-base font-semibold text-[#151515] pt-2">3. Refunds</h2>
      <p>Orders that cannot be delivered or are cancelled by an upstream provider are refunded proportionally to your platform wallet balance.</p>
    </div>
  </div>
</div>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/layouts/main.php';
