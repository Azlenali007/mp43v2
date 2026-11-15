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
    $baseCode = strtoupper(trim((string) ($setup['currency_code'] ?? 'INR'))) ?: 'INR';
    $baseSymbol = trim((string) ($setup['currency_symbol'] ?? '₹')) ?: '₹';
    $settingStmt->execute(['site_name', $setup['site_name'], 'general']);
    $settingStmt->execute(['support_email', $setup['support_email'], 'general']);
    $settingStmt->execute(['currency_code', $baseCode, 'general']);
    $settingStmt->execute(['currency_symbol', $baseSymbol, 'general']);
    $settingStmt->execute(['default_currency_code', $baseCode, 'currency']);
    $settingStmt->execute(['cron_token', $cronToken, 'system']);

    // Ensure chosen base currency is configured cleanly in currencies table
    $pdo->exec('UPDATE currencies SET is_base = 0, is_default = 0');
    $curCheck = $pdo->prepare('SELECT id FROM currencies WHERE code = ? LIMIT 1');
    $curCheck->execute([$baseCode]);
    if ($curCheck->fetch()) {
        $pdo->prepare('UPDATE currencies SET symbol = ?, is_base = 1, is_default = 1, exchange_rate = 1.00000000, rate_source = "base", is_enabled = 1, last_rate_updated_at = NOW() WHERE code = ?')
            ->execute([$baseSymbol, $baseCode]);
    } else {
        $pdo->prepare('INSERT INTO currencies (code, name, symbol, decimal_places, is_base, is_default, rate_mode, exchange_rate, rate_source, is_enabled, sort_order, last_rate_updated_at) VALUES (?, ?, ?, 2, 1, 1, "auto", 1.00000000, "base", 1, 0, NOW())')
            ->execute([$baseCode, $baseCode, $baseSymbol]);
    }
    if ($baseCode !== 'INR') {
        $pdo->prepare('UPDATE currencies SET exchange_rate = NULL, rate_source = "unconfigured", last_rate_updated_at = NULL WHERE code = "INR" AND is_base = 0')->execute();
    }

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

    // Verify all 28 required tables exist
    $requiredTables = [
        'users', 'admins', 'admin_roles', 'admin_permissions', 'categories', 'services',
        'providers', 'provider_service_mappings', 'orders', 'order_logs', 'wallet_transactions',
        'payments', 'payment_methods', 'payment_webhook_events', 'support_tickets',
        'ticket_messages', 'notifications', 'announcements', 'settings', 'activity_logs', 'api_logs',
        'themes', 'currencies', 'exchange_rates', 'exchange_rate_logs', 'currency_conversion_logs',
        'tools', 'tool_usage_logs',
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
