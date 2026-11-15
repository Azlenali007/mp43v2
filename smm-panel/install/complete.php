<?php
declare(strict_types=1);
require_once __DIR__ . '/_bootstrap.php';
installer_guard(true);

$info = $_SESSION['_install_just_completed'] ?? null;
if (!$info) {
    redirect('../index.php');
}
unset($_SESSION['_install_just_completed']);

installer_render_header(5, 'Installation Complete');
?>
<div class="space-y-6 text-center">
  <div class="w-14 h-14 mx-auto rounded-2xl bg-emerald-50 text-[#16A36A] flex items-center justify-center">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="w-7 h-7"><polyline points="20 6 9 17 4 12"/></svg>
  </div>

  <div>
    <h2 class="text-2xl font-bold text-[#151515]">Installation Verified &amp; Complete</h2>
    <p class="mt-2 text-sm text-slate-600">
      <strong><?= e($info['site_name']) ?></strong> has been installed and verified with <strong><?= (int) $info['tables_count'] ?></strong> MySQL tables. The web installer is now locked.
    </p>
  </div>

  <div class="p-4 rounded-xl border border-slate-200 bg-slate-50 text-left text-sm space-y-2 max-w-md mx-auto">
    <div class="flex justify-between">
      <span class="text-slate-500">Admin Email:</span>
      <span class="font-semibold text-[#151515]"><?= e($info['admin_email']) ?></span>
    </div>
    <div class="flex justify-between">
      <span class="text-slate-500">Installer Lock:</span>
      <span class="font-semibold text-[#16A36A]">Active</span>
    </div>
  </div>

  <div class="flex flex-wrap items-center justify-center gap-3 pt-4 border-t border-slate-100">
    <a href="../index.php" class="px-5 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-[#151515] hover:bg-slate-50">
      Open Public Website
    </a>
    <a href="../admin/login.php" class="px-6 py-2.5 rounded-xl bg-[#7138D8] text-white text-sm font-semibold hover:bg-[#5E28C2] transition">
      Go to Admin Login
    </a>
  </div>
</div>
<?php installer_render_footer(); ?>
