ALTER TABLE `login_attempts` ADD COLUMN `action` VARCHAR(50) NOT NULL DEFAULT 'login' AFTER `email`;
ALTER TABLE `login_attempts` ADD INDEX `idx_ip_action` (`ip`, `action`);
