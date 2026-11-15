-- OTP Marketplace Database Schema

CREATE TABLE IF NOT EXISTS `otp_providers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(120) NOT NULL DEFAULT 'SMS-Activate / DaisySMS',
  `api_url` VARCHAR(255) NOT NULL DEFAULT 'https://api.sms-activate.org/stg/handler.php',
  `api_key` TEXT NOT NULL,
  `api_type` VARCHAR(40) NOT NULL DEFAULT 'smsactivate',
  `balance` DECIMAL(14,4) NOT NULL DEFAULT 0.0000,
  `last_sync_at` DATETIME NULL DEFAULT NULL,
  `status` ENUM('active','disabled') NOT NULL DEFAULT 'active',
  `notes` TEXT NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_otp_prov_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `otp_countries` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `provider_id` INT UNSIGNED NULL DEFAULT NULL,
  `country_code` VARCHAR(20) NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `iso_code` VARCHAR(10) NULL DEFAULT NULL,
  `flag_icon` VARCHAR(50) NULL DEFAULT NULL,
  `service_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_otp_country` (`provider_id`, `country_code`),
  KEY `idx_otp_country_enabled` (`is_enabled`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `otp_services` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `provider_id` INT UNSIGNED NULL DEFAULT NULL,
  `external_service_id` VARCHAR(50) NOT NULL,
  `country_code` VARCHAR(20) NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `category` VARCHAR(60) NOT NULL DEFAULT 'general',
  `original_currency` VARCHAR(10) NOT NULL DEFAULT 'RUB',
  `original_provider_rate` DECIMAL(14,6) NOT NULL DEFAULT 0.000000,
  `import_exchange_rate` DECIMAL(18,8) NOT NULL DEFAULT 1.00000000,
  `provider_rate` DECIMAL(14,4) NOT NULL DEFAULT 0.0000,
  `margin_type` ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
  `margin_value` DECIMAL(10,4) NOT NULL DEFAULT 20.0000,
  `price_per_number` DECIMAL(14,4) NOT NULL DEFAULT 0.0000,
  `stock_status` VARCHAR(50) NOT NULL DEFAULT 'Available',
  `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_otp_service` (`provider_id`, `country_code`, `external_service_id`),
  KEY `idx_otp_svc_country` (`country_code`, `is_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `otp_orders` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_code` VARCHAR(32) NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `service_id` INT UNSIGNED NOT NULL,
  `provider_id` INT UNSIGNED NULL DEFAULT NULL,
  `provider_activation_id` VARCHAR(80) NULL DEFAULT NULL,
  `phone_number` VARCHAR(50) NOT NULL,
  `country_code` VARCHAR(20) NOT NULL,
  `cost` DECIMAL(14,4) NOT NULL DEFAULT 0.0000,
  `charge` DECIMAL(14,4) NOT NULL DEFAULT 0.0000,
  `profit` DECIMAL(14,4) NOT NULL DEFAULT 0.0000,
  `base_currency` VARCHAR(10) NOT NULL DEFAULT 'INR',
  `display_currency` VARCHAR(10) NOT NULL DEFAULT 'INR',
  `exchange_rate` DECIMAL(18,8) NOT NULL DEFAULT 1.00000000,
  `displayed_charge` DECIMAL(14,4) NOT NULL DEFAULT 0.0000,
  `status` ENUM('waiting','received','cancelled','expired','refunded','failed') NOT NULL DEFAULT 'waiting',
  `sms_code` VARCHAR(100) NULL DEFAULT NULL,
  `full_sms_text` TEXT NULL DEFAULT NULL,
  `expires_at` DATETIME NULL DEFAULT NULL,
  `received_at` DATETIME NULL DEFAULT NULL,
  `refunded_amount` DECIMAL(14,4) NOT NULL DEFAULT 0.0000,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_otp_order_code` (`order_code`),
  KEY `idx_otp_user_status` (`user_id`, `status`),
  KEY `idx_otp_activation` (`provider_activation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `otp_order_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `status` VARCHAR(40) NOT NULL,
  `message` VARCHAR(500) NOT NULL,
  `raw_response` TEXT NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_otp_logs_order` (`order_id`),
  CONSTRAINT `fk_otp_logs_order` FOREIGN KEY (`order_id`) REFERENCES `otp_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `otp_transactions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `type` ENUM('debit','refund') NOT NULL,
  `amount` DECIMAL(14,4) NOT NULL,
  `balance_before` DECIMAL(14,4) NOT NULL,
  `balance_after` DECIMAL(14,4) NOT NULL,
  `reference_id` INT UNSIGNED NULL DEFAULT NULL,
  `description` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_otp_tx_user` (`user_id`),
  CONSTRAINT `fk_otp_tx_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `otp_sync_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` VARCHAR(50) NOT NULL,
  `status` ENUM('success','failed','partial') NOT NULL,
  `updated_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `message` VARCHAR(500) NULL DEFAULT NULL,
  `raw_payload` MEDIUMTEXT NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_otp_sync_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `otp_settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` TEXT NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_otp_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
