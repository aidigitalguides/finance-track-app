-- ============================================================
-- Finance Track — Google OAuth Schema Patch
-- Run this AFTER your existing schema.sql
-- ============================================================

-- Add Google OAuth columns to users table
ALTER TABLE `users`
  ADD COLUMN `google_id`   VARCHAR(128) DEFAULT NULL UNIQUE AFTER `is_active`,
  ADD COLUMN `avatar_url`  VARCHAR(500) DEFAULT NULL AFTER `google_id`;

-- Index for fast Google ID lookup
CREATE INDEX `idx_google_id` ON `users` (`google_id`);

-- Make password_hash nullable (Google users have no password)
ALTER TABLE `users` MODIFY `password_hash` VARCHAR(255) DEFAULT NULL;

-- Log table for OAuth events (optional but useful for debugging)
CREATE TABLE `oauth_log` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT UNSIGNED NOT NULL,
  `provider`   VARCHAR(20) NOT NULL DEFAULT 'google',
  `event`      ENUM('login','register','link') NOT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
