-- ═══════════════════════════════════════════════════════
-- Vent Coach (مدرّب التهوية) — Forward Migration
-- Adds:
--   1. "vent_coach" feature key in the features registry
--   2. patient_cases table for storing anonymized clinical snapshots
-- Safe to run on existing databases. Fully reversible by
-- running migrations/rollback_vent_coach.sql manually.
-- ═══════════════════════════════════════════════════════

-- Register the new feature
INSERT IGNORE INTO `features` (`key`, `name`, `description`, `icon`, `sort_order`, `is_active`) VALUES
('vent_coach', 'Vent Coach', 'Real-time safety scoring and titration coaching from current ventilator settings and ABG', '🧠', 9, 1);

-- Grant to every existing plan so current users do not lose value on deployment.
-- Admins can later restrict this feature per plan from /admin/plans.
INSERT IGNORE INTO `plan_features` (`plan_id`, `feature_id`)
SELECT p.id, f.id FROM plans p CROSS JOIN features f WHERE f.`key` = 'vent_coach';

-- Anonymized patient cases. Owners can save, edit and delete their own cases.
-- Patient data is intentionally kept minimal (no PHI / no names) — only a free-text
-- "reference" field (e.g. "Bed 12 — 06:30") and clinical parameters.
CREATE TABLE IF NOT EXISTS `patient_cases` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`         INT UNSIGNED NOT NULL,
  `reference`       VARCHAR(80)  NULL  COMMENT 'Optional bedside reference, e.g. "Bed 12"',
  `scenario`        VARCHAR(50)  NOT NULL DEFAULT 'healthy',
  `pbw_kg`          DECIMAL(5,2) NULL,
  `vent_data_json`  TEXT NULL  COMMENT 'JSON-encoded ventilator settings',
  `abg_data_json`   TEXT NULL  COMMENT 'JSON-encoded latest ABG values',
  `result_json`     TEXT NULL  COMMENT 'JSON-encoded analyzer output (denormalized snapshot)',
  `safety_level`    ENUM('green','yellow','red') NULL,
  `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_user_created` (`user_id`, `created_at`),
  INDEX `idx_user_updated` (`user_id`, `updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
