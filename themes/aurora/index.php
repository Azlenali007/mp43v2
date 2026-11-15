<?php
declare(strict_types=1);
if (!defined('APP_ROOT')) {
    http_response_code(403);
    exit('Forbidden');
}
$pageTitle = $pageTitle ?? 'Aurora Command Panel';
ob_start();
?>
<!-- Aurora Split Telemetry Hero -->
<section class="border-b border-slate-800/80">
  <div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 py-16 lg:py-24 grid lg:grid-cols-12 gap-12 items-center">
    <div class="lg:col-span-6 space-y-6">
      <div class="inline-flex items-center gap-2 px-3 py-1 rounded-lg bg-teal-500/10 border border-teal-500/30 text-xs font-semibold text-teal-300">
        Automated Fulfillment Engine
      </div>
      <h1 class="text-4xl sm:text-5xl font-bold tracking-tight text-white leading-[1.12] [text-wrap:balance]">
        High-Velocity Social Growth Command Center
      </h1>
      <p class="text-base text-slate-300 max-w-xl leading-relaxed">
        Execute Instagram, YouTube, Telegram, TikTok, and multi-network campaigns from a single dark command console with real-time FX conversion and atomic wallet security.
      </p>
      <div class="flex flex-wrap items-center gap-4 pt-2">
        <?php if (!empty($user)): ?>
          <a href="<?= e($rp) ?>new-order.php" class="px-6 py-3 rounded-xl aurora-btn-primary text-sm font-semibold transition shadow-sm">
            Launch New Order
          </a>
          <a href="<?= e($rp) ?>dashboard.php" class="px-6 py-3 rounded-xl border border-slate-700 bg-[#111A2E] text-white text-sm font-semibold hover:border-teal-400/50 transition">
            Open Console
          </a>
        <?php else: ?>
          <a href="<?= e($rp) ?>register.php" class="px-6 py-3 rounded-xl aurora-btn-primary text-sm font-semibold transition shadow-sm">
            Create Free Account
          </a>
          <a href="<?= e($rp) ?>services.php" class="px-6 py-3 rounded-xl border border-slate-700 bg-[#111A2E] text-white text-sm font-semibold hover:border-teal-400/50 transition">
            Inspect Catalog
          </a>
        <?php endif; ?>
      </div>
    </div>

    <div class="lg:col-span-6">
      <div class="aurora-hero-banner rounded-2xl p-6 sm:p-8 space-y-6 shadow-2xl">
        <div class="grid grid-cols-3 gap-4">
          <div class="p-4 rounded-xl bg-black/30 border border-white/10">
            <p class="text-2xl font-bold text-teal-300 font-mono-num"><?= number_format((int) $totalServicesCount) ?></p>
            <p class="text-xs text-slate-300 mt-1">Live Services</p>
          </div>
          <div class="p-4 rounded-xl bg-black/30 border border-white/10">
            <p class="text-2xl font-bold text-white font-mono-num"><?= number_format((int) $totalOrdersCount) ?></p>
            <p class="text-xs text-slate-300 mt-1">Orders Routed</p>
          </div>
          <div class="p-4 rounded-xl bg-black/30 border border-white/10">
            <p class="text-2xl font-bold text-indigo-300 font-mono-num">24/7</p>
            <p class="text-xs text-slate-300 mt-1">API Sync</p>
          </div>
        </div>

        <div class="grid grid-cols-3 sm:grid-cols-6 gap-2.5">
          <?php foreach (['instagram', 'youtube', 'telegram', 'facebook', 'tiktok', 'twitter'] as $plat): ?>
            <?php $reg = icon_registry()[$plat]; ?>
            <div class="p-2.5 rounded-xl bg-white/5 border border-white/10 flex flex-col items-center gap-1.5 text-center">
              <?= render_icon_badge($plat, 'w-8 h-8', 'p-1.5') ?>
              <span class="text-[11px] font-medium text-slate-200"><?= e($reg['label']) ?></span>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="pt-4 border-t border-white/10 flex flex-wrap items-center justify-between gap-2 text-xs text-teal-200/90">
          <span>Multi-Currency FX</span>
          <span>Atomic MySQL Ledger</span>
          <span>Razorpay + UPI</span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Bento Service Categories -->
<section class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 py-16">
  <div class="flex flex-wrap items-end justify-between gap-4 mb-8">
    <div>
      <h2 class="text-2xl sm:text-3xl font-bold text-white">Channel Categories</h2>
      <p class="text-sm text-slate-400 mt-1">Select any social channel to filter live service tiers.</p>
    </div>
    <a href="<?= e($rp) ?>services.php" class="text-sm font-semibold text-teal-300 hover:underline">Explore All Services &rarr;</a>
  </div>

  <?php if (empty($categories)): ?>
    <div class="p-8 rounded-2xl aurora-surface text-center">
      <p class="text-sm font-medium text-white">No service categories have been published yet.</p>
      <p class="text-xs text-slate-400 mt-1">Enabled categories configured in Admin will populate here automatically.</p>
    </div>
  <?php else: ?>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <?php foreach (array_slice($categories, 0, 6) as $cat): ?>
        <?php require __DIR__ . '/components/service-card.php'; ?>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<!-- Featured Services Telemetry Table -->
