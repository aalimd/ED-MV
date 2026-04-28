<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
init_session();
if (is_logged_in()) log_activity('logout', 'User logged out');
session_destroy_full();
// Clear dark mode cookie? No — keep it.
header('Location: ' . APP_URL . '/auth/login.php');
exit;
