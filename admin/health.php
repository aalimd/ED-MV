<?php
/** Admin — Health & Mail Diagnostics */
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/../includes/mailer.php';

$root = dirname(__DIR__);
$db = getDB();
$user = session_user();

function health_bool_badge(bool $ok, string $okText = 'OK', string $badText = 'Issue'): string {
    $class = $ok ? 'active' : 'suspended';
    return '<span class="badge badge-' . $class . '">' . e($ok ? $okText : $badText) . '</span>';
}

function health_text_badge(string $text, string $class = 'subscriber'): string {
    return '<span class="badge badge-' . e($class) . '">' . e($text) . '</span>';
}

function health_const(string $name, string $fallback = ''): string {
    return defined($name) ? (string)constant($name) : $fallback;
}

function health_mask(?string $value, int $visible = 4): string {
    $value = (string)$value;
    if ($value === '') {
        return 'empty';
    }
    $length = strlen($value);
    if ($length <= ($visible * 2)) {
        return str_repeat('•', $length);
    }
    return substr($value, 0, $visible) . str_repeat('•', max(6, $length - ($visible * 2))) . substr($value, -$visible);
}

function health_run(string $command): ?string {
    if (!function_exists('exec')) {
        return null;
    }
    $disabled = array_map('trim', explode(',', (string)ini_get('disable_functions')));
    if (in_array('exec', $disabled, true)) {
        return null;
    }

    $lines = [];
    $exitCode = 0;
    exec($command . ' 2>&1', $lines, $exitCode);
    return $exitCode === 0 ? trim(implode("\n", $lines)) : null;
}

function health_migration_files(string $dir): array {
    $files = glob($dir . '/*.sql') ?: [];
    sort($files, SORT_STRING);
    return array_map('basename', $files);
}

function health_dns_txt(string $name): array {
    if (!function_exists('dns_get_record')) {
        return [];
    }

    $records = @dns_get_record($name, DNS_TXT);
    if (!is_array($records)) {
        return [];
    }

    $txt = [];
    foreach ($records as $record) {
        if (isset($record['txt'])) {
            $txt[] = (string)$record['txt'];
        } elseif (isset($record['entries']) && is_array($record['entries'])) {
            $txt[] = implode('', array_map('strval', $record['entries']));
        }
    }
    return $txt;
}

function health_has_txt(array $records, string $needle): bool {
    foreach ($records as $record) {
        if (stripos($record, $needle) !== false) {
            return true;
        }
    }
    return false;
}

function health_scrub_log_line(string $line): string {
    $patterns = [
        '/sndr_(?:live|test)_[A-Za-z0-9_\\-]+/' => 'sndr_<redacted>',
        '/(Authorization:\\s*Bearer\\s+)[A-Za-z0-9_\\.\\-]+/i' => '$1<redacted>',
        '/(password=)[^\\s&]+/i' => '$1<redacted>',
        '/(api[_-]?key["\\\']?\\s*[:=]\\s*["\\\']?)[^"\\\'\\s,}]+/i' => '$1<redacted>',
    ];

    return preg_replace(array_keys($patterns), array_values($patterns), $line) ?? $line;
}

function health_log_lines(string $path, int $limit = 8): array {
    if (!is_readable($path)) {
        return [];
    }

    $handle = fopen($path, 'rb');
    if ($handle === false) {
        return [];
    }

    $size = filesize($path);
    if ($size === false) {
        fclose($handle);
        return [];
    }

    $readBytes = min($size, 131072);
    if ($readBytes > 0) {
        fseek($handle, -$readBytes, SEEK_END);
    }

    $contents = stream_get_contents($handle);
    fclose($handle);
    if ($contents === false || $contents === '') {
        return [];
    }

    $lines = preg_split('/\R/', $contents) ?: [];
    $mailLines = array_values(array_filter(
        $lines,
        static fn (string $line): bool => stripos($line, 'MAILER') !== false || stripos($line, 'Mail send failed') !== false
    ));
    return array_map('health_scrub_log_line', array_slice($mailLines, -$limit));
}

function health_row(string $label, string $statusHtml, string $detail = ''): void {
    ?>
    <div class="health-kv">
      <div><strong><?= e($label) ?></strong></div>
      <div><?= $statusHtml ?></div>
      <div class="detail"><?= e($detail) ?></div>
    </div>
    <?php
}

$testResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'send_test_email') {
    if (!csrf_validate()) {
        flash('danger', 'Invalid request. Please try again.');
        redirect(app_url('/admin/health'));
    }

    $to = strtolower(trim((string)($_POST['test_email'] ?? '')));
    if (!valid_email($to)) {
        flash('danger', 'Enter a valid test email address.');
        redirect(app_url('/admin/health'));
    }

    $subject = APP_NAME . ' mail health test';
    $now = date('Y-m-d H:i:s T');
    $driver = mail_driver();
    $html = '<p>This is a mail health test from <strong>' . e(APP_NAME) . '</strong>.</p>'
        . '<p>Requested by admin: ' . e($user['email'] ?? 'unknown') . '</p>'
        . '<p>Configured driver: ' . e($driver) . '</p>'
        . '<p>Time: ' . e($now) . '</p>';
    $text = "Mail health test from " . APP_NAME . "\n"
        . "Requested by admin: " . ($user['email'] ?? 'unknown') . "\n"
        . "Configured driver: {$driver}\n"
        . "Time: {$now}\n";

    $testResult = send_app_email_detailed($to, $subject, $html, $text);
    log_activity(
        'admin_mail_health_test',
        'Sent test to ' . $to . ' via ' . $testResult['driver'] . ': ' . ($testResult['success'] ? 'success' : 'failed')
    );

    if ($testResult['success']) {
        $fallback = isset($testResult['details']['fallback_from']) ? ' after fallback from ' . $testResult['details']['fallback_from'] : '';
        flash('success', 'Test email accepted via ' . strtoupper($testResult['driver']) . $fallback . '.');
    } else {
        flash('danger', 'Test email failed: ' . $testResult['message']);
    }

    $_SESSION['health_mail_test_result'] = $testResult;
    redirect(app_url('/admin/health'));
}

$lastTestResult = $_SESSION['health_mail_test_result'] ?? null;
unset($_SESSION['health_mail_test_result']);

$mailDriver = mail_driver();
$sndrFrom = health_const('SNDR_FROM', health_const('MAIL_FROM'));
$smtpFrom = health_const('SMTP_FROM', health_const('MAIL_FROM'));
$mailFrom = health_const('MAIL_FROM');
$mailChecks = [
    ['Configured driver', health_text_badge(strtoupper($mailDriver)), 'Primary driver from [mail] driver.'],
    ['SNDR API key', health_bool_badge(health_const('SNDR_API_KEY') !== '', 'Loaded', 'Missing'), health_mask(health_const('SNDR_API_KEY'))],
    ['SNDR sender', health_bool_badge($sndrFrom === 'edu@aalimd.com', 'Correct', 'Check'), $sndrFrom],
    ['SMTP configured', health_bool_badge(smtp_mailer_enabled(), 'Ready', 'Missing'), health_const('SMTP_HOST') . ' as ' . $smtpFrom],
    ['Active MAIL_FROM', health_bool_badge($mailFrom === ($mailDriver === 'sndr' ? $sndrFrom : $smtpFrom), 'Aligned', 'Mismatch'), $mailFrom],
    ['cURL extension', health_bool_badge(function_exists('curl_init'), 'Loaded', 'Missing'), 'Required for SNDR API.'],
    ['Composer autoload', health_bool_badge(is_file($root . '/vendor/autoload.php'), 'Found', 'Missing'), 'Required for SMTP fallback through PHPMailer.'],
];

$aalimdTxt = health_dns_txt('aalimd.com');
$aalimdDmarc = health_dns_txt('_dmarc.aalimd.com');
$aalimdDkim = health_dns_txt('sndr._domainkey.aalimd.com');
$aamdTxt = health_dns_txt('aamd.sa');
$aamdDmarc = health_dns_txt('_dmarc.aamd.sa');

$dnsChecks = [
    ['aalimd.com SPF includes SNDR', health_bool_badge(health_has_txt($aalimdTxt, 'include:_spf.sndr.sh'), 'Present', 'Missing'), implode(' | ', $aalimdTxt)],
    ['aalimd.com SNDR DKIM', health_bool_badge($aalimdDkim !== [], 'Present', 'Missing'), 'sndr._domainkey.aalimd.com'],
    ['aalimd.com DMARC', health_bool_badge(health_has_txt($aalimdDmarc, 'v=DMARC1'), 'Present', 'Missing'), implode(' | ', $aalimdDmarc)],
    ['aamd.sa SPF includes Zoho', health_bool_badge(health_has_txt($aamdTxt, 'include:zoho.com'), 'Present', 'Missing'), implode(' | ', $aamdTxt)],
    ['aamd.sa DMARC', health_bool_badge(health_has_txt($aamdDmarc, 'v=DMARC1'), 'Present', 'Missing'), implode(' | ', $aamdDmarc)],
];

