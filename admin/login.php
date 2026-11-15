<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
ensure_installed();
require_once __DIR__ . '/../classes/Auth.php';

if (current_admin()) {
    redirect(app_root_prefix() . 'admin/dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    write_app_log('app', 'ADMIN_LOGIN_POST_RECEIVED');
    verify_csrf_or_abort();
    if (!check_rate_limit('admin_login', 8, 300)) {
        $error = 'Too many sign-in attempts. Please wait a few minutes.';
    } else {
        $email = sanitize_string($_POST['email'] ?? '', 190);
        $password = (string) ($_POST['password'] ?? '');
        if (!is_valid_email($email) || $password === '') {
            $error = 'Enter a valid administrator email and password.';
        } else {
            write_app_log('app', 'ADMIN_LOGIN_VALIDATION_PASSED');
            $res = Auth::loginAdmin($email, $password);
            if ($res['ok']) {
                write_app_log('app', 'ADMIN_DATABASE_AUTH_SUCCESS & SESSION_CREATED for admin #' . ($res['admin']['id'] ?? 0));
                write_app_log('app', 'ADMIN_REDIRECT_ATTEMPTED to admin/dashboard.php');
                redirect(app_root_prefix() . 'admin/dashboard.php');
            }
            $error = $res['error'] ?? 'Invalid admin credentials.';
            write_app_log('app', 'ADMIN_LOGIN_FAILED: ' . $error);
        }
    }
}

$siteName = get_setting('site_name', 'CrownSMM');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Administrator Sign In — <?= e($siteName) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="<?= e(app_root_prefix()) ?>assets/css/style.css">
</head>
<body class="bg-[#151515] min-h-screen flex items-center justify-center p-4">
  <div class="max-w-md w-full bg-white rounded-2xl p-8 shadow-2xl border border-slate-200">
    <div class="flex items-center gap-3 mb-6">
      <span class="w-10 h-10 rounded-xl bg-[#7138D8] flex items-center justify-center text-white">
        <svg viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5"><path d="M5 16L3 5l5.5 5L12 4l3.5 6L21 5l-2 11H5zm14 3c0 .6-.4 1-1 1H6c-.6 0-1-.4-1-1v-1h14v1z"/></svg>
      </span>
      <div>
        <h1 class="text-lg font-bold text-[#151515]"><?= e($siteName) ?></h1>
        <p class="text-xs text-slate-500">Administrator Console</p>
      </div>
    </div>

    <?php if ($error): ?>
      <div class="mb-4 p-3 rounded-xl bg-rose-50 border border-rose-200 text-xs font-medium text-rose-700"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php" class="space-y-4">
      <?= csrf_field() ?>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Administrator Email</label>
        <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required autofocus class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-[#7138D8]">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Password</label>
        <input type="password" name="password" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-[#7138D8]">
      </div>
      <button type="submit" class="w-full py-2.5 rounded-xl bg-[#7138D8] text-white text-sm font-semibold hover:bg-[#5B24C0] transition">
        Sign In to Admin Panel
      </button>
    </form>
  </div>
</body>
</html>
