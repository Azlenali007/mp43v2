<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
ensure_installed();

require_once __DIR__ . '/classes/Category.php';
require_once __DIR__ . '/classes/Service.php';

$pdo = Database::getConnection();
$categories = Category::all(true);
$popularServices = $pdo->query(
    'SELECT s.*, c.name AS category_name, c.icon_key AS category_icon
     FROM services s
     INNER JOIN categories c ON c.id = s.category_id
     WHERE s.status = "active" AND c.status = "active"
     ORDER BY s.sort_order ASC, s.id ASC
     LIMIT 6'
)->fetchAll();

$totalServicesCount = (int) $pdo->query('SELECT COUNT(*) FROM services WHERE status = "active"')->fetchColumn();
$totalOrdersCount = (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();

$siteName = get_setting('site_name', 'CrownSMM');
$pageTitle = 'Social Media Marketing Panel';
require __DIR__ . '/includes/public-header.php';
?>

<!-- Hero Section -->
<section class="relative overflow-hidden bg-[#F8F9FC] border-b border-slate-200">
  <div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 py-16 lg:py-24 grid lg:grid-cols-12 gap-12 items-center">
    <div class="lg:col-span-7 space-y-6">
      <h1 class="text-4xl sm:text-5xl font-bold tracking-tight text-[#151515] leading-[1.12] [text-wrap:balance]">
        Scale Your Social Presence With Precision &amp; Speed
      </h1>
      <p class="text-base sm:text-lg text-slate-600 max-w-2xl leading-relaxed">
        Manage Instagram, YouTube, Telegram, Facebook, and multi-channel campaigns from one unified dashboard with live order tracking, automated API fulfillment, and transparent INR pricing.
      </p>
      <div class="flex flex-wrap items-center gap-4 pt-2">
        <?php if (current_user()): ?>
          <a href="new-order.php" class="px-6 py-3 rounded-xl bg-[#7138D8] text-white text-sm font-semibold hover:bg-[#5B24C0] transition shadow-sm">
            Place New Order
          </a>
          <a href="dashboard.php" class="px-6 py-3 rounded-xl border border-slate-300 bg-white text-[#151515] text-sm font-semibold hover:bg-slate-50 transition">
            Open Dashboard
          </a>
        <?php else: ?>
          <a href="register.php" class="px-6 py-3 rounded-xl bg-[#7138D8] text-white text-sm font-semibold hover:bg-[#5B24C0] transition shadow-sm">
            Create Free Account
          </a>
          <a href="services.php" class="px-6 py-3 rounded-xl border border-slate-300 bg-white text-[#151515] text-sm font-semibold hover:bg-slate-50 transition">
            Browse Services
          </a>
        <?php endif; ?>
      </div>

      <div class="grid grid-cols-3 gap-6 pt-6 border-t border-slate-200 max-w-lg">
        <div>
          <p class="text-2xl font-bold text-[#151515] font-mono-num"><?= number_format($totalServicesCount) ?></p>
          <p class="text-xs text-slate-500 mt-0.5">Active Services</p>
        </div>
        <div>
          <p class="text-2xl font-bold text-[#151515] font-mono-num"><?= number_format($totalOrdersCount) ?></p>
          <p class="text-xs text-slate-500 mt-0.5">Orders Processed</p>
        </div>
        <div>
          <p class="text-2xl font-bold text-[#16A36A] font-mono-num">24/7</p>
          <p class="text-xs text-slate-500 mt-0.5">Automated Dispatch</p>
        </div>
      </div>
    </div>

    <div class="lg:col-span-5">
      <div class="hero-banner-gradient rounded-2xl p-6 sm:p-8 text-white shadow-xl border border-white/10 space-y-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs text-purple-200 font-medium">Supported Networks</p>
            <h2 class="text-xl font-bold mt-1">All Major Platforms</h2>
          </div>
          <span class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center">
            <svg viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 text-white"><path d="M5 16L3 5l5.5 5L12 4l3.5 6L21 5l-2 11H5zm14 3c0 .6-.4 1-1 1H6c-.6 0-1-.4-1-1v-1h14v1z"/></svg>
          </span>
        </div>

        <div class="grid grid-cols-3 gap-3">
          <?php foreach (['instagram', 'youtube', 'telegram', 'facebook', 'tiktok', 'twitter'] as $plat): ?>
            <?php $reg = icon_registry()[$plat]; ?>
            <div class="p-3 rounded-xl bg-white/10 border border-white/10 flex flex-col items-center gap-2 text-center">
              <?= render_icon_badge($plat, 'w-9 h-9', 'p-2') ?>
              <span class="text-xs font-medium text-white"><?= e($reg['label']) ?></span>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="pt-4 border-t border-white/15 flex items-center justify-between text-xs text-purple-100">
          <span>API v2 Ready</span>
          <span>Atomic Wallet Ledger</span>
          <span>INR Payments</span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Service Categories -->
<section class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 py-16">
  <div class="flex flex-wrap items-end justify-between gap-4 mb-8">
    <div>
      <h2 class="text-2xl sm:text-3xl font-bold text-[#151515]">Service Categories</h2>
      <p class="text-sm text-slate-600 mt-1">Explore active service categories configured for your campaigns.</p>
    </div>
    <a href="services.php" class="text-sm font-semibold text-[#7138D8] hover:underline">View All Services &rarr;</a>
  </div>

  <?php if (empty($categories)): ?>
    <div class="p-8 rounded-2xl border border-slate-200 bg-[#F8F9FC] text-center">
      <p class="text-sm font-medium text-slate-700">No service categories have been published yet.</p>
      <p class="text-xs text-slate-500 mt-1">Categories and services will appear here automatically once added by the site administrator.</p>
    </div>
  <?php else: ?>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <?php foreach (array_slice($categories, 0, 6) as $cat): ?>
        <a href="services.php?category=<?= (int) $cat['id'] ?>" class="p-5 rounded-2xl bg-white border border-slate-200 hover:border-[#7138D8] transition flex items-center gap-4">
          <?= render_icon_badge($cat['icon_key'] ?: 'globe', 'w-11 h-11', 'p-2.5') ?>
          <div class="min-w-0 flex-1">
            <h3 class="text-base font-semibold text-[#151515] truncate"><?= e($cat['name']) ?></h3>
            <p class="text-xs text-slate-500 mt-0.5 tabular-nums"><?= (int) $cat['active_services_count'] ?> active services</p>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<!-- Popular Services -->
<section class="bg-[#F8F9FC] border-y border-slate-200 py-16">
  <div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-end justify-between gap-4 mb-8">
      <div>
        <h2 class="text-2xl sm:text-3xl font-bold text-[#151515]">Featured Services</h2>
        <p class="text-sm text-slate-600 mt-1">Live database rates per 1,000 units with minimum and maximum order bounds.</p>
      </div>
      <a href="services.php" class="text-sm font-semibold text-[#7138D8] hover:underline">Full Price List &rarr;</a>
    </div>

    <?php if (empty($popularServices)): ?>
      <div class="p-8 rounded-2xl border border-slate-200 bg-white text-center">
        <p class="text-sm font-medium text-slate-700">No services are currently listed.</p>
        <p class="text-xs text-slate-500 mt-1">Services imported or created in the admin catalog will be displayed here in real time.</p>
      </div>
    <?php else: ?>
      <div class="bg-white rounded-2xl border border-slate-200 overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-200 text-xs font-semibold text-slate-500 bg-slate-50/70">
              <th class="py-3.5 px-4">ID</th>
              <th class="py-3.5 px-4">Service</th>
              <th class="py-3.5 px-4">Category</th>
              <th class="py-3.5 px-4 text-right">Rate / 1000</th>
              <th class="py-3.5 px-4 text-right">Min / Max</th>
              <th class="py-3.5 px-4 text-right">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-sm">
            <?php foreach ($popularServices as $svc): ?>
              <tr class="hover:bg-slate-50/70">
                <td class="py-3.5 px-4 font-mono-num text-xs text-slate-500">#<?= (int) $svc['id'] ?></td>
                <td class="py-3.5 px-4">
                  <div class="flex items-center gap-3">
                    <?= render_icon_badge($svc['icon_key'] ?: $svc['category_icon'] ?: 'sparkles', 'w-8 h-8', 'p-1.5') ?>
                    <span class="font-medium text-[#151515]"><?= e($svc['name']) ?></span>
                  </div>
                </td>
                <td class="py-3.5 px-4 text-slate-600"><?= e($svc['category_name']) ?></td>
                <td class="py-3.5 px-4 text-right font-mono-num font-semibold text-[#16A36A]"><?= e(format_currency($svc['price_per_1000'])) ?></td>
                <td class="py-3.5 px-4 text-right font-mono-num text-xs text-slate-600"><?= number_format((int) $svc['min_qty']) ?> / <?= number_format((int) $svc['max_qty']) ?></td>
                <td class="py-3.5 px-4 text-right">
                  <a href="new-order.php?service=<?= (int) $svc['id'] ?>" class="inline-flex px-3.5 py-1.5 rounded-lg bg-[#7138D8] text-white text-xs font-semibold hover:bg-[#5B24C0] transition">Order</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- How It Works & Features -->
<section class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 py-16">
  <div class="text-center max-w-2xl mx-auto mb-12">
    <h2 class="text-2xl sm:text-3xl font-bold text-[#151515]">How It Works</h2>
    <p class="text-sm text-slate-600 mt-2">Launch and monitor your social growth orders in four straightforward steps.</p>
  </div>

  <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
    <?php
    $steps = [
        ['01', 'Register Account', 'Create your personal or agency account in seconds with instant dashboard access.'],
        ['02', 'Add Wallet Funds', 'Deposit INR securely through enabled payment gateways or verified bank/UPI transfers.'],
        ['03', 'Submit Orders', 'Pick a category, choose your service, paste your link, and verify your order total.'],
        ['04', 'Track Delivery', 'Monitor real-time status updates, start counts, and remaining quantities live.'],
    ];
    foreach ($steps as [$num, $title, $desc]):
    ?>
      <div class="p-6 rounded-2xl border border-slate-200 bg-white">
        <span class="inline-flex w-9 h-9 rounded-xl bg-purple-50 text-[#7138D8] text-sm font-bold items-center justify-center font-mono-num"><?= e($num) ?></span>
        <h3 class="mt-4 text-base font-semibold text-[#151515]"><?= e($title) ?></h3>
        <p class="mt-2 text-sm text-slate-600 leading-relaxed"><?= e($desc) ?></p>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- FAQ Preview -->
<section class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 pb-16">
  <div class="p-8 rounded-2xl bg-[#F8F9FC] border border-slate-200 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
    <div>
      <h2 class="text-xl font-bold text-[#151515]">Have Questions About Orders or Payments?</h2>
      <p class="text-sm text-slate-600 mt-1">Read our detailed FAQ or reach out to our support team anytime.</p>
    </div>
    <div class="flex items-center gap-3 shrink-0">
      <a href="faq.php" class="px-5 py-2.5 rounded-xl border border-slate-300 bg-white text-sm font-semibold text-[#151515] hover:bg-slate-50">Read FAQ</a>
      <a href="contact.php" class="px-5 py-2.5 rounded-xl bg-[#151515] text-white text-sm font-semibold hover:bg-black">Contact Us</a>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/public-footer.php'; ?>
