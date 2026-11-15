<?php
declare(strict_types=1);

class Migrator
{
    private static bool $checkedThisRequest = false;

    /**
     * Full non-destructive schema & column upgrade (safe for existing installs & fresh installs).
     * Never overwrites existing user balances, payment settings, active themes, or site settings.
     */
    public static function ensureUpgraded(PDO $pdo): void
    {
        try {
            $schemaSql = @file_get_contents(APP_ROOT . '/database/schema.sql');
            if ($schemaSql !== false) {
                self::execSqlBatch($pdo, $schemaSql);
            }

            self::ensureCoreColumns($pdo);

            $mig002 = @file_get_contents(APP_ROOT . '/database/migrations/002_stability_and_theme_fixes.sql');
            if ($mig002 !== false) {
                self::execSqlBatch($pdo, $mig002);
            }

            $mig003 = @file_get_contents(APP_ROOT . '/database/migrations/003_security_hardening.sql');
            if ($mig003 !== false) {
                self::execSqlBatch($pdo, $mig003);
            }

            // Only seed defaults for brand-new databases where settings table is empty
            $settingsCount = (int) $pdo->query('SELECT COUNT(*) FROM settings')->fetchColumn();
            if ($settingsCount === 0) {
                $seedSql = @file_get_contents(APP_ROOT . '/database/seed.sql');
                if ($seedSql !== false) {
                    self::execSqlBatch($pdo, $seedSql);
                }
            }
        } catch (Throwable $e) {
            if (function_exists('write_app_log')) {
                write_app_log('migrator', 'Migration warning: ' . $e->getMessage());
            }
        }
    }

    /**
     * Lightweight per-request runtime safety check for pre-existing installations
     * missing upgraded columns (e.g. users.preferred_currency) or tables (currencies/themes).
     */
    public static function ensureSchemaReady(PDO $pdo): void
    {
        if (self::$checkedThisRequest) {
            return;
        }
        self::$checkedThisRequest = true;

        try {
            $missingPref = !self::hasColumn($pdo, 'users', 'preferred_currency');
            $missingCurrencies = !self::hasTable($pdo, 'currencies');
            $missingThemes = !self::hasTable($pdo, 'themes');
            $missingPayGw = !self::hasColumn($pdo, 'payments', 'gateway_currency');

            if ($missingPref || $missingCurrencies || $missingThemes || $missingPayGw) {
                self::ensureUpgraded($pdo);
            }
        } catch (Throwable $e) {
            if (function_exists('write_app_log')) {
                write_app_log('migrator', 'Schema readiness check failed: ' . $e->getMessage());
            }
        }
    }

