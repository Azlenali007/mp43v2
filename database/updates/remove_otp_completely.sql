-- SQL Migration: remove_otp_completely.sql
-- Description: Permanently drops all OTP Marketplace tables and settings, preserving all core SMM data and tables.

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

DELETE FROM `settings` WHERE `setting_key` LIKE 'otp_%';
