<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/validation.php';

app_start_session();

function installer_guard(bool $allowComplete = false): void
{
    if (app_is_installed()) {
        if ($allowComplete && !empty($_SESSION['_install_just_completed'])) {
            return;
        }
        http_response_code(403);
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Installer Locked</title><script src="https://cdn.tailwindcss.com"></script></head><body class="bg-[#F8F9FC] min-h-screen flex items-center justify-center p-6"><div class="max-w-md w-full bg-white border border-slate-200 rounded-2xl p-8 text-center shadow-sm"><h1 class="text-xl font-bold text-[#151515]">Installation Already Completed</h1><p class="mt-2 text-sm text-slate-600">The web installer has been locked for security. Remove <code>config/installed.lock</code> only if you intentionally need to reinstall.</p><a href="../index.php" class="mt-6 inline-flex items-center justify-center px-5 py-2.5 rounded-xl bg-[#7138D8] text-white text-sm font-semibold hover:bg-[#5E28C2] transition">Go to Website</a></div></body></html>';
        exit;
    }
}

function installer_requirements_status(): array
{
    $phpOk = version_compare(PHP_VERSION, '8.2.0', '>=');
    $extensions = [
        'pdo'       => extension_loaded('pdo'),
        'pdo_mysql' => extension_loaded('pdo_mysql'),
        'curl'      => extension_loaded('curl'),
        'mbstring'  => extension_loaded('mbstring'),
        'openssl'   => extension_loaded('openssl'),
        'json'      => extension_loaded('json'),
        'fileinfo'  => extension_loaded('fileinfo'),
        'gd'        => extension_loaded('gd'),
        'xml'       => extension_loaded('xml'),
    ];

    $dirs = [
        'config'           => CONFIG_PATH,
        'uploads/payments' => UPLOADS_PATH . '/payments',
        'uploads/tickets'  => UPLOADS_PATH . '/tickets',
        'storage/logs'     => LOGS_PATH,
        'storage/backups'  => BACKUPS_PATH,
    ];

    $dirStatus = [];
    foreach ($dirs as $label => $path) {
        if (!is_dir($path)) {
            @mkdir($path, 0755, true);
        }
        $dirStatus[$label] = is_dir($path) && is_writable($path);
    }

    $allPassed = $phpOk && !in_array(false, $extensions, true) && !in_array(false, $dirStatus, true);

    return [
        'php_version' => PHP_VERSION,
        'php_ok'      => $phpOk,
        'extensions'  => $extensions,
        'directories' => $dirStatus,
        'all_passed'  => $allPassed,
    ];
}

function installer_render_header(int $currentStep, string $title): void
{
    $steps = [
        1 => 'Welcome',
        2 => 'Requirements',
        3 => 'Database',
        4 => 'Admin & Setup',
        5 => 'Complete',
    ];
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($title) ?> — Panel Setup</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="assets/css/install.css">
</head>
<body class="min-h-screen bg-[#F8F9FC] text-[#151515] py-10 px-4">
  <div class="max-w-3xl mx-auto">
    <div class="flex items-center justify-between mb-8">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-[#7138D8] flex items-center justify-center text-white shadow-sm">
          <svg viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5"><path d="M5 16L3 5l5.5 5L12 4l3.5 6L21 5l-2 11H5zm14 3c0 .6-.4 1-1 1H6c-.6 0-1-.4-1-1v-1h14v1z"/></svg>
        </div>
        <div>
          <h1 class="text-lg font-bold tracking-tight text-[#151515]">SMM Panel Web Installer</h1>
          <p class="text-xs text-slate-500">Production Setup Wizard</p>
        </div>
      </div>
      <span class="text-xs font-medium text-slate-500">Step <?= $currentStep ?> of 5</span>
    </div>

    <div class="grid grid-cols-5 gap-2 mb-8">
      <?php foreach ($steps as $num => $label): ?>
        <?php
        $cls = $num < $currentStep ? 'step-done' : ($num === $currentStep ? 'step-active' : 'step-pending');
        ?>
        <div class="flex flex-col items-center text-center">
          <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold <?= $cls ?>">
            <?= $num < $currentStep ? '✓' : $num ?>
          </div>
          <span class="mt-1.5 text-[11px] font-medium <?= $num === $currentStep ? 'text-[#7138D8]' : 'text-slate-500' ?>"><?= e($label) ?></span>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="install-card p-6 sm:p-8">
    <?php
}

function installer_render_footer(): void
{
    ?>
    </div>
  </div>
  <script src="assets/js/install.js"></script>
</body>
</html>
    <?php
}
