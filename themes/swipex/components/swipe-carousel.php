<?php
declare(strict_types=1);
$rp = $rootPrefix ?? app_root_prefix();

$carouselCards = [
    [
        'title' => 'New Order',
        'desc' => 'Place a new order for your social media',
        'link' => $rp . 'new-order.php',
        'gradient' => 'from-blue-600 to-indigo-700',
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-8 h-8"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>'
    ],
    [
        'title' => 'Services',
        'desc' => 'Explore available social media services',
        'link' => $rp . 'services.php',
        'gradient' => 'from-indigo-600 to-purple-700',
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-8 h-8"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>'
    ],
    [
        'title' => 'My Orders',
        'desc' => 'Track and manage your orders',
        'link' => $rp . 'orders.php',
        'gradient' => 'from-blue-700 to-blue-900',
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-8 h-8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>'
    ],
    [
        'title' => 'Add Funds',
        'desc' => 'Add balance to your wallet',
        'link' => $rp . 'add-funds.php',
        'gradient' => 'from-purple-600 to-pink-600',
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-8 h-8"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>'
    ],
    [
        'title' => 'Transactions',
        'desc' => 'View your wallet activity',
        'link' => $rp . 'transactions.php',
        'gradient' => 'from-slate-800 to-indigo-900',
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-8 h-8"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>'
    ],
    [
        'title' => 'Support Center',
        'desc' => 'Get help with your orders',
        'link' => $rp . 'tickets.php',
        'gradient' => 'from-blue-600 to-purple-600',
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-8 h-8"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>'
    ],
];
?>
<div class="relative py-4">
  <!-- Horizontal Swipe Carousel Track -->
  <div id="swipeCarouselTrack" class="swipe-carousel-container flex items-center gap-4 overflow-x-auto snap-x snap-mandatory px-4 sm:px-8 py-4">
    <?php foreach ($carouselCards as $idx => $card): ?>
      <div class="swipe-card-item w-[85vw] sm:w-[420px] shrink-0 snap-center">
        <div class="bg-gradient-to-br <?= e($card['gradient']) ?> rounded-3xl p-8 text-white shadow-xl shadow-blue-500/15 flex flex-col items-center justify-between text-center min-h-[340px] relative overflow-hidden border border-white/10">
          <!-- Background decorative glow -->
          <div class="absolute -top-12 -right-12 w-40 h-40 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>

          <!-- Icon Badge -->
          <div class="w-16 h-16 rounded-2xl bg-white/20 border border-white/30 flex items-center justify-center text-white backdrop-blur-md shadow-inner my-2">
            <?= $card['icon'] ?>
          </div>

          <!-- Title & Description -->
          <div class="space-y-2 my-auto">
            <h3 class="text-2xl font-black tracking-tight"><?= e($card['title']) ?></h3>
            <p class="text-xs text-white/80 max-w-[280px] mx-auto leading-relaxed"><?= e($card['desc']) ?></p>
          </div>

          <!-- Open Button -->
          <div class="pt-4 w-full">
            <a href="<?= e($card['link']) ?>" class="w-full inline-flex items-center justify-center gap-2 py-3.5 px-6 rounded-2xl bg-white text-slate-900 font-extrabold text-xs shadow-md hover:bg-slate-50 transition">
              Open &rarr;
            </a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Pagination Dots Indicator -->
  <div class="flex items-center justify-center gap-2 mt-4">
    <?php foreach ($carouselCards as $idx => $card): ?>
      <button type="button" class="carousel-dot h-2 rounded-full transition-all duration-300 <?= $idx === 0 ? 'bg-blue-600 w-6' : 'bg-slate-300 w-2' ?>" data-index="<?= (int) $idx ?>"></button>
    <?php endforeach; ?>
  </div>
</div>
