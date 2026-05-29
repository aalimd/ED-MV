UPDATE `subscriptions` s
JOIN (
  SELECT candidates.`user_id`, MAX(candidates.`id`) AS keep_id
  FROM `subscriptions` candidates
  JOIN (
    SELECT `user_id`, MAX(COALESCE(`expires_at`, '9999-12-31 23:59:59')) AS max_expires_at
    FROM `subscriptions`
    WHERE `status` = 'active'
    GROUP BY `user_id`
    HAVING COUNT(*) > 1
  ) dup ON dup.`user_id` = candidates.`user_id`
       AND COALESCE(candidates.`expires_at`, '9999-12-31 23:59:59') = dup.max_expires_at
  WHERE candidates.`status` = 'active'
  GROUP BY candidates.`user_id`
) keepers ON keepers.`user_id` = s.`user_id`
SET s.`status` = 'cancelled'
WHERE s.`status` = 'active'
  AND s.`id` <> keepers.keep_id;

ALTER TABLE `subscriptions`
  ADD COLUMN `active_user_id` INT UNSIGNED
    GENERATED ALWAYS AS (CASE WHEN `status` = 'active' THEN `user_id` ELSE NULL END) STORED,
  ADD UNIQUE KEY `uniq_active_subscription_user` (`active_user_id`);

ALTER TABLE `login_attempts`
  ADD INDEX `idx_email_action` (`email`, `action`);

ALTER TABLE `users`
  DROP INDEX `idx_email`;
