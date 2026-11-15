-- Incremental Upgrade Reference SQL (Executed automatically via Migrator::ensureUpgraded)

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
  `exchange_rate` DECIMAL(18,8) NULL DEFAULT NULL,
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

CREATE TABLE IF NOT EXISTS `exchange_rates` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `base_currency` VARCHAR(10) NOT NULL,
  `target_currency` VARCHAR(10) NOT NULL,
  `rate` DECIMAL(18,8) NOT NULL,
  `source` VARCHAR(80) NOT NULL DEFAULT 'manual',
  `fetched_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_exrate_pair` (`base_currency`, `target_currency`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `exchange_rate_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `provider` VARCHAR(80) NOT NULL,
  `base_currency` VARCHAR(10) NOT NULL,
  `status` ENUM('success','failed','partial') NOT NULL,
  `updated_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `message` VARCHAR(500) NULL DEFAULT NULL,
  `raw_payload` MEDIUMTEXT NULL DEFAULT NULL,
  `triggered_by` VARCHAR(40) NOT NULL DEFAULT 'manual',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_exrate_logs_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `currency_conversion_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `context` VARCHAR(60) NOT NULL,
  `source_currency` VARCHAR(10) NOT NULL,
  `target_currency` VARCHAR(10) NOT NULL,
  `source_amount` DECIMAL(18,6) NOT NULL,
  `target_amount` DECIMAL(18,6) NOT NULL,
  `exchange_rate` DECIMAL(18,8) NOT NULL,
  `reference_id` INT UNSIGNED NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_conv_logs_context` (`context`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tools` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(60) NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `description` VARCHAR(300) NULL DEFAULT NULL,
  `icon_key` VARCHAR(60) NOT NULL DEFAULT 'sparkles',
  `category_label` VARCHAR(60) NOT NULL DEFAULT 'Growth',
  `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` INT NOT NULL DEFAULT 0,
  `usage_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_tools_slug` (`slug`),
  KEY `idx_tools_enabled` (`is_enabled`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tool_usage_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tool_slug` VARCHAR(60) NOT NULL,
  `user_id` INT UNSIGNED NULL DEFAULT NULL,
  `provider` VARCHAR(50) NOT NULL DEFAULT 'gemini',
  `model` VARCHAR(80) NULL DEFAULT NULL,
  `input_summary` VARCHAR(500) NULL DEFAULT NULL,
  `status` ENUM('success','error','rate_limited') NOT NULL DEFAULT 'success',
  `error_message` VARCHAR(500) NULL DEFAULT NULL,
  `latency_ms` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tool_logs_slug` (`tool_slug`, `created_at`),
  KEY `idx_tool_logs_user` (`user_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
