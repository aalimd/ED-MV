<?php
/**
 * ED VentGuide Pro — Configuration
 * ──────────────────────────────────
 * Copy this file to config.php on each environment and fill in real values.
 * Never commit config.php with production credentials.
 */

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

// ── Mail / Password Reset SMTP ────────────────────────
define('SMTP_HOST', 'smtp.example.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'smtp_username');
define('SMTP_PASSWORD', 'smtp_password');
define('SMTP_SECURE', 'tls');    // tls or ssl
define('SMTP_TIMEOUT', 10);      // seconds
define('MAIL_FROM', 'noreply@example.com');
define('MAIL_FROM_NAME', APP_NAME);

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
