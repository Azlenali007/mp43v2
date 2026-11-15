<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
ensure_installed();

$user = current_user();
$faqs = [
    ['How quickly do orders start after placement?', 'Most services start automatically within minutes of submission. Each service lists its speed, minimum quantity, and description before you place your order.'],
    ['How do I add funds to my wallet?', 'Open the Add Funds page from your dashboard and choose any payment method enabled by the administrator (such as Razorpay online checkout or Manual UPI/Bank transfer).'],
    ['What happens if an order is cancelled or partially delivered?', 'If a provider marks an order as cancelled or failed, or completes a partial delivery, the unfulfilled balance is refunded automatically back to your wallet ledger.'],
    ['Can I connect my own website or script via API?', 'Yes. Visit the API Access page inside your user dashboard to copy your private API key and view the standard JSON v2 endpoint documentation.'],
    ['Is my wallet balance safe?', 'All wallet credits, order charges, and refunds execute inside atomic MySQL transactions with row-level locking and idempotency keys to prevent duplicate deductions or credits.'],
];

Theme::renderPage('faq.php', [
    'pageTitle' => 'Frequently Asked Questions',
    'user'      => $user,
    'faqs'      => $faqs,
]);
