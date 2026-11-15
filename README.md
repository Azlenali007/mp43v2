# CrownSMM — Production PHP & MySQL SMM Panel

A complete, standalone Core PHP 8.2+ and MySQL 8+ Social Media Marketing (SMM) Panel built for standard cPanel / LAMP / LEMP hosting without Node.js or Composer.

## System Requirements
- **PHP**: 8.2 or higher
- **Database**: MySQL 8.0+ or MariaDB 10.6+
- **Required PHP Extensions**: `pdo_mysql`, `curl`, `openssl`, `mbstring`, `json`, `fileinfo` (or `gd`)
- **Web Server**: Apache with `mod_rewrite` (`.htaccess` included) or Nginx

## cPanel Installation Guide

1. **Upload Files**
   - Upload all project files and folders (`index.php`, `admin/`, `user/`, `api/`, `config/`, `includes/`, `classes/`, `install/`, `cron/`, `assets/`, `uploads/`, `database/`, `storage/`, `.htaccess`) directly into your domain's document root (`public_html/`).
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
     3. Site Name, Support Email, Currency (`INR` / `₹`), and Initial Super Admin account creation.
     4. Automated 21-table schema import, structural defaults seeding, AES-256 key generation, and automatic installer lock (`config/installed.lock`).
   - Once complete, the installer locks itself against re-execution. For extra hardening on production hosts, you may delete the `install/` directory afterward.

## Post-Install Configuration

### 1. Payment Methods (`Admin -> Payment Methods`)
Both **Razorpay** and **Manual Payment (UPI / Bank Transfer)** are disabled by default until configured:
- **Razorpay**: Enter your `Key ID`, `Key Secret`, and `Webhook Secret`, select `Test` or `Live` mode, click **Test Connection**, and enable the toggle.
  - Configure the Webhook URL in your Razorpay Dashboard: `https://yourdomain.com/api/razorpay-webhook.php`
  - Subscribe to events: `payment.captured`, `order.paid`, `payment.failed`
- **Manual Payment**: Enter your UPI ID, upload your payment QR code, add bank account/IFSC details and instructions, and enable the toggle.

### 2. SMM Providers & Service Import (`Admin -> Providers` & `Admin -> Import Services`)
- Add your upstream SMM API v2 provider endpoint and API key (encrypted at rest using AES-256-GCM).
- Use **Test Connection** to verify live balance retrieval.
- Open **Import Services** to fetch provider catalogs, preview duplicates, apply percentage or fixed profit margins, and import into local categories.

### 3. Cron Jobs (cPanel -> Cron Jobs)
Retrieve your private cron token from **Admin -> Settings** (`cron_token`) and schedule these jobs:

- **Sync Order Statuses (Every 2–5 Minutes)**
  ```bash
  php -q /home/USER/public_html/cron/update-orders.php
  ```
  Or via URL:
  ```bash
  curl -s "https://yourdomain.com/cron/update-orders.php?token=YOUR_CRON_TOKEN" > /dev/null
  ```

- **Sync Provider Service Prices & Availability (Every 6 Hours)**
  ```bash
  php -q /home/USER/public_html/cron/sync-services.php
  ```

- **Process Automated Refunds for Cancelled/Partial Orders (Every 10 Minutes)**
  ```bash
  php -q /home/USER/public_html/cron/process-refunds.php
  ```

## Security Notes
- Public pages contain zero links to the admin login portal; administrators sign in directly via `/admin/login.php`.
- `.htaccess` rules block HTTP access to `config/`, `database/`, `storage/`, `classes/`, `includes/`, and disable PHP execution inside `uploads/`.
- All order charges, payment verifications, wallet debits/credits, and refunds are enforced server-side inside atomic MySQL transactions (`FOR UPDATE` row locking).
