<?php
declare(strict_types=1);

/**
 * Shared PWA tags for installable mobile/desktop app support.
 */

function pwa_asset_url(string $path): string {
    $base = defined('APP_URL') ? rtrim((string)APP_URL, '/') : '';
    return $base . '/' . ltrim($path, '/');
}

function pwa_head_tags(string $description = 'Evidence-based emergency department ventilation reference.'): string {
    $appName = defined('APP_NAME') ? (string)APP_NAME : 'ED VentGuide Pro';
    $shortName = 'VentGuide';
    $themeColor = '#2563eb';

    $tags = [
        '<meta name="theme-color" content="' . $themeColor . '" id="themeColorMeta">',
        '<meta name="description" content="' . htmlspecialchars($description, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '">',
        '<meta name="application-name" content="' . htmlspecialchars($appName, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '">',
        '<meta name="mobile-web-app-capable" content="yes">',
        '<meta name="apple-mobile-web-app-capable" content="yes">',
        '<meta name="apple-mobile-web-app-status-bar-style" content="default">',
        '<meta name="apple-mobile-web-app-title" content="' . $shortName . '">',
        '<link rel="manifest" href="' . pwa_asset_url('/manifest.webmanifest') . '">',
        '<link rel="icon" type="image/svg+xml" href="' . pwa_asset_url('/assets/pwa/icon.svg') . '">',
        '<link rel="icon" type="image/png" sizes="192x192" href="' . pwa_asset_url('/assets/pwa/icon-192.png') . '">',
        '<link rel="apple-touch-icon" href="' . pwa_asset_url('/assets/pwa/apple-touch-icon.png') . '">',
    ];

    return implode("\n", $tags);
}

function pwa_script_tag(): string {
    return '<script src="' . pwa_asset_url('/assets/js/pwa.js') . '" defer></script>';
}
