<?php
declare(strict_types=1);
require_once __DIR__ . '/_bootstrap.php';
installer_guard();

if (empty($_SESSION['install_db']) || empty($_SESSION['install_setup'])) {
    redirect('database.php');
}

$db = $_SESSION['install_db'];
$setup = $_SESSION['install_setup'];

$errors = [];

try {
    $pdo = Database::testConnection(
        $db['host'],
        (int) $db['port'],
        $db['database'],
        $db['username'],
        $db['password']
    );

    $schemaSql = file_get_contents(APP_ROOT . '/database/schema.sql');
    $seedSql = file_get_contents(APP_ROOT . '/database/seed.sql');
    if ($schemaSql === false || $seedSql === false) {
        throw new RuntimeException('Unable to read SQL schema or seed files.');
    }

    // Split SQL statements safely and execute via PDO
    $executeSqlBatch = function (PDO $conn, string $sql): void {
        $lines = preg_split('/\R/', $sql);
        $buffer = '';
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '--')) {
                continue;
            }
            $buffer .= $line . "\n";
            if (str_ends_with($trimmed, ';')) {
                $conn->exec($buffer);
                $buffer = '';
            }
        }
        if (trim($buffer) !== '') {
            $conn->exec($buffer);
        }
    };

    $executeSqlBatch($pdo, $schemaSql);
    $executeSqlBatch($pdo, $seedSql);

    // Update initial site settings
    $settingStmt = $pdo->prepare(
        'INSERT INTO settings (setting_key, setting_value, setting_group) VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
    );
    $cronToken = bin2hex(random_bytes(20));
    $settingStmt->execute(['site_name', $setup['site_name'], 'general']);
    $settingStmt->execute(['support_email', $setup['support_email'], 'general']);
    $settingStmt->execute(['currency_code', $setup['currency_code'], 'general']);
    $settingStmt->execute(['currency_symbol', $setup['currency_symbol'], 'general']);
    $settingStmt->execute(['cron_token', $cronToken, 'system']);

    // Create initial Super Admin
    $adminHash = password_hash($setup['admin_password'], PASSWORD_BCRYPT, ['cost' => 12]);
    $checkAdmin = $pdo->prepare('SELECT id FROM admins WHERE email = ? LIMIT 1');
    $checkAdmin->execute([$setup['admin_email']]);
    if (!$checkAdmin->fetch()) {
        $insAdmin = $pdo->prepare(
            'INSERT INTO admins (role_id, name, email, password_hash, status) VALUES (1, ?, ?, ?, "active")'
        );
        $insAdmin->execute([$setup['admin_name'], $setup['admin_email'], $adminHash]);
    }

    // Verify all 21 required tables exist
    $requiredTables = [
        'users', 'admins', 'admin_roles', 'admin_permissions', 'categories', 'services',
        'providers', 'provider_service_mappings', 'orders', 'order_logs', 'wallet_transactions',
        'payments', 'payment_methods', 'payment_webhook_events', 'support_tickets',
        'ticket_messages', 'notifications', 'announcements', 'settings', 'activity_logs', 'api_logs',
    ];
    $existingTables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $missing = array_diff($requiredTables, $existingTables);
    if (!empty($missing)) {
        throw new RuntimeException('Installation verification failed. Missing tables: ' . implode(', ', $missing));
    }

    // Generate encryption key and database.local.php
    $secretKey = bin2hex(random_bytes(32));
    $secretPhp = "<?php\ndeclare(strict_types=1);\nreturn " . var_export($secretKey, true) . ";\n";
    if (file_put_contents(CONFIG_PATH . '/app.secret.php', $secretPhp, LOCK_EX) === false) {
        throw new RuntimeException('Failed to write config/app.secret.php');
    }

    $localDbConfig = [
        'host'     => $db['host'],
        'port'     => (int) $db['port'],
        'database' => $db['database'],
        'username' => $db['username'],
        'password' => $db['password'],
        'charset'  => 'utf8mb4',
    ];
    $dbPhp = "<?php\ndeclare(strict_types=1);\nreturn " . var_export($localDbConfig, true) . ";\n";
    if (file_put_contents(CONFIG_PATH . '/database.local.php', $dbPhp, LOCK_EX) === false) {
        throw new RuntimeException('Failed to write config/database.local.php');
    }

    // Create installation lock
    $lockData = json_encode([
        'installed_at' => date('c'),
        'tables_count' => count($existingTables),
        'php_version'  => PHP_VERSION,
    ], JSON_PRETTY_PRINT);
    if (file_put_contents(INSTALL_LOCK_FILE, $lockData, LOCK_EX) === false) {
        throw new RuntimeException('Failed to write installation lock file.');
    }

    unset($_SESSION['install_db'], $_SESSION['install_setup']);
    $_SESSION['_install_just_completed'] = [
        'site_name'    => $setup['site_name'],
        'admin_email'  => $setup['admin_email'],
        'tables_count' => count($existingTables),
    ];
    redirect('complete.php');
} catch (Throwable $e) {
    $errors[] = $e->getMessage();
}

installer_render_header(4, 'Installation Error');
?>
<div class="space-y-6">
  <h2 class="text-xl font-bold text-rose-700">Database Installation Failed</h2>
  <?php foreach ($errors as $err): ?>
    <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-sm text-rose-700"><?= e($err) ?></div>
  <?php endforeach; ?>
  <div class="flex items-center justify-between pt-4 border-t border-slate-100">
    <a href="database.php" class="px-5 py-2.5 rounded-xl border border-slate-200 text-sm font-medium text-slate-700 hover:bg-slate-50">Back to Database Setup</a>
  </div>
</div>
<?php installer_render_footer(); ?>
