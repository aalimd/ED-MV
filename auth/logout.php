<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
init_session();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_validate()) {
    redirect(APP_URL . '/');
}
if (is_logged_in()) log_activity('logout', 'User logged out');
session_destroy_full();
redirect(APP_URL . '/auth/login');
