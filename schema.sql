-- ============================================================
-- PaisaTrack — Complete Database Schema
-- MySQL 8.0+ | Hostinger Business Plan
-- ============================================================

SET NAMES utf8mb4;
SET time_zone = '+05:30';

-- ============================================================
-- TABLE 1: users
-- ============================================================
CREATE TABLE `users` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`          VARCHAR(100) NOT NULL,
  `email`         VARCHAR(150) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `phone`         VARCHAR(15) DEFAULT NULL,
  `age_group`     TINYINT UNSIGNED DEFAULT 1 COMMENT '1=16-25, 2=25-45, 3=45+',
  `currency`      VARCHAR(5) NOT NULL DEFAULT 'INR',
  `timezone`      VARCHAR(50) NOT NULL DEFAULT 'Asia/Kolkata',
  `pin_hash`      VARCHAR(255) DEFAULT NULL COMMENT 'bcrypt of 4-digit PIN for quick lock',
  `avatar_color`  VARCHAR(7) DEFAULT '#1D9E75',
  `last_login`    DATETIME DEFAULT NULL,
  `is_active`     TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE 2: sessions
-- ============================================================
CREATE TABLE `sessions` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT UNSIGNED NOT NULL,
  `token`      VARCHAR(64) NOT NULL UNIQUE,
  `expires_at` DATETIME NOT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_token` (`token`),
  INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE 3: categories (system + custom)
