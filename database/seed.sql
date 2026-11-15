-- Structural System Defaults (Zero Fake Data)

INSERT INTO `admin_roles` (`id`, `name`, `slug`, `description`) VALUES
(1, 'Super Admin', 'super_admin', 'Full unrestricted access to all system settings, financials, and administrators'),
(2, 'Operations Manager', 'manager', 'Manage orders, services, categories, providers, and manual payment approvals'),
(3, 'Support Agent', 'support', 'Handle support tickets, user inquiries, and view order statuses')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

INSERT INTO `admin_permissions` (`role_id`, `permission_key`) VALUES
(1, 'all'),
(2, 'manage_users'),
(2, 'manage_services'),
(2, 'manage_categories'),
(2, 'manage_providers'),
(2, 'manage_orders'),
(2, 'manage_payments'),
(2, 'manage_tickets'),
(2, 'view_reports'),
(3, 'manage_tickets'),
(3, 'view_orders'),
(3, 'view_users')
ON DUPLICATE KEY UPDATE `permission_key` = VALUES(`permission_key`);

INSERT INTO `payment_methods` (`code`, `name`, `description`, `is_enabled`, `mode`, `config_json`, `min_amount`, `max_amount`) VALUES
('razorpay', 'Razorpay', 'Automatic instant online payments via UPI, Cards, Netbanking & Wallets (INR)', 0, 'test', '{"key_id":"","key_secret":"","webhook_secret":""}', 10.00, 100000.00),
('manual', 'Manual Payment (UPI / Bank Transfer)', 'Pay via UPI, QR Code, or Bank Transfer and submit your UTR reference for verification', 0, 'live', '{"method_name":"Manual UPI / Bank Transfer","upi_id":"","qr_code_path":"","bank_name":"","account_number":"","account_holder":"","ifsc_code":"","instructions":"Transfer the exact amount via UPI or IMPS/NEFT, then enter your 12-digit UTR number and upload your payment screenshot."}', 10.00, 100000.00)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_group`) VALUES
('site_name', 'CrownSMM', 'general'),
('site_tagline', 'Automated Social Media Marketing Panel', 'general'),
('support_email', 'support@example.com', 'general'),
('currency_code', 'INR', 'general'),
('currency_symbol', '₹', 'general'),
('timezone', 'Asia/Kolkata', 'general'),
('registration_enabled', '1', 'general'),
('maintenance_mode', '0', 'general'),
('default_margin_percent', '25', 'pricing'),
('min_deposit_amount', '10', 'payments'),
('max_deposit_amount', '100000', 'payments'),
('cron_token', '', 'system')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);
