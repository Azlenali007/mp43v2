<?php
declare(strict_types=1);
$rp = $rootPrefix ?? app_root_prefix();
$mobileTabs = [
    ['href' => $rp . 'dashboard.php', 'match' => 'dashboard.php', 'label' => 'Home', 'icon' => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>'],
    ['href' => $rp . 'new-order.php', 'match' => 'new-order.php', 'label' => 'Order', 'icon' => '<rect x="3" y="3" width="18" height="18" rx="2"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/>'],
    ['href' => $rp . 'orders.php', 'match' => 'orders.php', 'label' => 'Orders', 'icon' => '<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>'],
    ['href' => $rp . 'add-funds.php', 'match' => 'add-funds.php', 'label' => 'Wallet', 'icon' => '<rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/>'],
    ['href' => $rp . 'profile.php', 'match' => 'profile.php', 'label' => 'Profile', 'icon' => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>'],
];
?>
      </main>
    </div>
  </div>

  <!-- Mobile Bottom Navigation -->
  <nav class="md:hidden fixed bottom-0 inset-x-0 h-14 bg-white border-t border-slate-200 flex items-center justify-around z-30 px-2">
    <?php foreach ($mobileTabs as $tab): ?>
      <?php $isActive = ($currentScript ?? '') === $tab['match']; ?>
      <a href="<?= e($tab['href']) ?>" class="flex flex-col items-center justify-center gap-0.5 text-[11px] font-medium <?= $isActive ? 'text-[#7138D8]' : 'text-slate-500' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5"><?= $tab['icon'] ?></svg>
        <span><?= e($tab['label']) ?></span>
      </a>
    <?php endforeach; ?>
  </nav>

  <script src="<?= e($rp) ?>assets/js/app.js"></script>
  <script src="<?= e($rp) ?>assets/js/dashboard.js"></script>
  <script src="<?= e(Theme::assetUrl('js/theme.js')) ?>"></script>
</body>
</html>
