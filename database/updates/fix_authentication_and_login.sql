-- ============================================================================
-- SQL Migration: fix_authentication_and_login.sql
-- Description: Ensures complete schema alignment for secure Admin and User login,
--              password hashes, roles, permissions, sessions and settings.
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. Ensure users table exists with correct authentication columns
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(120) NOT NULL,
  `username` VARCHAR(60) NOT NULL,
  `email` VARCHAR(190) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(30) NULL DEFAULT NULL,
  `wallet_balance` DECIMAL(14,4) NOT NULL DEFAULT 0.0000,
  `spent_total` DECIMAL(14,4) NOT NULL DEFAULT 0.0000,
  `preferred_currency` VARCHAR(10) NULL DEFAULT NULL,
  `api_key` VARCHAR(64) NOT NULL,
  `status` ENUM('active','blocked','pending') NOT NULL DEFAULT 'active',
  `timezone` VARCHAR(64) NOT NULL DEFAULT 'Asia/Kolkata',
  `last_login_at` DATETIME NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_users_username` (`username`),
  UNIQUE KEY `uniq_users_email` (`email`),
  UNIQUE KEY `uniq_users_api_key` (`api_key`),
  KEY `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Ensure admin roles and admins tables exist
CREATE TABLE IF NOT EXISTS `admin_roles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(80) NOT NULL,
  `slug` VARCHAR(60) NOT NULL,
  `description` VARCHAR(255) NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_admin_roles_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `email` VARCHAR(190) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `status` ENUM('active','disabled') NOT NULL DEFAULT 'active',
  `last_login_at` DATETIME NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_admins_email` (`email`),
  KEY `idx_admins_role` (`role_id`),
  CONSTRAINT `fk_admins_role` FOREIGN KEY (`role_id`) REFERENCES `admin_roles` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Ensure base settings exist
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_group`) VALUES
('site_name', 'CrownSMM', 'general'),
('registration_enabled', '1', 'general')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

SET FOREIGN_KEY_CHECKS = 1;
