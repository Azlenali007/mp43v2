-- Structural System Defaults (Zero Fake Data, Zero Fake Exchange Rates)

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
('manual', 'Manual Payment (UPI / Bank Transfer)', 'Pay via UPI, QR Code, or Bank Transfer and submit your UTR reference for verification', 0, 'live', '{"method_name":"Manual UPI / Bank Transfer","accepted_currency":"INR","upi_id":"","qr_code_path":"","bank_name":"","account_number":"","account_holder":"","ifsc_code":"","instructions":"Transfer the exact amount via UPI or IMPS/NEFT, then enter your 12-digit UTR number and upload your payment screenshot."}', 10.00, 100000.00)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_group`) VALUES
('site_name', 'CrownSMM', 'general'),
('site_tagline', 'Automated Social Media Marketing Panel', 'general'),
('support_email', 'support@example.com', 'general'),
('currency_code', 'INR', 'general'),
('currency_symbol', '₹', 'general'),
('default_currency_code', 'INR', 'currency'),
('exchange_api_provider', 'open_er_api', 'currency'),
('exchange_api_url', 'https://open.er-api.com/v6/latest/{BASE}', 'currency'),
('exchange_api_key', '', 'currency'),
('exchange_auto_enabled', '1', 'currency'),
('exchange_interval_hours', '12', 'currency'),
('timezone', 'Asia/Kolkata', 'general'),
('registration_enabled', '1', 'general'),
('maintenance_mode', '0', 'general'),
('default_margin_percent', '25', 'pricing'),
('min_deposit_amount', '10', 'payments'),
('max_deposit_amount', '100000', 'payments'),
('cron_token', '', 'system'),
('active_theme', 'classic', 'themes'),
('ai_provider', 'gemini', 'tools'),
('ai_model', 'gemini-2.5-flash', 'tools'),
('ai_endpoint_url', '', 'tools'),
('ai_api_key', '', 'tools'),
('tools_rate_limit_per_hour', '30', 'tools')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

INSERT INTO `themes` (`slug`, `name`, `description`, `version`, `author`, `preview_accent`, `is_active`, `status`) VALUES
('classic', 'Classic Studio', 'Crisp white & obsidian workspace with royal purple highlights, glass KPI cards, and clean tabular typography.', '1.0.0', 'Panel Core', '#7138D8', 1, 'active'),
('aurora', 'Aurora Horizon', 'Atmospheric deep-navy & emerald-teal studio layout with elevated split cards and crisp luminous contrast.', '1.0.0', 'Panel Core', '#0D9488', 0, 'active')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `description` = VALUES(`description`), `preview_accent` = VALUES(`preview_accent`);

INSERT INTO `currencies` (`code`, `name`, `symbol`, `decimal_places`, `is_base`, `is_default`, `rate_mode`, `exchange_rate`, `rate_source`, `is_enabled`, `sort_order`, `last_rate_updated_at`) VALUES
('INR', 'Indian Rupee', '₹', 2, 1, 1, 'auto', 1.00000000, 'base', 1, 1, NOW()),
('USD', 'US Dollar', '$', 2, 0, 0, 'auto', NULL, 'unconfigured', 1, 2, NULL),
('EUR', 'Euro', '€', 2, 0, 0, 'auto', NULL, 'unconfigured', 1, 3, NULL),
('GBP', 'British Pound', '£', 2, 0, 0, 'auto', NULL, 'unconfigured', 1, 4, NULL),
('AED', 'UAE Dirham', 'د.إ', 2, 0, 0, 'auto', NULL, 'unconfigured', 1, 5, NULL),
('PKR', 'Pakistani Rupee', '₨', 2, 0, 0, 'auto', NULL, 'unconfigured', 1, 6, NULL),
('BDT', 'Bangladeshi Taka', '৳', 2, 0, 0, 'auto', NULL, 'unconfigured', 1, 7, NULL),
('SAR', 'Saudi Riyal', '﷼', 2, 0, 0, 'auto', NULL, 'unconfigured', 1, 8, NULL)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `symbol` = VALUES(`symbol`);

INSERT INTO `tools` (`slug`, `name`, `description`, `icon_key`, `category_label`, `is_enabled`, `sort_order`) VALUES
('instagram-caption', 'Instagram Caption & Hashtag Generator', 'Generate tailored Instagram post captions and targeted hashtag sets with tone, language, and category controls.', 'instagram', 'Instagram', 1, 1),
('instagram-bio', 'Instagram Bio Generator', 'Craft high-converting Instagram profile bios strictly respecting the 150-character Instagram limit.', 'instagram', 'Instagram', 1, 2),
('youtube-seo', 'YouTube SEO Generator', 'Create searchable YouTube video titles, structured SEO descriptions, and high-relevance tag packs.', 'youtube', 'YouTube', 1, 3)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `description` = VALUES(`description`);
