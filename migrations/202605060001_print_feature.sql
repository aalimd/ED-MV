-- ═══════════════════════════════════════════════════════
-- Print Feature Gating — Migration
-- Adds "Print Pocket Card" as a controllable feature.
-- Safe to run on existing databases.
-- ═══════════════════════════════════════════════════════

-- Add the print feature to the registry
INSERT IGNORE INTO `features` (`key`, `name`, `icon`, `sort_order`) VALUES
('print', 'Print Pocket Card', '🖨️', 8);

-- Grant print to ALL existing plans (no user loses access on deployment)
INSERT IGNORE INTO `plan_features` (`plan_id`, `feature_id`)
SELECT p.id, f.id FROM plans p CROSS JOIN features f WHERE f.`key` = 'print';
