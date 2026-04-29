-- ═══════════════════════════════════════════════════════
-- Feature Gating System — Migration
-- Adds feature registry + plan-to-feature mapping.
-- Safe to run on existing databases.
-- ═══════════════════════════════════════════════════════

-- Feature registry: defines all lockable features
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

-- Plan-to-feature many-to-many mapping
CREATE TABLE IF NOT EXISTS `plan_features` (
  `plan_id` INT UNSIGNED NOT NULL,
  `feature_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`plan_id`, `feature_id`),
  FOREIGN KEY (`plan_id`) REFERENCES `plans`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`feature_id`) REFERENCES `features`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed the 7 gatable features
INSERT IGNORE INTO `features` (`key`, `name`, `icon`, `sort_order`) VALUES
('scenarios',  'Ventilation Scenarios', '🏥', 1),
('abg_calc',   'ABG Calculator',       '🧪', 2),
('compare',    'Scenario Comparison',   '📊', 3),
('guide',      'Clinical Guidelines',   '📖', 4),
('tools',      'Clinical Tools',        '🔧', 5),
('pbw_calc',   'PBW Calculator',        '⚖️', 6),
('ehr_export', 'EHR Export',            '📋', 7);

-- Give ALL existing plans ALL features (preserves current behavior)
-- No user loses access on deployment.
INSERT IGNORE INTO `plan_features` (`plan_id`, `feature_id`)
SELECT p.id, f.id FROM plans p CROSS JOIN features f;
