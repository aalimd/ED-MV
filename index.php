<?php
/**
 * ED VentGuide Pro — Main Router
 * Routes to: app (if subscribed), subscribe page, or login
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

// Not logged in → login page
if (!is_logged_in()) {
    redirect(app_url('/auth/login'));
}

// Admin → admin dashboard or app
$user = session_user();
if ($user['role'] === 'admin') {
    // Admins go straight to the app
    redirect(app_url('/app/ventguide'));
}

// Check subscription
if (has_subscription()) {
    redirect(app_url('/app/ventguide'));
} else {
    redirect(app_url('/subscribe'));
}
