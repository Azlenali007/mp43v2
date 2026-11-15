<?php
declare(strict_types=1);
require_once __DIR__ . '/_bootstrap.php';
installer_guard();

$status = installer_requirements_status();
installer_render_header(2, 'System Requirements');
?>
<div class="space-y-6">
  <div>
    <h2 class="text-xl font-bold text-[#151515]">System Requirements &amp; Permissions</h2>
    <p class="mt-1 text-sm text-slate-600">All PHP extensions and folder write permissions below are verified live on this server.</p>
  </div>

  <div class="rounded-xl border border-slate-200 overflow-hidden">
    <div class="px-4 py-3 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
      <span class="text-xs font-semibold text-slate-700">PHP Runtime Version</span>
      <span class="text-xs font-semibold <?= $status['php_ok'] ? 'text-[#16A36A]' : 'text-rose-600' ?>">
        PHP <?= e($status['php_version']) ?> (<?= $status['php_ok'] ? 'Compatible' : 'Requires 8.2+' ?>)
      </span>
    </div>
    <div class="divide-y divide-slate-100">
      <?php foreach ($status['extensions'] as $ext => $loaded): ?>
        <div class="px-4 py-2.5 flex items-center justify-between text-sm">
          <span class="text-slate-700 font-medium">ext-<?= e($ext) ?></span>
          <?php if ($loaded): ?>
            <span class="text-xs font-semibold text-[#16A36A]">Loaded</span>
          <?php else: ?>
            <span class="text-xs font-semibold text-rose-600">Missing</span>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="rounded-xl border border-slate-200 overflow-hidden">
    <div class="px-4 py-3 bg-slate-50 border-b border-slate-200">
      <span class="text-xs font-semibold text-slate-700">Directory Write Permissions</span>
    </div>
    <div class="divide-y divide-slate-100">
      <?php foreach ($status['directories'] as $dir => $writable): ?>
        <div class="px-4 py-2.5 flex items-center justify-between text-sm">
          <span class="font-mono text-xs text-slate-700">/<?= e($dir) ?>/</span>
          <?php if ($writable): ?>
            <span class="text-xs font-semibold text-[#16A36A]">Writable</span>
          <?php else: ?>
            <span class="text-xs font-semibold text-rose-600">Not Writable</span>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="flex items-center justify-between pt-4 border-t border-slate-100">
    <a href="index.php" class="px-4 py-2 rounded-xl border border-slate-200 text-sm font-medium text-slate-700 hover:bg-slate-50">Back</a>
    <?php if ($status['all_passed']): ?>
      <a href="database.php" class="px-6 py-2.5 rounded-xl bg-[#7138D8] text-white text-sm font-semibold hover:bg-[#5E28C2] transition">
        Continue to Database Setup
      </a>
    <?php else: ?>
      <a href="requirements.php" class="px-6 py-2.5 rounded-xl bg-rose-600 text-white text-sm font-semibold hover:bg-rose-700 transition">
        Re-check Requirements
      </a>
    <?php endif; ?>
  </div>
</div>
<?php installer_render_footer(); ?>
