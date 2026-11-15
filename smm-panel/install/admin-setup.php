<?php
declare(strict_types=1);
require_once __DIR__ . '/_bootstrap.php';
installer_guard();

if (empty($_SESSION['install_db'])) {
    redirect('database.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();
    $siteName = sanitize_string($_POST['site_name'] ?? 'CrownSMM', 80);
    $supportEmail = sanitize_string($_POST['support_email'] ?? '', 190);
    $currencyCode = strtoupper(sanitize_string($_POST['currency_code'] ?? 'INR', 10));
    $currencySymbol = sanitize_string($_POST['currency_symbol'] ?? '₹', 5);
    $adminName = sanitize_string($_POST['admin_name'] ?? '', 120);
    $adminEmail = sanitize_string($_POST['admin_email'] ?? '', 190);
    $adminPass = (string) ($_POST['admin_password'] ?? '');
    $adminPassConfirm = (string) ($_POST['admin_password_confirm'] ?? '');

    if ($siteName === '' || $adminName === '' || !is_valid_email($adminEmail)) {
        $error = 'Please provide a valid site name, administrator name, and email address.';
    } elseif (strlen($adminPass) < 8) {
        $error = 'Administrator password must be at least 8 characters long.';
    } elseif ($adminPass !== $adminPassConfirm) {
        $error = 'Administrator passwords do not match.';
    } else {
        $_SESSION['install_setup'] = [
            'site_name'       => $siteName,
            'support_email'   => is_valid_email($supportEmail) ? $supportEmail : $adminEmail,
            'currency_code'   => $currencyCode ?: 'INR',
            'currency_symbol' => $currencySymbol ?: '₹',
            'admin_name'      => $adminName,
            'admin_email'     => $adminEmail,
            'admin_password'  => $adminPass,
        ];
        redirect('install-process.php');
    }
}

installer_render_header(4, 'Administrator & Initial Settings');
?>
<div class="space-y-6">
  <div>
    <h2 class="text-xl font-bold text-[#151515]">Initial Settings &amp; Super Admin Account</h2>
    <p class="mt-1 text-sm text-slate-600">Configure your panel identity and create the primary Super Administrator account.</p>
  </div>

  <?php if ($error): ?>
    <div class="p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-sm text-rose-700 font-medium"><?= e($error) ?></div>
  <?php endif; ?>

  <form method="POST" action="admin-setup.php" class="space-y-5">
    <?= csrf_field() ?>

    <div class="p-4 rounded-xl border border-slate-200 space-y-4">
      <h3 class="text-sm font-semibold text-[#151515]">Panel Configuration</h3>
      <div class="grid sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1.5">Panel Name</label>
          <input type="text" name="site_name" value="<?= e($_POST['site_name'] ?? 'CrownSMM') ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-[#7138D8]">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1.5">Support Email</label>
          <input type="email" name="support_email" value="<?= e($_POST['support_email'] ?? '') ?>" placeholder="support@yourdomain.com" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-[#7138D8]">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1.5">Currency Code</label>
          <input type="text" name="currency_code" value="<?= e($_POST['currency_code'] ?? 'INR') ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-[#7138D8]">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1.5">Currency Symbol</label>
          <input type="text" name="currency_symbol" value="<?= e($_POST['currency_symbol'] ?? '₹') ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-[#7138D8]">
        </div>
      </div>
    </div>

    <div class="p-4 rounded-xl border border-slate-200 space-y-4">
      <h3 class="text-sm font-semibold text-[#151515]">Super Administrator Account</h3>
      <div class="grid sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1.5">Full Name</label>
          <input type="text" name="admin_name" value="<?= e($_POST['admin_name'] ?? '') ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-[#7138D8]">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1.5">Admin Email</label>
          <input type="email" name="admin_email" value="<?= e($_POST['admin_email'] ?? '') ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-[#7138D8]">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1.5">Password (min 8 chars)</label>
          <input type="password" name="admin_password" required minlength="8" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-[#7138D8]">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1.5">Confirm Password</label>
          <input type="password" name="admin_password_confirm" required minlength="8" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-[#7138D8]">
        </div>
      </div>
    </div>

    <div class="flex items-center justify-between pt-4 border-t border-slate-100">
      <a href="database.php" class="px-4 py-2 rounded-xl border border-slate-200 text-sm font-medium text-slate-700 hover:bg-slate-50">Back</a>
      <button type="submit" class="px-6 py-2.5 rounded-xl bg-[#7138D8] text-white text-sm font-semibold hover:bg-[#5E28C2] transition">
        Install Database &amp; Complete Setup
      </button>
    </div>
  </form>
</div>
<?php installer_render_footer(); ?>
