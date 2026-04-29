<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
$migrationDir = $root . '/migrations';
$configPath = $root . '/config.php';
$args = $_SERVER['argv'] ?? [];

function fail(string $message, int $code = 1): never {
    fwrite(STDERR, "ERROR: {$message}\n");
    exit($code);
}

function usage(): void {
    echo <<<TXT
Usage:
  php tools/migrate.php --status
  php tools/migrate.php --dry-run
  php tools/migrate.php --apply

Options:
  --status   Show applied and pending migrations.
  --dry-run  Show what would run without changing the database.
  --apply    Apply pending migrations.

TXT;
}

function connectToDatabase(): PDO {
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
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        fail('Database connection failed: ' . $e->getMessage());
    }
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

function migrationTableExists(PDO $pdo): bool {
    $stmt = $pdo->query("SHOW TABLES LIKE 'schema_migrations'");
    return $stmt->fetchColumn() !== false;
}

function loadMigrationFiles(string $migrationDir): array {
    if (!is_dir($migrationDir)) {
        fail("Missing migrations directory: {$migrationDir}");
    }

    $files = glob($migrationDir . '/*.sql') ?: [];
    sort($files, SORT_STRING);

    $migrations = [];
    foreach ($files as $file) {
        $name = basename($file);
        $migrations[$name] = [
            'name' => $name,
            'path' => $file,
            'checksum' => hash_file('sha256', $file),
        ];
    }

    return $migrations;
}

function loadAppliedMigrations(PDO $pdo): array {
    $rows = $pdo->query('SELECT migration, checksum, applied_at FROM schema_migrations ORDER BY migration')->fetchAll();
    $applied = [];
    foreach ($rows as $row) {
        $applied[$row['migration']] = $row;
    }
    return $applied;
}

function assertAppliedChecksums(array $migrations, array $applied): void {
    foreach ($applied as $name => $row) {
        if (!isset($migrations[$name])) {
            echo "WARNING: Applied migration is no longer present in Git: {$name}\n";
            continue;
        }
        if (!hash_equals($row['checksum'], $migrations[$name]['checksum'])) {
            fail("Migration checksum changed after it was applied: {$name}. Create a new migration instead of editing old migrations.");
        }
    }
}

function splitSqlStatements(string $sql): array {
    $sql = preg_replace('/^\xEF\xBB\xBF/', '', $sql) ?? $sql;
    $statements = [];
    $buffer = '';
    $quote = null;
    $length = strlen($sql);

    for ($i = 0; $i < $length; $i++) {
        $char = $sql[$i];
        $next = $i + 1 < $length ? $sql[$i + 1] : '';

        if ($quote !== null) {
            $buffer .= $char;

            if (($quote === "'" || $quote === '"') && $char === '\\' && $next !== '') {
                $buffer .= $next;
                $i++;
                continue;
            }

            if ($char === $quote) {
                if ($next === $quote) {
                    $buffer .= $next;
                    $i++;
                    continue;
                }
                $quote = null;
            }
            continue;
        }

        if ($char === "'" || $char === '"' || $char === '`') {
            $quote = $char;
            $buffer .= $char;
            continue;
        }

        if ($char === '-' && $next === '-' && ($i + 2 >= $length || preg_match('/\s/', $sql[$i + 2]) === 1)) {
            while ($i < $length && $sql[$i] !== "\n") {
                $i++;
            }
            $buffer .= "\n";
            continue;
        }

        if ($char === '#') {
            while ($i < $length && $sql[$i] !== "\n") {
                $i++;
            }
            $buffer .= "\n";
            continue;
        }

        if ($char === '/' && $next === '*') {
            $i += 2;
            while ($i < $length - 1 && !($sql[$i] === '*' && $sql[$i + 1] === '/')) {
                $i++;
            }
            $i++;
            continue;
        }

        if ($char === ';') {
            $statement = trim($buffer);
            if ($statement !== '') {
                $statements[] = $statement;
            }
            $buffer = '';
            continue;
        }

        $buffer .= $char;
    }

    $statement = trim($buffer);
    if ($statement !== '') {
        $statements[] = $statement;
    }

    return $statements;
}

function applyMigration(PDO $pdo, array $migration): void {
    $sql = file_get_contents($migration['path']);
    if ($sql === false) {
        fail('Could not read migration: ' . $migration['name']);
    }

    $statements = splitSqlStatements($sql);
    if ($statements === []) {
        fail('Migration contains no SQL statements: ' . $migration['name']);
    }

    echo "Applying {$migration['name']}...\n";
    foreach ($statements as $statement) {
        $pdo->exec($statement);
    }

    $stmt = $pdo->prepare('INSERT INTO schema_migrations (migration, checksum) VALUES (?, ?)');
    $stmt->execute([$migration['name'], $migration['checksum']]);
    echo "Applied {$migration['name']}\n";
}

function printStatus(array $migrations, array $applied): void {
    echo "Migration status:\n";
    foreach ($migrations as $name => $migration) {
        $state = isset($applied[$name]) ? 'applied ' . $applied[$name]['applied_at'] : 'pending';
        echo "  {$state}  {$name}\n";
    }

    $pending = array_diff_key($migrations, $applied);
    echo "\nPending migrations: " . count($pending) . "\n";
}

$modeCount = (int)in_array('--status', $args, true)
    + (int)in_array('--dry-run', $args, true)
    + (int)in_array('--apply', $args, true);

if (in_array('--help', $args, true) || in_array('-h', $args, true)) {
    usage();
    exit(0);
}

if ($modeCount !== 1) {
    usage();
    fail('Choose exactly one mode: --status, --dry-run, or --apply.');
}

if (!is_file($configPath)) {
    fail("Missing config.php. Copy config.example.php to config.php and set production database credentials.");
}

require_once $configPath;

$pdo = connectToDatabase();
$hasMigrationTable = migrationTableExists($pdo);
if (in_array('--apply', $args, true) && !$hasMigrationTable) {
    ensureMigrationTable($pdo);
    $hasMigrationTable = true;
}
$migrations = loadMigrationFiles($migrationDir);
$applied = $hasMigrationTable ? loadAppliedMigrations($pdo) : [];
assertAppliedChecksums($migrations, $applied);
$pending = array_diff_key($migrations, $applied);

if (in_array('--status', $args, true)) {
    if (!$hasMigrationTable) {
        echo "Migration table has not been created yet. Run --apply to initialize it.\n\n";
    }
    printStatus($migrations, $applied);
    exit(0);
}

if (in_array('--dry-run', $args, true)) {
    if (!$hasMigrationTable) {
        echo "Migration table has not been created yet. Dry run is treating all migrations as pending.\n\n";
    }
    if ($pending === []) {
        echo "No pending migrations.\n";
        exit(0);
    }

    echo "Pending migrations:\n";
    foreach ($pending as $name => $_migration) {
        echo "  {$name}\n";
    }
    exit(0);
}

if ($pending === []) {
    echo "No pending migrations.\n";
    exit(0);
}

foreach ($pending as $migration) {
    applyMigration($pdo, $migration);
}

echo "All pending migrations applied.\n";
