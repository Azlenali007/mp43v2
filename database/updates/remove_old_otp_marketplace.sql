-- ============================================================================
-- SQL Migration: remove_old_otp_marketplace.sql
-- Description: Safely drops old OTP tables and settings, preserving all core SMM
--              Panel users, admins, wallets, orders, payments, services and settings.
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Drop old OTP tables if they exist
DROP TABLE IF EXISTS `otp_order_logs`;
DROP TABLE IF EXISTS `otp_transactions`;
DROP TABLE IF EXISTS `otp_orders`;
DROP TABLE IF EXISTS `otp_services`;
DROP TABLE IF EXISTS `otp_countries`;
DROP TABLE IF EXISTS `otp_providers`;
DROP TABLE IF EXISTS `otp_sync_logs`;
DROP TABLE IF EXISTS `otp_cron_logs`;
DROP TABLE IF EXISTS `otp_refunds`;
DROP TABLE IF EXISTS `otp_settings`;

-- Remove old OTP settings from settings table if any
DELETE FROM `settings` WHERE `setting_key` LIKE 'otp_%';

SET FOREIGN_KEY_CHECKS = 1;
