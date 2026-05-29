<?php
/**
 * ED VentGuide Pro — Configuration
 * ──────────────────────────────────
 * Copy this file to config.php on each environment and fill in real values.
 * Never commit config.php with production credentials. Put mail/API secrets in
 * EDMV_SECRETS_PATH or /home/<user>/private/edmv.secrets.ini, outside web root.
 */

function edmv_secrets_paths(): array {
    $paths = [];
    $override = getenv('EDMV_SECRETS_PATH') ?: '';
    if ($override !== '') {
        $paths[] = $override;
    }
    $home = getenv('HOME') ?: '';
    if ($home !== '') {
        $paths[] = $home . '/private/edmv.secrets.ini';
    }
    $user = get_current_user();
    if ($user !== '') {
        $paths[] = '/home/' . $user . '/private/edmv.secrets.ini';
    }
    return array_values(array_unique($paths));
}

function edmv_load_secrets(): array {
    foreach (edmv_secrets_paths() as $path) {
        if (!is_readable($path)) {
            continue;
        }
        $parsed = parse_ini_file($path, true, INI_SCANNER_RAW);
        if (is_array($parsed)) {
            return $parsed;
        }
    }
    return [];
}

function edmv_secret(array $secrets, string $section, string $key, string $default = ''): string {
    if (isset($secrets[$section][$key])) {
        return (string)$secrets[$section][$key];
    }
    if (isset($secrets[$key])) {
        return (string)$secrets[$key];
    }
    return $default;
}

$edmvSecrets = edmv_load_secrets();

// ── Database ──────────────────────────────────────────
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');
define('DB_CHARSET', 'utf8mb4');

// ── Application ───────────────────────────────────────
define('APP_NAME', 'ED VentGuide Pro');
define('APP_URL', 'https://your-domain.com');   // Change for production
define('APP_ROOT', __DIR__);
define('APP_DEBUG', false);     // Keep false outside a private local debugging session

// ── Mail / Password Reset ─────────────────────────────
define('MAIL_DRIVER', edmv_secret($edmvSecrets, 'mail', 'driver', 'sndr')); // 'smtp' or 'sndr'
define('SNDR_API_KEY', edmv_secret($edmvSecrets, 'sndr', 'api_key', ''));
define('SNDR_API_URL', edmv_secret($edmvSecrets, 'sndr', 'api_url', 'https://api.sndr.sh/v1/send'));

define('SMTP_HOST', edmv_secret($edmvSecrets, 'smtp', 'host', ''));
define('SMTP_PORT', (int)edmv_secret($edmvSecrets, 'smtp', 'port', '587'));
define('SMTP_USERNAME', edmv_secret($edmvSecrets, 'smtp', 'username', ''));
define('SMTP_PASSWORD', edmv_secret($edmvSecrets, 'smtp', 'password', ''));
define('SMTP_SECURE', edmv_secret($edmvSecrets, 'smtp', 'secure', 'tls'));
define('SMTP_TIMEOUT', (int)edmv_secret($edmvSecrets, 'smtp', 'timeout', '10'));
define('MAIL_FROM', edmv_secret($edmvSecrets, 'smtp', 'from', 'edu@aalimd.com'));
define('MAIL_FROM_NAME', edmv_secret($edmvSecrets, 'smtp', 'from_name', APP_NAME));


// ── Security ──────────────────────────────────────────
define('SESSION_NAME', 'ventguide_sid');
define('SESSION_LIFETIME', 7200);        // 2 hours in seconds
define('REMEMBER_ME_LIFETIME', 2592000); // 30 days in seconds
define('CSRF_TOKEN_NAME', '_csrf_token');
define('BCRYPT_COST', 12);               // Fallback cost if Argon2id is missing
define('REQUIRE_ADMIN_APPROVAL', true);

// ── Rate Limiting ─────────────────────────────────────
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_MINUTES', 15);
define('TRUST_PROXIES', false); // Set to true if running behind Cloudflare or a load balancer
define('TRUSTED_PROXY_IPS', []);

// ── Timezone ──────────────────────────────────────────
date_default_timezone_set('Asia/Riyadh');  // Adjust to your timezone

// ── Error Handling ────────────────────────────────────
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', APP_ROOT . '/logs/error.log');
}
