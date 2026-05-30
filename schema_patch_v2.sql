-- ============================================================
-- PaisaTrack Schema Patch v3 — Password Reset + Email Reminders
-- Run in phpMyAdmin SQL tab
-- ============================================================

-- Password reset tokens table
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `email`      VARCHAR(150) NOT NULL,
  `token`      VARCHAR(64) NOT NULL UNIQUE,
  `expires_at` DATETIME NOT NULL,
  `used`       TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_token` (`token`),
  INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Email notifications log
CREATE TABLE IF NOT EXISTS `email_notifications` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT UNSIGNED NOT NULL,
  `type`       ENUM('emi_reminder','card_reminder','budget_alert','password_reset','welcome') NOT NULL,
  `ref_id`     INT UNSIGNED DEFAULT NULL,
  `subject`    VARCHAR(255) NOT NULL,
  `sent_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `status`     ENUM('sent','failed') DEFAULT 'sent',
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