$gitHead = health_run('git -C ' . escapeshellarg($root) . ' rev-parse --short HEAD');
$originHead = health_run('git -C ' . escapeshellarg($root) . ' rev-parse --short origin/main');
$gitDirty = health_run('git -C ' . escapeshellarg($root) . ' status --porcelain --untracked-files=no');
$pendingMigrations = [];
try {
    $files = health_migration_files($root . '/migrations');
    $rows = $db->query('SELECT migration FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
    $applied = array_flip(array_map('strval', $rows));
    foreach ($files as $file) {
        if (!isset($applied[$file])) {
            $pendingMigrations[] = $file;
        }
    }
} catch (Throwable $e) {
    $pendingMigrations[] = 'Could not read schema_migrations: ' . $e->getMessage();
}

$deploymentChecks = [
    ['PHP version', health_bool_badge(version_compare(PHP_VERSION, '8.0.0', '>='), 'OK', 'Old'), PHP_VERSION],
    ['APP_DEBUG', health_bool_badge(defined('APP_DEBUG') && APP_DEBUG === false, 'Off', 'On'), 'Must be false in production.'],
    ['APP_URL HTTPS', health_bool_badge(str_starts_with((string)APP_URL, 'https://'), 'HTTPS', 'Check'), (string)APP_URL],
    ['Git commit', health_bool_badge($gitHead !== null && $gitHead !== '', 'Detected', 'Unknown'), $gitHead ?: 'Git unavailable'],
    ['Origin/main match', health_bool_badge($gitHead !== null && $originHead !== null && $gitHead === $originHead, 'Current', 'Pull needed'), 'local=' . ($gitHead ?: 'unknown') . ' origin=' . ($originHead ?: 'unknown')],
    ['Tracked files', health_bool_badge($gitDirty === '', 'Clean', 'Modified'), $gitDirty === '' ? 'No tracked local edits.' : (string)$gitDirty],
    ['Pending migrations', health_bool_badge($pendingMigrations === [], 'None', 'Pending'), $pendingMigrations === [] ? 'All migrations applied.' : implode(', ', $pendingMigrations)],
    ['Public secrets file', health_bool_badge(!is_file($root . '/config.secrets.ini'), 'Absent', 'Exposed'), 'config.secrets.ini must not be in public_html.'],
];

$duplicateActiveUsers = 0;
$recentLockouts = 0;
$dbMessage = 'Connected.';
try {
    $duplicateActiveUsers = (int)$db->query("SELECT COUNT(*) FROM (SELECT user_id FROM subscriptions WHERE status='active' GROUP BY user_id HAVING COUNT(*) > 1) dupes")->fetchColumn();
    $recentLockouts = (int)$db->query("SELECT COUNT(*) FROM login_attempts WHERE locked_until IS NOT NULL AND locked_until > NOW()")->fetchColumn();
} catch (Throwable $e) {
    $dbMessage = $e->getMessage();
}

$dbChecks = [
    ['Database connection', health_bool_badge(true, 'Connected', 'Failed'), $dbMessage],
    ['One active subscription/user', health_bool_badge($duplicateActiveUsers === 0, 'Enforced', 'Duplicates'), $duplicateActiveUsers . ' duplicate active user(s).'],
    ['Active lockouts', health_text_badge((string)$recentLockouts, $recentLockouts > 0 ? 'pending' : 'subscriber'), 'Current brute-force lockout rows.'],
];

$mailLogLines = health_log_lines($root . '/logs/error.log');

admin_header('Health & Mail', '🩺', 'health');
?>

<style>
.health-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px;margin-bottom:20px}
.health-kv{display:grid;grid-template-columns:150px 120px minmax(0,1fr);gap:10px;align-items:center;padding:10px 14px;border-bottom:1px solid var(--border);font-size:.84rem}
.health-kv:last-child{border-bottom:none}
.health-kv .detail{font-size:.78rem;color:var(--text-2);word-break:break-word}
.health-pre{white-space:pre-wrap;font:12px/1.5 'Space Mono',monospace;background:var(--surface-2);border:1px solid var(--border);border-radius:10px;padding:12px;color:var(--text-2);overflow:auto}
@media(max-width:680px){.health-kv{grid-template-columns:1fr}.health-kv .detail{margin-top:-4px}}
</style>