-- ============================================================
CREATE TABLE `categories` (
  `id`        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`   INT UNSIGNED DEFAULT NULL COMMENT 'NULL = system category',
  `name`      VARCHAR(60) NOT NULL,
  `icon`      VARCHAR(10) NOT NULL DEFAULT '📁',
  `color_hex` VARCHAR(7) NOT NULL DEFAULT '#888780',
  `type`      ENUM('expense','income','both') NOT NULL DEFAULT 'expense',
  `is_system` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` SMALLINT UNSIGNED DEFAULT 99,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_user_type` (`user_id`, `type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default system categories
INSERT INTO `categories` (`user_id`, `name`, `icon`, `color_hex`, `type`, `is_system`, `sort_order`) VALUES
(NULL, 'Opening Balance',     '🏦', '#0F6E56', 'income',  1, 0),
(NULL, 'Food & Dining',       '🍔', '#E85D24', 'expense', 1, 1),
(NULL, 'Rent & Housing',      '🏠', '#185FA5', 'expense', 1, 2),
(NULL, 'Travel & Petrol',     '🚗', '#1D9E75', 'expense', 1, 3),
(NULL, 'Electricity & Bills', '⚡', '#BA7517', 'expense', 1, 4),
(NULL, 'Shopping',            '🛒', '#D4537E', 'expense', 1, 5),
(NULL, 'Medical',             '💊', '#E24B4A', 'expense', 1, 6),
(NULL, 'Education',           '📚', '#534AB7', 'expense', 1, 7),
(NULL, 'Entertainment',       '🎬', '#993C1D', 'expense', 1, 8),
(NULL, 'OTT & Subscriptions', '📺', '#5F5E5A', 'expense', 1, 9),
(NULL, 'Mobile & Internet',   '📱', '#0F6E56', 'expense', 1, 10),
(NULL, 'Groceries',           '🥦', '#3B6D11', 'expense', 1, 11),
(NULL, 'Personal Care',       '💈', '#72243E', 'expense', 1, 12),
(NULL, 'Investments',         '📈', '#0C447C', 'expense', 1, 13),
(NULL, 'Salary',              '💰', '#085041', 'income',  1, 1),
(NULL, 'Business Income',     '🏢', '#633806', 'income',  1, 2),
(NULL, 'Freelance',           '💻', '#3C3489', 'income',  1, 3),
(NULL, 'Pocket Money',        '👛', '#712B13', 'income',  1, 4),
(NULL, 'Interest & Returns',  '🏦', '#27500A', 'income',  1, 5),
(NULL, 'Other Income',        '💸', '#444441', 'income',  1, 6);

-- ============================================================
-- TABLE 4: income_sources
-- ============================================================
CREATE TABLE `income_sources` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`      INT UNSIGNED NOT NULL,
  `name`         VARCHAR(100) NOT NULL,
  `type`         ENUM('salary','business','freelance','pocket_money','investment','other') NOT NULL DEFAULT 'salary',
  `amount`       DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `frequency`    ENUM('monthly','weekly','daily','one_time') NOT NULL DEFAULT 'monthly',
  `day_of_month` TINYINT UNSIGNED DEFAULT 1 COMMENT 'Credit day for monthly income',
  `is_active`    TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE 5: recurring_transactions
-- ============================================================
CREATE TABLE `recurring_transactions` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`      INT UNSIGNED NOT NULL,
  `category_id`  INT UNSIGNED NOT NULL,
  `name`         VARCHAR(100) NOT NULL,
  `amount`       DECIMAL(12,2) NOT NULL,
  `type`         ENUM('expense','income') NOT NULL DEFAULT 'expense',
  `frequency`    ENUM('monthly','weekly','daily','yearly') NOT NULL DEFAULT 'monthly',
  `day_of_month` TINYINT UNSIGNED DEFAULT 1,
  `start_date`   DATE NOT NULL,
  `end_date`     DATE DEFAULT NULL,
  `last_generated` DATE DEFAULT NULL,
  `is_active`    TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE 6: cards
-- ============================================================
CREATE TABLE `cards` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`         INT UNSIGNED NOT NULL,
  `card_name`       VARCHAR(60) NOT NULL COMMENT 'e.g. HDFC Regalia',
  `bank_name`       VARCHAR(60) NOT NULL,
  `card_type`       ENUM('credit','debit') NOT NULL DEFAULT 'credit',
  `last4`           CHAR(4) DEFAULT NULL,
  `credit_limit`    DECIMAL(12,2) DEFAULT NULL,
  `current_balance` DECIMAL(12,2) DEFAULT 0.00 COMMENT 'Outstanding bill amount',
  `billing_date`    TINYINT UNSIGNED DEFAULT 1 COMMENT 'Day of month statement generates',
  `due_date`        TINYINT UNSIGNED DEFAULT 15 COMMENT 'Payment due day',
  `color_hex`       VARCHAR(7) DEFAULT '#185FA5',
  `is_active`       TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE 7: emis
-- ============================================================
CREATE TABLE `emis` (
  `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`        INT UNSIGNED NOT NULL,
  `loan_name`      VARCHAR(100) NOT NULL,
  `lender`         VARCHAR(100) DEFAULT NULL,
  `total_amount`   DECIMAL(14,2) NOT NULL,
  `emi_amount`     DECIMAL(12,2) NOT NULL,
  `interest_rate`  DECIMAL(5,2) DEFAULT NULL COMMENT 'Annual rate %',
  `tenure_months`  SMALLINT UNSIGNED NOT NULL,
  `paid_months`    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `due_day`        TINYINT UNSIGNED NOT NULL DEFAULT 5,
  `start_date`     DATE NOT NULL,
  `category_id`    INT UNSIGNED DEFAULT NULL,
  `is_active`      TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE 8: transactions (CORE TABLE)
-- ============================================================
CREATE TABLE `transactions` (
  `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`        INT UNSIGNED NOT NULL,
  `category_id`    INT UNSIGNED NOT NULL,
  `card_id`        INT UNSIGNED DEFAULT NULL,
  `emi_id`         INT UNSIGNED DEFAULT NULL,
  `recurring_id`   INT UNSIGNED DEFAULT NULL,
  `amount`         DECIMAL(12,2) NOT NULL,
  `type`           ENUM('expense','income','transfer') NOT NULL,
  `payment_method` ENUM('cash','upi','card','netbanking','cheque','other') NOT NULL DEFAULT 'upi',
  `note`           VARCHAR(255) DEFAULT NULL,
  `txn_date`       DATE NOT NULL,
  `is_recurring`   TINYINT(1) NOT NULL DEFAULT 0,
  `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`),
  FOREIGN KEY (`card_id`) REFERENCES `cards`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`emi_id`) REFERENCES `emis`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`recurring_id`) REFERENCES `recurring_transactions`(`id`) ON DELETE SET NULL,
  INDEX `idx_user_date` (`user_id`, `txn_date`),
  INDEX `idx_user_type` (`user_id`, `type`),
  INDEX `idx_user_month` (`user_id`, `txn_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE 9: budgets
-- ============================================================
CREATE TABLE `budgets` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`      INT UNSIGNED NOT NULL,
  `category_id`  INT UNSIGNED NOT NULL,
  `month`        TINYINT UNSIGNED NOT NULL COMMENT '1-12',
  `year`         SMALLINT UNSIGNED NOT NULL,
  `amount`       DECIMAL(12,2) NOT NULL,
  `alert_at_pct` TINYINT UNSIGNED NOT NULL DEFAULT 80 COMMENT 'Alert when % reached',
  `is_active`    TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_user_cat_period` (`user_id`, `category_id`, `month`, `year`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE 10: savings_goals
-- ============================================================
CREATE TABLE `savings_goals` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`      INT UNSIGNED NOT NULL,
  `name`         VARCHAR(100) NOT NULL,
  `target_amount` DECIMAL(12,2) NOT NULL,
  `saved_amount`  DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `target_date`  DATE DEFAULT NULL,
  `icon`         VARCHAR(10) DEFAULT '🎯',
  `color_hex`    VARCHAR(7) DEFAULT '#1D9E75',
  `is_achieved`  TINYINT(1) NOT NULL DEFAULT 0,
  `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE 11: reminders
-- ============================================================
CREATE TABLE `reminders` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`       INT UNSIGNED NOT NULL,
  `title`         VARCHAR(150) NOT NULL,
  `reminder_type` ENUM('emi','credit_card','bill','custom') NOT NULL DEFAULT 'custom',
  `ref_id`        INT UNSIGNED DEFAULT NULL COMMENT 'FK to emis/cards etc.',
  `amount`        DECIMAL(12,2) DEFAULT NULL,
  `day_of_month`  TINYINT UNSIGNED DEFAULT NULL,
  `remind_days_before` TINYINT UNSIGNED DEFAULT 3,
  `is_email`      TINYINT(1) DEFAULT 0,
  `is_active`     TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE 12: user_settings
-- ============================================================
CREATE TABLE `user_settings` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`       INT UNSIGNED NOT NULL UNIQUE,
  `dark_mode`     TINYINT(1) DEFAULT 0,
  `large_text`    TINYINT(1) DEFAULT 0,
  `default_view`  VARCHAR(20) DEFAULT 'dashboard',
  `notif_budget`  TINYINT(1) DEFAULT 1,
  `notif_emi`     TINYINT(1) DEFAULT 1,
  `notif_card`    TINYINT(1) DEFAULT 1,
  `tax_regime`    ENUM('old','new') DEFAULT 'new',
  `first_day_week` TINYINT(1) DEFAULT 1 COMMENT '1=Monday, 0=Sunday',
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE 13: financial_health_log
-- ============================================================
CREATE TABLE `financial_health_log` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`      INT UNSIGNED NOT NULL,
  `month`        TINYINT UNSIGNED NOT NULL,
  `year`         SMALLINT UNSIGNED NOT NULL,
  `score`        TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `total_income` DECIMAL(12,2) DEFAULT 0,
  `total_expense` DECIMAL(12,2) DEFAULT 0,
  `total_savings` DECIMAL(12,2) DEFAULT 0,
  `savings_rate`  DECIMAL(5,2) DEFAULT 0,
  `calculated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_user_period` (`user_id`, `month`, `year`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
