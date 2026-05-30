# Finance Track — Setup Guide

## Step 1: Database SQL Patches
Run these in phpMyAdmin → SQL tab (only if upgrading from v1):

```sql
-- Password reset table
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(150) NOT NULL,
  `token` VARCHAR(64) NOT NULL UNIQUE,
  `expires_at` DATETIME NOT NULL,
  `used` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_token` (`token`), INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Email notifications log
CREATE TABLE IF NOT EXISTS `email_notifications` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `type` ENUM('emi_reminder','card_reminder','budget_alert','password_reset','welcome') NOT NULL,
  `ref_id` INT UNSIGNED DEFAULT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `sent_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('sent','failed') DEFAULT 'sent',
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Opening balance category (if not exists)
INSERT IGNORE INTO `categories` (`user_id`,`name`,`icon`,`color_hex`,`type`,`is_system`,`sort_order`)
VALUES (NULL,'Opening Balance','🏦','#0F6E56','income',1,0);
```

## Step 2: Update config.php
```php
define('MAIL_FROM',   'noreply@yourdomain.com');  // Your email
define('CRON_SECRET', 'your-random-secret-here'); // Change this!
```

## Step 3: Set up Cron Jobs in Hostinger
Go to hPanel → Advanced → Cron Jobs → Add new:

### Daily email reminders (9:00 AM)
- **Command:** `php /home/u429732427/domains/app.finance-track.app/public_html/cron/send-reminders.php`
- **Schedule:** `0 9 * * *`

### Auto EMI & Card transactions (midnight)
- **Command:** `php /home/u429732427/domains/app.finance-track.app/public_html/cron/auto-transactions.php`  
- **Schedule:** `0 0 * * *`

### Test cron manually via browser (use your CRON_SECRET):
`https://app.finance-track.app/cron/send-reminders.php?key=your-secret`
`https://app.finance-track.app/cron/auto-transactions.php?key=your-secret`

## Step 4: Enable email on Hostinger
hPanel → Emails → Create email account → `noreply@yourdomain.com`
Then update MAIL_FROM in config.php with this address.

## Features Summary
- ✅ Password reset via email
- ✅ Welcome email on registration  
- ✅ EMI payment reminders (7, 3, 1 days before)
- ✅ Budget alert emails (at threshold %)
- ✅ Credit card utilization alerts (>30%)
- ✅ Auto EMI transaction on due date
- ✅ Auto card bill transaction on due date
- ✅ Card transactions tracked per billing cycle
- ✅ Card selector in Add Transaction modal
