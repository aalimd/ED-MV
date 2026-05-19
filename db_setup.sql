-- ═══════════════════════════════════════════════════════
-- ED VentGuide Pro — Database Schema (bootstrap only)
-- ─────────────────────────────────────────────────────
-- Source of truth for production: migrations/*.sql + tools/migrate.php
-- Use this file only for first-time local/phpMyAdmin bootstrap.
-- Do NOT re-import over a live database after launch.
-- ═══════════════════════════════════════════════════════

CREATE DATABASE IF NOT EXISTS `edmvpro` 
  CHARACTER SET utf8mb4 
  COLLATE utf8mb4_unicode_ci;

USE `edmvpro`;

-- ── Users ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `auth_version` INT UNSIGNED NOT NULL DEFAULT 1,
  `role` ENUM('user','subscriber','admin') NOT NULL DEFAULT 'user',
  `status` ENUM('pending','active','suspended','deleted') NOT NULL DEFAULT 'pending',
  `email_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `last_login` DATETIME NULL,
  `last_ip` VARCHAR(45) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_email` (`email`),
  INDEX `idx_status` (`status`),
  INDEX `idx_role` (`role`)
) ENGINE=InnoDB;

-- ── Subscription Plans ────────────────────────────────
CREATE TABLE IF NOT EXISTS `plans` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(50) NOT NULL UNIQUE,
  `description` TEXT NULL,
  `features` TEXT NULL COMMENT 'Pipe-separated feature list',
  `duration_days` INT UNSIGNED NOT NULL DEFAULT 30,
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `currency` VARCHAR(3) NOT NULL DEFAULT 'SAR',
  `badge` VARCHAR(50) NULL COMMENT 'e.g. Best Value, Most Popular',
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `color` VARCHAR(7) NOT NULL DEFAULT '#2563eb',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ── Default Plans ─────────────────────────────────────
INSERT IGNORE INTO `plans` (`name`, `slug`, `description`, `features`, `duration_days`, `price`, `currency`, `badge`, `is_featured`, `color`, `is_active`, `sort_order`) VALUES
('Monthly', 'monthly', 'Full access for 30 days', 'Full ventilation reference|All clinical scenarios|PBW calculator|ABG correction tool', 30, 9.99, 'SAR', NULL, 0, '#2563eb', 1, 1),
('Yearly', 'yearly', 'Full access for 365 days', 'Full ventilation reference|All clinical scenarios|PBW calculator|ABG correction tool|Priority support', 365, 49.99, 'SAR', 'Best Value', 1, '#7c3aed', 1, 2),
('Lifetime', 'lifetime', 'Unlimited access forever', 'Full ventilation reference|All clinical scenarios|PBW calculator|ABG correction tool|Priority support|Lifetime updates', 36500, 99.99, 'SAR', 'Most Popular', 0, '#059669', 1, 3);

-- ── Subscriptions ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS `subscriptions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `plan_id` INT UNSIGNED NOT NULL,
  `status` ENUM('pending','active','expired','cancelled') NOT NULL DEFAULT 'pending',
  `starts_at` DATETIME NULL,
  `expires_at` DATETIME NULL,
  `activated_by` INT UNSIGNED NULL COMMENT 'Admin who activated',
  `notes` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`plan_id`) REFERENCES `plans`(`id`),
  FOREIGN KEY (`activated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_user_status` (`user_id`, `status`),
  INDEX `idx_expires` (`expires_at`)
) ENGINE=InnoDB;

-- ── Login Attempts (Rate Limiting) ────────────────────
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `ip` VARCHAR(45) NOT NULL,
  `email` VARCHAR(255) NULL,
  `action` VARCHAR(50) NOT NULL DEFAULT 'login',
  `attempts` INT UNSIGNED NOT NULL DEFAULT 1,
  `first_attempt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `locked_until` DATETIME NULL,
  INDEX `idx_ip` (`ip`),
  INDEX `idx_ip_action` (`ip`, `action`),
  INDEX `idx_locked` (`locked_until`)
) ENGINE=InnoDB;

-- ── Feature Gating ────────────────────────────────────
CREATE TABLE IF NOT EXISTS `features` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `key` VARCHAR(50) NOT NULL UNIQUE,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  `icon` VARCHAR(10) NULL DEFAULT '🔒',
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `plan_features` (
  `plan_id` INT UNSIGNED NOT NULL,
  `feature_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`plan_id`, `feature_id`),
  FOREIGN KEY (`plan_id`) REFERENCES `plans`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`feature_id`) REFERENCES `features`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `features` (`key`, `name`, `icon`, `sort_order`) VALUES
('scenarios',  'Ventilation Scenarios', '🏥', 1),
('abg_calc',   'ABG Calculator',       '🧪', 2),
('compare',    'Scenario Comparison',   '📊', 3),
('guide',      'Clinical Guidelines',   '📖', 4),
('tools',      'Clinical Tools',        '🔧', 5),
('pbw_calc',   'PBW Calculator',        '⚖️', 6),
('ehr_export', 'EHR Export',            '📋', 7),
('print',      'Print Pocket Card',     '🖨️', 8);

INSERT IGNORE INTO `plan_features` (`plan_id`, `feature_id`)
SELECT p.id, f.id FROM plans p CROSS JOIN features f;

-- ── Schema Migrations Tracking ────────────────────────
CREATE TABLE IF NOT EXISTS `schema_migrations` (
  `migration` VARCHAR(255) NOT NULL PRIMARY KEY,
  `checksum` CHAR(64) NOT NULL,
  `applied_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Password Resets ───────────────────────────────────
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(255) NOT NULL,
  `token_hash` VARCHAR(255) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `used` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_email` (`email`),
  INDEX `idx_expires` (`expires_at`)
) ENGINE=InnoDB;

-- ── Email Verifications ──────────────────────────────
CREATE TABLE IF NOT EXISTS `email_verifications` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `token_hash` VARCHAR(255) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `used` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_email` (`email`),
  INDEX `idx_token` (`token_hash`),
  INDEX `idx_expires` (`expires_at`)
) ENGINE=InnoDB;

-- ── Activity Log ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS `activity_log` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NULL,
  `action` VARCHAR(100) NOT NULL,
  `details` TEXT NULL,
  `ip` VARCHAR(45) NULL,
  `user_agent` VARCHAR(500) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_action` (`action`),
  INDEX `idx_created` (`created_at`),
  INDEX `idx_user` (`user_id`)
) ENGINE=InnoDB;

-- ── App Settings ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS `app_settings` (
  `setting_key` VARCHAR(100) PRIMARY KEY,
  `setting_value` TEXT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ── Default Settings ──────────────────────────────────
INSERT IGNORE INTO `app_settings` (`setting_key`, `setting_value`) VALUES
('app_name', 'ED VentGuide Pro'),
('app_tagline', 'Evidence-Based Emergency Ventilation Reference'),
('theme_color', '#2563eb'),
('maintenance_mode', '0'),
('registration_open', '1'),
('require_email_verification', '1'),
('require_approval', '1'),
('session_timeout_minutes', '120'),
('max_login_attempts', '5'),
('lockout_minutes', '15');
