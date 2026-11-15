-- Migration 002: Safe Idempotent Schema Stability, Multi-Currency & Theme Sync
-- Strictly preserves existing users, balances, payment configs, orders, and active theme.

CREATE TABLE IF NOT EXISTS `themes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(60) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `description` VARCHAR(255) NULL DEFAULT NULL,
  `version` VARCHAR(30) NOT NULL DEFAULT '1.0.0',
  `author` VARCHAR(80) NOT NULL DEFAULT 'System',
  `preview_accent` VARCHAR(30) NOT NULL DEFAULT '#7138D8',
  `is_active` TINYINT(1) NOT NULL DEFAULT 0,
  `status` ENUM('active','disabled') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_themes_slug` (`slug`),
  KEY `idx_themes_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `currencies` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(10) NOT NULL,
  `name` VARCHAR(80) NOT NULL,
  `symbol` VARCHAR(12) NOT NULL,
  `decimal_places` TINYINT UNSIGNED NOT NULL DEFAULT 2,
  `is_base` TINYINT(1) NOT NULL DEFAULT 0,
  `is_default` TINYINT(1) NOT NULL DEFAULT 0,
  `rate_mode` ENUM('auto','manual') NOT NULL DEFAULT 'auto',
  `exchange_rate` DECIMAL(18,8) NULL DEFAULT NULL COMMENT 'Units of Base Currency per 1 unit of this currency',
  `rate_source` VARCHAR(80) NOT NULL DEFAULT 'unconfigured',
  `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` INT NOT NULL DEFAULT 0,
  `last_rate_updated_at` DATETIME NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_currencies_code` (`code`),
  KEY `idx_currencies_flags` (`is_base`, `is_default`, `is_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `themes` (`slug`, `name`, `description`, `version`, `author`, `preview_accent`, `is_active`, `status`) VALUES
('classic', 'Classic Studio', 'Crisp white & obsidian workspace with royal purple highlights, glass KPI cards, and clean tabular typography.', '1.0.0', 'Panel Core', '#7138D8', 1, 'active'),
('aurora', 'Aurora Horizon', 'Atmospheric deep-navy & emerald-teal command layout with elevated split cards and crisp luminous contrast.', '1.0.0', 'Panel Core', '#0D9488', 0, 'active');

INSERT IGNORE INTO `currencies` (`code`, `name`, `symbol`, `decimal_places`, `is_base`, `is_default`, `rate_mode`, `exchange_rate`, `rate_source`, `is_enabled`, `sort_order`, `last_rate_updated_at`) VALUES
('INR', 'Indian Rupee', '₹', 2, 1, 1, 'auto', 1.00000000, 'base', 1, 1, NOW()),
('USD', 'US Dollar', '$', 2, 0, 0, 'auto', NULL, 'unconfigured', 1, 2, NULL),
('EUR', 'Euro', '€', 2, 0, 0, 'auto', NULL, 'unconfigured', 1, 3, NULL),
('GBP', 'British Pound', '£', 2, 0, 0, 'auto', NULL, 'unconfigured', 1, 4, NULL);
