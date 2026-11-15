<?php
declare(strict_types=1);
require_once __DIR__ . '/_bootstrap.php';
installer_guard();

installer_render_header(1, 'Welcome');
?>
<div class="space-y-6">
  <div>
    <h2 class="text-2xl font-bold text-[#151515]">Welcome to the Installation Wizard</h2>
    <p class="mt-2 text-sm text-slate-600 leading-relaxed">
      This guided installer will verify your server environment, connect to your MySQL database using PHP PDO, create all required tables, configure your site settings, and create your initial Super Administrator account.
    </p>
  </div>

  <div class="grid sm:grid-cols-2 gap-4">
    <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/60">
      <h3 class="text-sm font-semibold text-[#151515]">Before You Begin</h3>
      <ul class="mt-2 space-y-1.5 text-xs text-slate-600">
        <li>• MySQL 8+ or MariaDB database name &amp; user credentials</li>
        <li>• PHP 8.2 or higher with PDO MySQL &amp; cURL enabled</li>
        <li>• Writable permissions on config, uploads &amp; storage folders</li>
      </ul>
    </div>
    <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/60">
      <h3 class="text-sm font-semibold text-[#151515]">Security &amp; Lock</h3>
      <ul class="mt-2 space-y-1.5 text-xs text-slate-600">
        <li>• Passwords are salted and hashed with bcrypt</li>
        <li>• Database credentials are written outside web assets</li>
        <li>• Installer locks automatically upon completion</li>
      </ul>
    </div>
  </div>

  <div class="flex items-center justify-end pt-4 border-t border-slate-100">
    <a href="requirements.php" class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-[#7138D8] text-white text-sm font-semibold hover:bg-[#5E28C2] transition">
      Start System Check
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4"><polyline points="9 18 15 12 9 6"/></svg>
    </a>
  </div>
</div>
<?php installer_render_footer(); ?>
