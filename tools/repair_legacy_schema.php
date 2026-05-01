<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
$configPath = $root . '/config.php';
$migrationPath = $root . '/migrations/202604290001_initial_schema.sql';

function fail(string $message, int $code = 1): never {
    fwrite(STDERR, "ERROR: {$message}\n");
    exit($code);
}

if (!is_file($configPath)) {
    fail('Missing config.php.');
}

if (!is_file($migrationPath)) {
    fail('Missing initial migration file.');
}

require_once $configPath;

foreach (['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'] as $constant) {
    if (!defined($constant)) {
        fail("Missing {$constant} in config.php.");
    }
}

$charset = defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4';
$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . $charset;
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' . $charset,
];

if (defined('PDO::MYSQL_ATTR_MULTI_STATEMENTS')) {
    $options[PDO::MYSQL_ATTR_MULTI_STATEMENTS] = false;
}

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    fail('Database connection failed: ' . $e->getMessage());
}

function tableExists(PDO $pdo, string $table): bool {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
    $stmt->execute([$table]);
    return (int)$stmt->fetchColumn() > 0;
}

function columnExists(PDO $pdo, string $table, string $column): bool {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?");
    $stmt->execute([$table, $column]);
    return (int)$stmt->fetchColumn() > 0;
}

function ensureMigrationTable(PDO $pdo): void {
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS `schema_migrations` (
            `migration` VARCHAR(190) NOT NULL PRIMARY KEY,
            `checksum` CHAR(64) NOT NULL,
            `applied_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
}

function markInitialMigrationApplied(PDO $pdo, string $migrationPath): void {
    $name = basename($migrationPath);
    $checksum = hash_file('sha256', $migrationPath);
    if ($checksum === false) {
        fail('Could not compute migration checksum.');
    }

    $stmt = $pdo->prepare(
        'INSERT INTO schema_migrations (migration, checksum) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE checksum = VALUES(checksum)'
    );
    $stmt->execute([$name, $checksum]);
}

function ensureDefaultPlanRows(PDO $pdo): void {
    $sql = "INSERT IGNORE INTO `plans`
        (`name`, `slug`, `description`, `features`, `duration_days`, `price`, `currency`, `badge`, `is_featured`, `color`, `is_active`, `sort_order`)
        VALUES
        ('Monthly', 'monthly', 'Full access for 30 days', 'Full ventilation reference|All clinical scenarios|PBW calculator|ABG correction tool', 30, 9.99, 'SAR', NULL, 0, '#2563eb', 1, 1),
        ('Yearly', 'yearly', 'Full access for 365 days', 'Full ventilation reference|All clinical scenarios|PBW calculator|ABG correction tool|Priority support', 365, 49.99, 'SAR', 'Best Value', 1, '#7c3aed', 1, 2),
        ('Lifetime', 'lifetime', 'Unlimited access forever', 'Full ventilation reference|All clinical scenarios|PBW calculator|ABG correction tool|Priority support|Lifetime updates', 36500, 99.99, 'SAR', 'Most Popular', 0, '#059669', 1, 3)";
    $pdo->exec($sql);
}

function ensureDefaultSettings(PDO $pdo): void {
    $sql = "INSERT IGNORE INTO `app_settings` (`setting_key`, `setting_value`) VALUES
        ('app_name', 'ED VentGuide Pro'),
        ('app_tagline', 'Evidence-Based Emergency Ventilation Reference'),
        ('theme_color', '#2563eb'),
        ('maintenance_mode', '0'),
        ('registration_open', '1'),
        ('require_email_verification', '1'),
        ('require_approval', '1'),
        ('session_timeout_minutes', '120'),
        ('max_login_attempts', '5'),
        ('lockout_minutes', '15')";
    $pdo->exec($sql);
}

echo "Repairing legacy schema...\n";

ensureMigrationTable($pdo);

$planAlterStatements = [
    'features' => "ALTER TABLE `plans` ADD COLUMN `features` TEXT NULL COMMENT 'Pipe-separated feature list' AFTER `description`",
    'badge' => "ALTER TABLE `plans` ADD COLUMN `badge` VARCHAR(50) NULL COMMENT 'e.g. Best Value, Most Popular' AFTER `currency`",
    'is_featured' => "ALTER TABLE `plans` ADD COLUMN `is_featured` TINYINT(1) NOT NULL DEFAULT 0 AFTER `badge`",
    'color' => "ALTER TABLE `plans` ADD COLUMN `color` VARCHAR(7) NOT NULL DEFAULT '#2563eb' AFTER `is_featured`",
];

if (!tableExists($pdo, 'plans')) {
    fail('The plans table does not exist. Run php tools/migrate.php --apply first on a clean database.');
}

foreach ($planAlterStatements as $column => $statement) {
    if (!columnExists($pdo, 'plans', $column)) {
        echo "Adding missing plans column: {$column}\n";
        $pdo->exec($statement);
    }
}

ensureDefaultPlanRows($pdo);
ensureDefaultSettings($pdo);
markInitialMigrationApplied($pdo, $migrationPath);

echo "Legacy schema repair completed.\n";
echo "Next step: php tools/deployment_check.php\n";