    public static function ensureCoreColumns(PDO $pdo): void
    {
        self::ensureColumn($pdo, 'users', 'preferred_currency', 'VARCHAR(10) NULL DEFAULT NULL AFTER `spent_total`');
        self::ensureColumn($pdo, 'providers', 'currency_source', "ENUM('manual','api') NOT NULL DEFAULT 'manual' AFTER `currency`");
        self::ensureColumn($pdo, 'providers', 'exchange_rate_snapshot', 'DECIMAL(18,8) NULL DEFAULT NULL AFTER `currency_source`');
        self::ensureColumn($pdo, 'services', 'original_currency', 'VARCHAR(10) NULL DEFAULT NULL AFTER `type`');
        self::ensureColumn($pdo, 'services', 'original_provider_rate', 'DECIMAL(14,6) NOT NULL DEFAULT 0.000000 AFTER `original_currency`');
        self::ensureColumn($pdo, 'services', 'import_exchange_rate', 'DECIMAL(18,8) NOT NULL DEFAULT 1.00000000 AFTER `original_provider_rate`');
        self::ensureColumn($pdo, 'provider_service_mappings', 'external_currency', 'VARCHAR(10) NULL DEFAULT NULL AFTER `external_name`');
        self::ensureColumn($pdo, 'provider_service_mappings', 'exchange_rate_used', 'DECIMAL(18,8) NOT NULL DEFAULT 1.00000000 AFTER `external_rate`');

        self::ensureColumn($pdo, 'orders', 'base_currency', "VARCHAR(10) NOT NULL DEFAULT 'INR' AFTER `profit`");
        self::ensureColumn($pdo, 'orders', 'display_currency', "VARCHAR(10) NOT NULL DEFAULT 'INR' AFTER `base_currency`");
        self::ensureColumn($pdo, 'orders', 'exchange_rate', 'DECIMAL(18,8) NOT NULL DEFAULT 1.00000000 AFTER `display_currency`');
        self::ensureColumn($pdo, 'orders', 'displayed_charge', 'DECIMAL(14,4) NOT NULL DEFAULT 0.0000 AFTER `exchange_rate`');

        self::ensureColumn($pdo, 'payments', 'original_amount', 'DECIMAL(14,4) NOT NULL DEFAULT 0.0000 AFTER `currency`');
        self::ensureColumn($pdo, 'payments', 'original_currency', "VARCHAR(10) NOT NULL DEFAULT 'INR' AFTER `original_amount`");
        self::ensureColumn($pdo, 'payments', 'base_amount', 'DECIMAL(14,4) NOT NULL DEFAULT 0.0000 AFTER `original_currency`');
        self::ensureColumn($pdo, 'payments', 'base_currency', "VARCHAR(10) NOT NULL DEFAULT 'INR' AFTER `base_amount`");
        self::ensureColumn($pdo, 'payments', 'exchange_rate', 'DECIMAL(18,8) NOT NULL DEFAULT 1.00000000 AFTER `base_currency`');
        self::ensureColumn($pdo, 'payments', 'display_currency', "VARCHAR(10) NOT NULL DEFAULT 'INR' AFTER `exchange_rate`");
        self::ensureColumn($pdo, 'payments', 'displayed_amount', 'DECIMAL(14,4) NOT NULL DEFAULT 0.0000 AFTER `display_currency`');
        self::ensureColumn($pdo, 'payments', 'gateway_amount', 'DECIMAL(14,4) NOT NULL DEFAULT 0.0000 AFTER `displayed_amount`');
        self::ensureColumn($pdo, 'payments', 'gateway_currency', "VARCHAR(10) NOT NULL DEFAULT 'INR' AFTER `gateway_amount`");

        self::ensureColumn($pdo, 'wallet_transactions', 'original_amount', 'DECIMAL(14,4) NOT NULL DEFAULT 0.0000 AFTER `balance_after`');
        self::ensureColumn($pdo, 'wallet_transactions', 'original_currency', "VARCHAR(10) NOT NULL DEFAULT 'INR' AFTER `original_amount`");
        self::ensureColumn($pdo, 'wallet_transactions', 'base_currency', "VARCHAR(10) NOT NULL DEFAULT 'INR' AFTER `original_currency`");
        self::ensureColumn($pdo, 'wallet_transactions', 'exchange_rate', 'DECIMAL(18,8) NOT NULL DEFAULT 1.00000000 AFTER `base_currency`');
        self::ensureColumn($pdo, 'wallet_transactions', 'display_currency', "VARCHAR(10) NOT NULL DEFAULT 'INR' AFTER `exchange_rate`");
        self::ensureColumn($pdo, 'wallet_transactions', 'displayed_amount', 'DECIMAL(14,4) NOT NULL DEFAULT 0.0000 AFTER `display_currency`');
    }

    public static function hasTable(PDO $pdo, string $table): bool
    {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
        );
        $stmt->execute([$table]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public static function hasColumn(PDO $pdo, string $table, string $column): bool
    {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
        );
        $stmt->execute([$table, $column]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private static function ensureColumn(PDO $pdo, string $table, string $column, string $definition): void
    {
        if (!self::hasColumn($pdo, $table, $column)) {
            $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
        }
    }

    public static function execSqlBatch(PDO $pdo, string $sql): void
    {
        $lines = preg_split('/\R/', $sql);
        $buffer = '';
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '--')) {
                continue;
            }
            $buffer .= $line . "\n";
            if (str_ends_with($trimmed, ';')) {
                $pdo->exec($buffer);
                $buffer = '';
            }
        }
        if (trim($buffer) !== '') {
            $pdo->exec($buffer);
        }
    }
}
