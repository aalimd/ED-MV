<?php
declare(strict_types=1);

/**
 * Prune stale auth/rate-limit rows and old activity logs.
 * Run via Hostinger cron (CLI): php /path/to/tools/cleanup.php
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';

$db = getDB();
$summary = [];

$stmt = $db->prepare(
    'DELETE FROM login_attempts WHERE first_attempt < DATE_SUB(NOW(), INTERVAL 30 DAY)'
);
$stmt->execute();
$summary['login_attempts'] = $stmt->rowCount();

$stmt = $db->prepare(
    'DELETE FROM password_resets WHERE expires_at < NOW() OR used = 1'
);
$stmt->execute();
$summary['password_resets'] = $stmt->rowCount();

$stmt = $db->prepare(
    'DELETE FROM email_verifications WHERE expires_at < NOW() OR used = 1'
);
$stmt->execute();
$summary['email_verifications'] = $stmt->rowCount();

$stmt = $db->prepare(
    'DELETE FROM activity_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)'
);
$stmt->execute();
$summary['activity_log'] = $stmt->rowCount();

echo "Cleanup complete\n";
foreach ($summary as $table => $count) {
    echo "  {$table}: {$count} row(s) removed\n";
}
