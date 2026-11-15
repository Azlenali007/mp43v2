<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/includes/ai-client.php';
$user = require_user();

$enabledTools = Tools::all(true);
$aiCfg = AIClient::getConfig();

$pageTitle = 'Creator Tools';
require __DIR__ . '/../includes/user-header.php';
?>
<link rel="stylesheet" href="assets/css/tools.css">
<div class="space-y-6">
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h1 class="text-2xl font-bold text-[#151515]">Creator Tools</h1>
      <p class="text-sm text-slate-600 mt-1">Free growth utilities included with your panel account to craft better captions, bios, and YouTube SEO.</p>
    </div>
    <a href="<?= e(app_root_prefix()) ?>new-order.php" class="px-4 py-2 rounded-xl bg-[#7138D8] text-white text-xs font-semibold hover:bg-[#5E28C2] self-start">
      + Launch Campaign Order
    </a>
  </div>

  <?php if (!$aiCfg['has_key']): ?>
    <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 text-xs font-medium">
      AI generation API key has not been configured by the administrator yet. Once configured in Admin &gt; Creator Tools, live AI outputs will be enabled automatically.
    </div>
  <?php endif; ?>

  <?php if (empty($enabledTools)): ?>
    <div class="bg-white border border-slate-200 rounded-2xl p-10 text-center">
      <h2 class="text-base font-bold text-[#151515]">No Creator Tools Currently Enabled</h2>
      <p class="text-sm text-slate-500 mt-1">All creator tools are temporarily disabled by the administrator.</p>
    </div>
  <?php else: ?>
    <div class="grid md:grid-cols-3 gap-6">
      <?php foreach ($enabledTools as $tool): ?>
        <a href="<?= e($tool['route_path']) ?>" class="bg-white border border-slate-200 rounded-2xl p-6 hover:border-[#7138D8] transition flex flex-col justify-between gap-5 shadow-sm">
          <div class="space-y-4">
            <div class="flex items-center justify-between">
              <?= render_icon_badge((string) $tool['icon_key'], 'w-11 h-11', 'p-2.5') ?>
              <span class="px-2.5 py-1 rounded-lg bg-slate-100 text-[11px] font-mono-num font-semibold text-slate-600">
                <?= number_format((int) $tool['usage_count']) ?> runs
              </span>
            </div>
            <div>
              <h2 class="text-base font-bold text-[#151515]"><?= e($tool['name']) ?></h2>
              <p class="text-xs text-slate-600 mt-1.5 leading-relaxed"><?= e($tool['description']) ?></p>
            </div>
          </div>
          <span class="inline-flex items-center gap-1.5 text-xs font-bold text-[#7138D8]">
            <span>Open Tool</span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
          </span>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/user-footer.php'; ?>
