<?php
/**
 * ED VentGuide Pro — Main Router
 * Routes to: app (if subscribed), subscribe page, or login
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

// Check maintenance mode
if (get_setting('maintenance_mode', '0') === '1' && !is_admin()) {
    http_response_code(503);
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0, viewport-fit=cover"><title>Maintenance</title>';
    echo '<link rel="stylesheet" href="' . APP_URL . '/assets/css/auth.css"></head><body>';
    echo '<div class="auth-wrapper"><div class="auth-card" style="text-align:center"><div style="font-size:3rem;margin-bottom:12px">🔧</div>';
    echo '<h1 class="auth-title">Under Maintenance</h1>';
    echo '<p class="auth-subtitle">We\'re updating VentGuide Pro. Please check back shortly.</p></div></div></body></html>';
    exit;
}

// Not logged in → login page
if (!is_logged_in()) {
    redirect(APP_URL . '/auth/login.php');
}

// Admin → admin dashboard or app
$user = session_user();
if ($user['role'] === 'admin') {
    // Admins go straight to the app
    redirect(APP_URL . '/app/ventguide.php');
}

// Check subscription
if (has_subscription()) {
    redirect(APP_URL . '/app/ventguide.php');
} else {
    redirect(APP_URL . '/subscribe.php');
}
