# CrownSMM — Production PHP & MySQL SMM Panel

A complete, standalone Core PHP 8.2+ and MySQL 8+ Social Media Marketing (SMM) Panel built for standard cPanel / LAMP / LEMP hosting without Node.js or Composer.

## System Requirements
- **PHP**: 8.2 or higher
- **Database**: MySQL 8.0+ or MariaDB 10.6+
- **Required PHP Extensions**: `pdo_mysql`, `curl`, `openssl`, `mbstring`, `json`, `fileinfo` (or `gd`)
- **Web Server**: Apache with `mod_rewrite` (`.htaccess` included) or Nginx

## cPanel Installation Guide

1. **Upload Files**
   - Upload all project files and folders (`index.php`, `admin/`, `user/`, `api/`, `themes/`, `tools/`, `config/`, `includes/`, `classes/`, `install/`, `cron/`, `assets/`, `uploads/`, `database/`, `storage/`, `.htaccess`) directly into your domain's document root (`public_html/`).
   - Ensure `config/database.local.php`, `config/app.secret.php`, and `config/installed.lock` do **not** exist before running a fresh installation.

2. **Create MySQL Database**
   - Open **cPanel -> MySQL Databases**.
   - Create a new database and a new database user with a strong password.
   - Assign **ALL PRIVILEGES** to the user on that database.

3. **Set Folder Permissions**
   - Ensure the following directories are writable (`0755` or `0775`):
     - `config/`
     - `uploads/payments/`
     - `uploads/tickets/`
     - `storage/logs/`
     - `storage/backups/`

4. **Run the Web Installer**
   - Visit `https://yourdomain.com/install/` in your browser.
   - Complete the 4-step wizard:
     1. PHP Version, Extensions & Folder Permissions check.
     2. MySQL Host, Port, Database, Username & Password connection test.
     3. Site Name, Support Email, Base Currency (`INR` / `₹`), and Initial Super Admin account creation.
     4. Automated 28-table schema import, structural defaults seeding, AES-256 key generation, and automatic installer lock (`config/installed.lock`).
   - Once complete, the installer locks itself against re-execution. For extra hardening on production hosts, you may delete the `install/` directory afterward.

## Post-Install Configuration

### 1. Multi-Currency & Live Exchange Rates (`Admin -> Currencies`)
- Your panel runs on a canonical **Base Currency** (default `INR`) for accounting, wallets, provider costs, and reports.
- Click **Sync Live Exchange Rates Now** (uses `open.er-api.com` or your configured ExchangeRate-API key) or set manual overrides per currency before enabling foreign display currencies or importing multi-currency providers.
- Logged-in users can switch their preferred display currency (`USD`, `EUR`, `GBP`, `AED`, etc.) from the top header switcher or `Account Settings`.

### 2. Modular Frontend Themes (`Admin -> Themes`)
- Switch globally between **Classic Studio** (`themes/classic/`) and **Aurora Midnight** (`themes/aurora/`), or use **Preview Live** (Admin-authenticated only).
- The admin workspace (`admin/`) stays cleanly separated and consistent.

### 3. Creator Tools & AI Integration (`Admin -> Creator Tools`)
- Includes 3 toggleable tools under `tools/`: **Instagram Caption & Hashtag Generator**, **Instagram Bio Generator** (enforces <= 150 chars), and **YouTube SEO Generator**.
- Configure your Gemini or OpenAI-compatible API key in **Admin -> Creator Tools** and run **Test AI Connection**.

### 4. Payment Methods (`Admin -> Payment Methods`)
Both **Razorpay** and **Manual Payment (UPI / Bank Transfer)** are disabled by default until configured:
- **Razorpay**: Enter your `Key ID`, `Key Secret`, and `Webhook Secret`, select `Test` or `Live` mode, click **Test Connection**, and enable the toggle.
  - Configure the Webhook URL in your Razorpay Dashboard: `https://yourdomain.com/api/razorpay-webhook.php`
  - Subscribe to events: `payment.captured`, `order.paid`, `payment.failed`
- **Manual Payment**: Choose the accepted currency, enter your UPI ID / QR / bank details, and enable the toggle.

### 5. SMM Providers & Service Import (`Admin -> Providers` & `Admin -> Import Services`)
- Add your upstream SMM API v2 provider endpoint and API key (encrypted at rest using AES-256-GCM) and assign the provider's billing currency.
- **Import Services** validates the provider's currency and exchange rate, converts provider prices into canonical Base Currency first, applies percentage/fixed margins, and previews duplicates before import.

### 6. Cron Jobs (cPanel -> Cron Jobs)
Retrieve your private cron token from **Admin -> Settings** (`cron_token`) and schedule these jobs:

- **Sync Order Statuses (Every 2–5 Minutes)**
  ```bash
  php -q /home/USER/public_html/cron/update-orders.php
  ```
- **Sync Provider Service Prices & Availability (Every 6 Hours)**
  ```bash
  php -q /home/USER/public_html/cron/sync-services.php
  ```
- **Sync Live Currency Exchange Rates (Every 6–12 Hours)**
  ```bash
  php -q /home/USER/public_html/cron/update-exchange-rates.php
  ```
- **Process Automated Refunds for Cancelled/Partial Orders (Every 10 Minutes)**
  ```bash
  php -q /home/USER/public_html/cron/process-refunds.php
  ```

## Security Notes
- Public pages contain zero links to the admin login portal; administrators sign in directly via `/admin/login.php`.
- `.htaccess` rules block HTTP access to `config/`, `database/`, `storage/`, `classes/`, `includes/`, and disable PHP execution inside `uploads/`.
- All order charges, payment verifications, wallet debits/credits, and refunds are enforced server-side inside atomic MySQL transactions (`FOR UPDATE` row locking) with immutable multi-currency historical snapshots.
