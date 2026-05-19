<?php
declare(strict_types=1);

/**
 * Shared PWA tags for installable mobile/desktop app support.
 */

require_once __DIR__ . '/security_headers.php';

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
    return '<script src="' . pwa_asset_url('/assets/js/pwa.js?v=5') . '" defer></script>';
}

/**
 * Inline synchronous zoom-lock script for the <head>.
 * Must run before iOS registers touch handlers — defer/async won't work.
 */
function pwa_zoom_lock_script(): string {
    return '<script' . script_nonce_attr() . '>(function(){
/* ── Zoom prevention (must run synchronously before iOS claims gestures) ── */
var _lastTouch=0;
document.addEventListener("gesturestart",function(e){e.preventDefault();},{passive:false});
document.addEventListener("gesturechange",function(e){e.preventDefault();},{passive:false});
document.addEventListener("touchmove",function(e){
  /* Always kill multi-touch (pinch) */
  if(e.touches&&e.touches.length>1){e.preventDefault();return;}
  /* Smart scroll: allow single-finger swipe ONLY inside elements that actually overflow and have auto/scroll */
  var el=e.target;
  while(el&&el!==document.documentElement){
    if(el.id==="pwa-ios-sheet"){return;}
    if(el.scrollHeight>el.clientHeight){
      var oy=window.getComputedStyle(el).overflowY;
      if(oy==="auto"||oy==="scroll"){return;}
    }
    el=el.parentElement;
  }
  e.preventDefault();
},{passive:false});
document.addEventListener("touchend",function(e){
  var now=Date.now();
  if(now-_lastTouch<=300){e.preventDefault();}
  _lastTouch=now;
},{passive:false});
}());</script>';
}