<div class="stats-grid">
  <div class="stat-card"><div class="sc-label">Mail Driver</div><div class="sc-value" style="font-size:1.3rem"><?= e(strtoupper($mailDriver)) ?></div><div class="sc-change sc-up"><?= e($mailFrom) ?></div></div>
  <div class="stat-card"><div class="sc-label">SNDR Sender</div><div class="sc-value" style="font-size:1rem"><?= e($sndrFrom) ?></div><div class="sc-change <?= $sndrFrom === 'edu@aalimd.com' ? 'sc-up' : 'sc-down' ?>"><?= $sndrFrom === 'edu@aalimd.com' ? 'Verified domain expected' : 'Check sender domain' ?></div></div>
  <div class="stat-card"><div class="sc-label">SMTP Sender</div><div class="sc-value" style="font-size:1rem"><?= e($smtpFrom) ?></div><div class="sc-change sc-up">Fallback sender</div></div>
  <div class="stat-card"><div class="sc-label">Migrations</div><div class="sc-value"><?= count($pendingMigrations) ?></div><div class="sc-change <?= $pendingMigrations === [] ? 'sc-up' : 'sc-down' ?>"><?= $pendingMigrations === [] ? 'No pending work' : 'Needs apply' ?></div></div>
</div>

<div class="data-card">
  <div class="dc-header">
    <div class="dc-title">📧 Mail Tester</div>
  </div>
  <form method="POST" class="admin-form" style="max-width:760px;padding:18px"><?= csrf_field() ?>
    <input type="hidden" name="action" value="send_test_email">
    <div class="form-group">
      <label for="test_email">Send Test Email To</label>
      <input id="test_email" type="email" name="test_email" value="<?= e($user['email'] ?? '') ?>" required>
    </div>
    <button type="submit" class="btn btn-primary" style="max-width:220px">Send Test Email</button>
  </form>
  <?php if (is_array($lastTestResult)): ?>
    <div style="padding:0 18px 18px">
      <div class="flash flash-<?= $lastTestResult['success'] ? 'success' : 'danger' ?>">
        <?= e(strtoupper((string)$lastTestResult['driver'])) ?>: <?= e((string)$lastTestResult['message']) ?>
        <?php if (!empty($lastTestResult['details']['fallback_from'])): ?>
          <br>Fallback from <?= e((string)$lastTestResult['details']['fallback_from']) ?> after: <?= e((string)($lastTestResult['details']['primary_error'] ?? 'unknown error')) ?>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
</div>

<div class="health-grid">
  <div class="data-card">
    <div class="dc-header"><div class="dc-title">📨 Mail Configuration</div></div>
    <?php foreach ($mailChecks as [$label, $status, $detail]) health_row($label, $status, $detail); ?>
  </div>

  <div class="data-card">
    <div class="dc-header"><div class="dc-title">🌐 DNS Deliverability</div></div>
    <?php foreach ($dnsChecks as [$label, $status, $detail]) health_row($label, $status, $detail); ?>
  </div>
</div>

<div class="health-grid">
  <div class="data-card">
    <div class="dc-header"><div class="dc-title">🚀 Deployment</div></div>
    <?php foreach ($deploymentChecks as [$label, $status, $detail]) health_row($label, $status, $detail); ?>
  </div>

  <div class="data-card">
    <div class="dc-header"><div class="dc-title">🗄️ Database & Security</div></div>
    <?php foreach ($dbChecks as [$label, $status, $detail]) health_row($label, $status, $detail); ?>
  </div>
</div>

<div class="data-card">
  <div class="dc-header"><div class="dc-title">🧾 Recent Mail Errors</div></div>
  <div style="padding:16px">
    <?php if ($mailLogLines === []): ?>
      <div class="empty-state" style="padding:20px"><p>No recent mail errors found in logs/error.log.</p></div>
    <?php else: ?>
      <div class="health-pre"><?= e(implode("\n", $mailLogLines)) ?></div>
    <?php endif; ?>
  </div>
</div>

<?php admin_footer(); ?>
