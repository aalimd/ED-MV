-- ═══════════════════════════════════════════════════════════════
-- Vent Coach — MANUAL ROLLBACK SCRIPT
-- ───────────────────────────────────────────────────────────────
-- This script is NOT auto-applied by tools/migrate.php.
-- Run it ONLY if you need to fully remove the Vent Coach feature.
--
-- How to run it on Hostinger:
--   1. Backup your database first (cPanel → phpMyAdmin → Export).
--   2. Open phpMyAdmin → select your database → "SQL" tab.
--   3. Paste this file's contents and press "Go".
--   4. Optionally delete /app/coach.php and /includes/vent_coach.php
--      from File Manager to remove the unused UI/code as well.
-- ═══════════════════════════════════════════════════════════════

-- 1) Drop the saved cases table (also deletes any saved patient data)
DROP TABLE IF EXISTS `patient_cases`;

-- 2) Remove every plan→feature mapping that points at vent_coach
DELETE pf FROM `plan_features` pf
JOIN `features` f ON pf.feature_id = f.id
WHERE f.`key` = 'vent_coach';

-- 3) Remove the feature row itself
DELETE FROM `features` WHERE `key` = 'vent_coach';

-- 4) Allow the migrator to re-apply the migration cleanly later if desired
DELETE FROM `schema_migrations` WHERE `migration` = '202605190003_vent_coach.sql';
