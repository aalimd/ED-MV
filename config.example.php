<?php
/**
 * ED VentGuide Pro - Example Configuration
 *
 * Copy this file to config.php on each environment and fill in real values.
 * Never commit config.php with production credentials.
 */

// Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');
define('DB_CHARSET', 'utf8mb4');

// Application
define('APP_NAME', 'ED VentGuide Pro');
define('APP_URL', 'https://your-domain.com');
define('APP_ROOT', __DIR__);
define('APP_DEBUG', false);

// Security
define('SESSION_NAME', 'ventguide_sid');
define('SESSION_LIFETIME', 7200);
define('REMEMBER_ME_LIFETIME', 2592000);
define('CSRF_TOKEN_NAME', '_csrf_token');
define('BCRYPT_COST', 12);
define('REQUIRE_ADMIN_APPROVAL', true);

// Rate Limiting
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_MINUTES', 15);

// Timezone
date_default_timezone_set('Asia/Riyadh');

// Error Handling
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', APP_ROOT . '/logs/error.log');
}

