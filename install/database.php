<?php
declare(strict_types=1);
require_once __DIR__ . '/_bootstrap.php';
installer_guard();

$req = installer_requirements_status();
if (!$req['all_passed']) {
    redirect('requirements.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort(($_POST['action'] ?? '') === 'test_connection');
    $host = sanitize_string($_POST['db_host'] ?? '127.0.0.1', 120);
    $port = validate_positive_int($_POST['db_port'] ?? 3306, 1, 65535) ?? 3306;
    $name = sanitize_string($_POST['db_name'] ?? '', 120);
    $user = sanitize_string($_POST['db_user'] ?? '', 120);
    $pass = (string) ($_POST['db_pass'] ?? '');

    if ($host === '' || $name === '' || $user === '') {
        $msg = 'Database host, name, and username are required.';
        if (($_POST['action'] ?? '') === 'test_connection') {
            json_response(['ok' => false, 'message' => $msg]);
        }
        $error = $msg;
    } else {
        try {
            $pdo = Database::testConnection($host, $port, $name, $user, $pass);
            $version = (string) $pdo->query('SELECT VERSION()')->fetchColumn();
            if (($_POST['action'] ?? '') === 'test_connection') {
                json_response(['ok' => true, 'message' => 'Connection successful! Server version: ' . $version]);
            }
            $_SESSION['install_db'] = [
                'host'     => $host,
                'port'     => $port,
                'database' => $name,
                'username' => $user,
                'password' => $pass,
            ];
            redirect('admin-setup.php');
        } catch (Throwable $e) {
            $msg = 'MySQL Connection Error: ' . $e->getMessage();
            if (($_POST['action'] ?? '') === 'test_connection') {
                json_response(['ok' => false, 'message' => $msg]);
            }
            $error = $msg;
        }
    }
}

$saved = $_SESSION['install_db'] ?? ['host' => '127.0.0.1', 'port' => 3306, 'database' => '', 'username' => '', 'password' => ''];
installer_render_header(3, 'Database Configuration');
?>
<div class="space-y-6">
  <div>
    <h2 class="text-xl font-bold text-[#151515]">MySQL Database Configuration</h2>
    <p class="mt-1 text-sm text-slate-600">Enter your MySQL 8+ or MariaDB credentials. The connection is verified using PHP PDO before continuing.</p>
  </div>

  <?php if ($error): ?>
    <div class="p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-sm text-rose-700 font-medium"><?= e($error) ?></div>
  <?php endif; ?>

  <form id="dbForm" method="POST" action="database.php" class="space-y-4">
    <?= csrf_field() ?>
    <div class="grid sm:grid-cols-3 gap-4">
      <div class="sm:col-span-2">
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Database Host</label>
        <input type="text" name="db_host" value="<?= e($saved['host']) ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-[#7138D8]">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Port</label>
        <input type="number" name="db_port" value="<?= (int) $saved['port'] ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-[#7138D8]">
      </div>
    </div>

    <div>
      <label class="block text-xs font-semibold text-slate-700 mb-1.5">Database Name</label>
      <input type="text" name="db_name" value="<?= e($saved['database']) ?>" placeholder="e.g. smm_panel" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-[#7138D8]">
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Database Username</label>
        <input type="text" name="db_user" value="<?= e($saved['username']) ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-[#7138D8]">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Database Password</label>
        <input type="password" name="db_pass" value="<?= e($saved['password']) ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-[#7138D8]">
      </div>
    </div>

    <div id="dbTestResult" class="hidden"></div>

    <div class="flex flex-wrap items-center justify-between gap-3 pt-4 border-t border-slate-100">
      <a href="requirements.php" class="px-4 py-2 rounded-xl border border-slate-200 text-sm font-medium text-slate-700 hover:bg-slate-50">Back</a>
      <div class="flex items-center gap-3">
        <button type="button" id="testDbBtn" class="px-4 py-2.5 rounded-xl border border-[#7138D8] text-[#7138D8] text-sm font-semibold hover:bg-purple-50 transition">
          Test Connection
        </button>
        <button type="submit" class="px-6 py-2.5 rounded-xl bg-[#7138D8] text-white text-sm font-semibold hover:bg-[#5E28C2] transition">
          Save &amp; Continue
        </button>
      </div>
    </div>
  </form>
</div>
<?php installer_render_footer(); ?>
