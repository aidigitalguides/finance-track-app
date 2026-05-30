-- ============================================================
-- PaisaTrack — Schema Patch v2
-- Run this in phpMyAdmin if upgrading from v1
-- New users get this automatically via category seeds
-- ============================================================

-- Add opening balance as a system category (if not exists)
INSERT IGNORE INTO `categories` (`user_id`, `name`, `icon`, `color_hex`, `type`, `is_system`, `sort_order`)
VALUES (NULL, 'Opening Balance', '🏦', '#0F6E56', 'income', 1, 0);

-- Add account_balance column to users table (stores current declared balance)
ALTER TABLE `users` 
  ADD COLUMN IF NOT EXISTS `opening_balance` DECIMAL(14,2) DEFAULT 0.00 COMMENT 'User-declared current account balance',
  ADD COLUMN IF NOT EXISTS `opening_balance_date` DATE DEFAULT NULL COMMENT 'Date of opening balance entry';
