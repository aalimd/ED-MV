<?php
declare(strict_types=1);

/**
 * CSP nonce + shared HTTP security headers.
 */

function csp_nonce(): string {
    static $nonce = null;
    if ($nonce === null) {
        $nonce = base64_encode(random_bytes(16));
    }
    return $nonce;
}

function script_nonce_attr(): string {
    return ' nonce="' . htmlspecialchars(csp_nonce(), ENT_QUOTES | ENT_HTML5, 'UTF-8') . '"';
}

function style_nonce_attr(): string {
    return script_nonce_attr();
}

function request_is_https(): bool {
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }
    return (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}

function send_security_headers(): void {
    if (headers_sent()) {
        return;
    }

    $nonce = csp_nonce();

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    header('Cross-Origin-Opener-Policy: same-origin');
    header('Cross-Origin-Resource-Policy: same-origin');

    if (request_is_https()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }

    header(
        "Content-Security-Policy: default-src 'self'; "
        . "script-src 'self' 'nonce-{$nonce}'; "
        . "worker-src 'self'; "
        . "manifest-src 'self'; "
        . "connect-src 'self'; "
        . "style-src 'self' 'nonce-{$nonce}' https://fonts.googleapis.com; "
        . "font-src 'self' https://fonts.gstatic.com; "
        . "img-src 'self' data:; "
        . "frame-ancestors 'none';"
    );
}

function ui_script_tag(): string {
    $base = defined('APP_URL') ? rtrim((string)APP_URL, '/') : '';
    return '<script src="' . $base . '/assets/js/ui.js?v=1" defer></script>';
}
