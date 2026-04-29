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
        '<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">',
        '<meta name="apple-mobile-web-app-title" content="' . $shortName . '">',
        '<link rel="apple-touch-startup-image" href="' . pwa_asset_url('/assets/pwa/icon-512.png') . '">',
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

/**
 * Inline synchronous zoom-lock script for the <head>.
 * Must run before iOS registers touch handlers — defer/async won't work.
 */
function pwa_zoom_lock_script(): string {
    return '<script>(function(){
var _lastTouch=0;
document.addEventListener("gesturestart",function(e){e.preventDefault();},{passive:false});
document.addEventListener("gesturechange",function(e){e.preventDefault();},{passive:false});
document.addEventListener("touchmove",function(e){if(e.touches&&e.touches.length>1){e.preventDefault();return;}var el=e.target;while(el&&el!==document.body){if(el.id==="pwa-ios-sheet"||el.classList.contains("auth-wrapper")){return;}el=el.parentElement;}e.preventDefault();},{passive:false});
document.addEventListener("touchend",function(e){var now=Date.now();if(now-_lastTouch<=300){e.preventDefault();}_lastTouch=now;},{passive:false});
}());</script>';
}
