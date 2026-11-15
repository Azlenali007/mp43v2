<?php
declare(strict_types=1);
if (!defined('APP_ROOT')) {
    http_response_code(403);
    exit('Forbidden');
}
$rp = $rootPrefix ?? app_root_prefix();
?>
<a href="<?= e($rp) ?>services.php?category=<?= (int) ($cat['id'] ?? 0) ?>" class="group p-5 rounded-2xl aurora-surface hover:border-teal-400/50 transition flex items-center justify-between gap-4">
  <div class="flex items-center gap-4 min-w-0">
    <?= render_icon_badge(($cat['icon_key'] ?? '') ?: 'globe', 'w-11 h-11', 'p-2.5') ?>
    <div class="min-w-0">
      <h3 class="text-base font-semibold text-white group-hover:text-teal-300 transition truncate"><?= e($cat['name'] ?? '') ?></h3>
      <p class="text-xs text-slate-400 mt-0.5 tabular-nums"><?= (int) ($cat['active_services_count'] ?? 0) ?> active services</p>
    </div>
  </div>
  <span class="text-slate-500 group-hover:text-teal-300 transition text-sm shrink-0">&rarr;</span>
</a>
