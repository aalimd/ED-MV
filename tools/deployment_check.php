<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
$configPath = $root . '/config.php';
$migrationDir = $root . '/migrations';
$failures = 0;
$warnings = 0;

function checkOk(string $message): void {
    echo "[OK] {$message}\n";
}

function checkWarn(string $message): void {
    global $warnings;
    $warnings++;
    echo "[WARN] {$message}\n";
}

function checkFail(string $message): void {
    global $failures;
    $failures++;
    echo "[FAIL] {$message}\n";
}

function shellAvailable(): bool {
    if (!function_exists('exec')) {
        return false;
    }

    $disabled = array_map('trim', explode(',', (string)ini_get('disable_functions')));
    return !in_array('exec', $disabled, true);
}

function runCommand(string $command): ?string {
    if (!shellAvailable()) {
        return null;
    }

    $lines = [];
    $exitCode = 0;
    exec($command . ' 2>&1', $lines, $exitCode);

    if ($exitCode !== 0) {
        return null;
    }

    return trim(implode("\n", $lines));
}

function connectForCheck(): ?PDO {
    foreach (['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'] as $constant) {
        if (!defined($constant)) {
            checkFail("Missing {$constant} in config.php.");
            return null;
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
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        checkFail('Database connection failed: ' . $e->getMessage());
        return null;
    }
}

function migrationFiles(string $migrationDir): array {
    $files = glob($migrationDir . '/*.sql') ?: [];
    sort($files, SORT_STRING);

    $migrations = [];
    foreach ($files as $file) {
        $migrations[basename($file)] = hash_file('sha256', $file);
    }

    return $migrations;
}

echo "Deployment check\n";
echo "================\n";

if (version_compare(PHP_VERSION, '8.0.0', '<')) {
    checkFail('PHP 8.0 or newer is required. Current version: ' . PHP_VERSION);
} elseif (version_compare(PHP_VERSION, '8.2.0', '<')) {
    checkWarn('PHP is older than 8.2. Use a supported PHP version on hosting. Current version: ' . PHP_VERSION);
} else {
    checkOk('PHP version: ' . PHP_VERSION);
}

if (!is_file($configPath)) {
    checkFail('Missing config.php.');
} else {
    checkOk('config.php exists.');
    $perms = fileperms($configPath);
    if ($perms !== false && ($perms & 0002) !== 0) {
        checkFail('config.php is world-writable. Use safer permissions like 600 or 640.');
    } else {
        checkOk('config.php is not world-writable.');
    }
    require_once $configPath;
}

if (defined('APP_DEBUG') && APP_DEBUG === false) {
    checkOk('APP_DEBUG is false.');
} elseif (defined('APP_DEBUG')) {
    checkFail('APP_DEBUG is enabled. Turn it off on production hosting.');
}

if (defined('APP_URL')) {
    if (str_starts_with(APP_URL, 'https://') && !str_contains(APP_URL, 'localhost') && !str_contains(APP_URL, '127.0.0.1')) {
        checkOk('APP_URL uses HTTPS: ' . APP_URL);
    } else {
        checkFail('APP_URL should be the production HTTPS domain. Current value: ' . APP_URL);
    }
}

foreach (['tools/.htaccess', 'migrations/.htaccess', 'includes/.htaccess', 'app/.htaccess'] as $protectedFile) {
    if (is_file($root . '/' . $protectedFile)) {
        checkOk($protectedFile . ' exists.');
    } else {
        checkFail($protectedFile . ' is missing.');
    }
}

$gitHead = runCommand('git -C ' . escapeshellarg($root) . ' rev-parse --short HEAD');
if ($gitHead !== null && $gitHead !== '') {
    checkOk('Git commit on this server: ' . $gitHead);
    $branch = runCommand('git -C ' . escapeshellarg($root) . ' rev-parse --abbrev-ref HEAD');
    if ($branch === 'main') {
        checkOk('Git branch is main.');
    } elseif ($branch !== null && $branch !== '') {
        checkFail('Git branch should be main. Current branch: ' . $branch);
    }

    $originHead = runCommand('git -C ' . escapeshellarg($root) . ' rev-parse --short origin/main');
    if ($originHead !== null && $originHead !== '') {
        if ($originHead === $gitHead) {
            checkOk('Server checkout matches origin/main.');
        } else {
            checkFail("Server checkout {$gitHead} does not match fetched origin/main {$originHead}. Run git pull --ff-only origin main.");
        }
    }

    $dirty = runCommand('git -C ' . escapeshellarg($root) . ' status --porcelain --untracked-files=no');
    if ($dirty === '') {
        checkOk('Tracked files are clean.');
    } elseif ($dirty !== null) {
        checkWarn('Tracked files have local modifications. Review before deploying: ' . str_replace("\n", '; ', $dirty));
    }
} else {
    checkWarn('Git commit could not be detected. If this is manual upload hosting, compare files against the GitHub release/commit.');
}

$ignoredConfig = runCommand('git -C ' . escapeshellarg($root) . ' check-ignore -q config.php; echo $?');
if ($ignoredConfig === '0') {
    checkOk('config.php is ignored by Git.');
} elseif ($ignoredConfig !== null) {
    checkWarn('config.php does not appear to be ignored by Git.');
}

if (is_file($configPath)) {
    $pdo = connectForCheck();
    if ($pdo instanceof PDO) {
        checkOk('Database connection works.');

        $requiredTables = [
            'users',
            'plans',
            'subscriptions',
            'login_attempts',
            'password_resets',
            'activity_log',
            'app_settings',
            'schema_migrations',
        ];

        $requiredColumns = [
            'users' => ['id', 'name', 'email', 'password_hash', 'role', 'status', 'email_verified', 'last_login', 'last_ip', 'created_at', 'updated_at'],
            'plans' => ['id', 'name', 'slug', 'description', 'features', 'duration_days', 'price', 'currency', 'badge', 'is_featured', 'color', 'is_active', 'sort_order', 'created_at'],
            'subscriptions' => ['id', 'user_id', 'plan_id', 'status', 'starts_at', 'expires_at', 'activated_by', 'notes', 'created_at', 'updated_at'],
            'login_attempts' => ['id', 'ip', 'email', 'attempts', 'first_attempt', 'locked_until'],
            'password_resets' => ['id', 'email', 'token_hash', 'expires_at', 'used', 'created_at'],
            'activity_log' => ['id', 'user_id', 'action', 'details', 'ip', 'user_agent', 'created_at'],
            'app_settings' => ['setting_key', 'setting_value', 'updated_at'],
            'schema_migrations' => ['migration', 'checksum', 'applied_at'],
        ];

        $existingTables = [];
        try {
            foreach ($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_NUM) as $row) {
                $existingTables[] = $row[0];
            }

            foreach ($requiredTables as $table) {
                if (in_array($table, $existingTables, true)) {
                    checkOk("Database table exists: {$table}");
                } else {
                    checkFail("Database table missing: {$table}");
                }
            }

            foreach ($requiredColumns as $table => $columns) {
                if (!in_array($table, $existingTables, true)) {
                    continue;
                }

                $columnRows = $pdo->query('SHOW COLUMNS FROM `' . str_replace('`', '``', $table) . '`')->fetchAll();
                $existingColumns = array_map(static fn (array $row): string => $row['Field'], $columnRows);
                $missingColumns = array_values(array_diff($columns, $existingColumns));

                if ($missingColumns === []) {
                    checkOk("Database columns match required set: {$table}");
                } else {
                    checkFail("Database table {$table} is missing columns: " . implode(', ', $missingColumns));
                }
            }

            if (in_array('schema_migrations', $existingTables, true)) {
                $files = migrationFiles($migrationDir);
                $rows = $pdo->query('SELECT migration, checksum FROM schema_migrations ORDER BY migration')->fetchAll();
                $applied = [];
                foreach ($rows as $row) {
                    $applied[$row['migration']] = $row['checksum'];
                }

                foreach ($applied as $name => $checksum) {
                    if (isset($files[$name]) && !hash_equals($checksum, $files[$name])) {
                        checkFail("Applied migration checksum mismatch: {$name}");
                    }
                }

                $pending = array_diff_key($files, $applied);
                if ($pending === []) {
                    checkOk('No pending migrations.');
                } else {
                    checkFail('Pending migrations: ' . implode(', ', array_keys($pending)));
                }
            }

            $planCount = (int)$pdo->query('SELECT COUNT(*) FROM plans')->fetchColumn();
            $settingCount = (int)$pdo->query('SELECT COUNT(*) FROM app_settings')->fetchColumn();

            if ($planCount > 0) {
                checkOk("Plans available: {$planCount}");
            } else {
                checkFail('No plans found in database.');
            }

            if ($settingCount > 0) {
                checkOk("Settings available: {$settingCount}");
            } else {
                checkFail('No app settings found in database.');
            }
        } catch (PDOException $e) {
            checkFail('Database verification failed: ' . $e->getMessage());
        }
    }
}

echo "\nResult: {$failures} failure(s), {$warnings} warning(s).\n";
exit($failures > 0 ? 1 : 0);
