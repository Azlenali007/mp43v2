<?php
declare(strict_types=1);

/**
 * Database Configuration Loader
 * During web installation, config/database.local.php is generated securely.
 */
$localConfigPath = __DIR__ . '/database.local.php';
if (file_exists($localConfigPath)) {
    return require $localConfigPath;
}

return [
    'host'     => getenv('DB_HOST') ?: '127.0.0.1',
    'port'     => (int) (getenv('DB_PORT') ?: 3306),
    'database' => getenv('DB_NAME') ?: '',
    'username' => getenv('DB_USER') ?: '',
    'password' => getenv('DB_PASS') ?: '',
    'charset'  => 'utf8mb4',
];
