<?php
declare(strict_types=1);
if (!defined('APP_ROOT')) {
    http_response_code(403);
    exit('Forbidden');
}
/** Classic Service Category Card Component ($cat expected, optional $rp) */
$rp = $rootPrefix ?? app_root_prefix();
?>
<a href="<?= e($rp) ?>services.php?category=<?= (int) ($cat['id'] ?? 0) ?>" class="p-5 rounded-2xl bg-white border border-slate-200 hover:border-[#7138D8] transition flex items-center gap-4">
  <?= render_icon_badge(($cat['icon_key'] ?? '') ?: 'globe', 'w-11 h-11', 'p-2.5') ?>
  <div class="min-w-0 flex-1">
    <h3 class="text-base font-semibold text-[#151515] truncate"><?= e($cat['name'] ?? '') ?></h3>
    <p class="text-xs text-slate-500 mt-0.5 tabular-nums"><?= (int) ($cat['active_services_count'] ?? 0) ?> active services</p>
  </div>
</a>