<section class="border-y border-slate-800/80 bg-[#090F1C]/60 py-16">
  <div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-end justify-between gap-4 mb-8">
      <div>
        <h2 class="text-2xl sm:text-3xl font-bold text-white">Live Rate Sheet</h2>
        <p class="text-sm text-slate-400 mt-1">Real-time database pricing converted automatically to your display currency.</p>
      </div>
      <a href="<?= e($rp) ?>services.php" class="text-sm font-semibold text-teal-300 hover:underline">Full Catalog &rarr;</a>
    </div>

    <?php if (empty($popularServices)): ?>
      <div class="p-8 rounded-2xl aurora-surface text-center">
        <p class="text-sm font-medium text-white">No services are currently listed.</p>
      </div>
    <?php else: ?>
      <div class="aurora-surface rounded-2xl overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-800 text-xs font-semibold text-slate-400 bg-[#0B1220]/70">
              <th class="py-3.5 px-4">ID</th>
              <th class="py-3.5 px-4">Service</th>
              <th class="py-3.5 px-4">Category</th>
              <th class="py-3.5 px-4 text-right">Rate / 1000</th>
              <th class="py-3.5 px-4 text-right">Min / Max</th>
              <th class="py-3.5 px-4 text-right">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800/80 text-sm">
            <?php foreach ($popularServices as $svc): ?>
              <tr class="hover:bg-white/[0.02]">
                <td class="py-3.5 px-4 font-mono-num text-xs text-teal-400">#<?= (int) $svc['id'] ?></td>
                <td class="py-3.5 px-4">
                  <div class="flex items-center gap-3">
                    <?= render_icon_badge($svc['icon_key'] ?: $svc['category_icon'] ?: 'sparkles', 'w-8 h-8', 'p-1.5') ?>
                    <span class="font-medium text-white"><?= e($svc['name']) ?></span>
                  </div>
                </td>
                <td class="py-3.5 px-4 text-slate-300"><?= e($svc['category_name']) ?></td>
                <td class="py-3.5 px-4 text-right font-mono-num font-semibold text-teal-300"><?= e(format_currency($svc['price_per_1000'])) ?></td>
                <td class="py-3.5 px-4 text-right font-mono-num text-xs text-slate-400"><?= number_format((int) $svc['min_qty']) ?> / <?= number_format((int) $svc['max_qty']) ?></td>
                <td class="py-3.5 px-4 text-right">
                  <a href="<?= e($rp) ?>new-order.php?service=<?= (int) $svc['id'] ?>" class="inline-flex px-3.5 py-1.5 rounded-lg aurora-btn-primary text-xs font-semibold transition">Order</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- Workflow & Support CTA -->
<section class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 py-16 space-y-12">
  <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
    <?php
    $steps = [
        ['01', 'Initialize Account', 'Create your personal or agency console account with API access enabled out of the box.'],
        ['02', 'Fund Your Wallet', 'Deposit securely via Razorpay or verified bank/UPI requests with automatic base accounting.'],
        ['03', 'Configure Order', 'Search instant rates, set quantity within bounds, and inspect base + display conversion.'],
        ['04', 'Monitor Live', 'Track provider status, start counts, and automated refunds cleanly from one dashboard.'],
    ];
    foreach ($steps as [$num, $title, $desc]):
    ?>
      <div class="p-6 rounded-2xl aurora-surface">
        <span class="inline-flex w-9 h-9 rounded-xl bg-teal-500/15 border border-teal-500/30 text-teal-300 text-sm font-bold items-center justify-center font-mono-num"><?= e($num) ?></span>
        <h3 class="mt-4 text-base font-semibold text-white"><?= e($title) ?></h3>
        <p class="mt-2 text-sm text-slate-400 leading-relaxed"><?= e($desc) ?></p>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="p-8 rounded-2xl aurora-hero-banner flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
    <div>
      <h2 class="text-xl font-bold text-white">Need Help Configuring Orders or API Integration?</h2>
      <p class="text-sm text-slate-200 mt-1">Check our knowledge base or open a priority ticket with our technical desk.</p>
    </div>
    <div class="flex items-center gap-3 shrink-0">
      <a href="<?= e($rp) ?>faq.php" class="px-5 py-2.5 rounded-xl bg-black/30 border border-white/20 text-sm font-semibold text-white hover:bg-black/45">Read FAQ</a>
      <a href="<?= e($rp) ?>contact.php" class="px-5 py-2.5 rounded-xl bg-white text-[#0B1220] text-sm font-bold hover:bg-teal-50">Contact Support</a>
    </div>
  </div>
</section>
<?php
$pageContent = ob_get_clean();
require __DIR__ . '/layouts/main.php';
