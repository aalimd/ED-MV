<?php
/**
 * Feature Gating System
 * ─────────────────────
 * Generic — driven entirely by DB rows + HTML data-feature attributes.
 * No feature-specific logic lives here. Adding a new gatable feature
 * requires only a DB row + an HTML data-feature attribute.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/session.php';

/**
 * Get all feature keys granted to the current user's active subscription.
 * Admins receive all active features. Unauthenticated users receive none.
 *
 * @return string[] Array of feature key strings
 */
function get_user_features(): array {
    $user = session_user();
    if (!$user) return [];

    // Admins bypass — get everything
    if ($user['role'] === 'admin') {
        return getDB()
            ->query("SELECT `key` FROM features WHERE is_active = 1")
            ->fetchAll(PDO::FETCH_COLUMN);
    }

    $db = getDB();
    $stmt = $db->prepare(
        "SELECT DISTINCT f.`key`
         FROM features f
         JOIN plan_features pf ON f.id = pf.feature_id
         JOIN subscriptions s ON s.plan_id = pf.plan_id
         WHERE s.user_id = ?
           AND s.status = 'active'
           AND (s.expires_at IS NULL OR s.expires_at > NOW())
           AND f.is_active = 1"
    );
    $stmt->execute([$user['id']]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Get features for the current request.
 * Authorization state must reflect admin plan/subscription edits immediately,
 * so this intentionally avoids a session TTL cache.
 *
 * @return string[] Array of feature key strings
 */
function get_user_features_cached(): array {
    unset($_SESSION['_feat_keys'], $_SESSION['_feat_ts'], $_SESSION['_feat_user_id']);
    return get_user_features();
}

/**
 * Check if current user has access to a specific feature key.
 * Supports dot-notation parent walk: has_feature('tools.pf_ratio')
 * will return true if user has 'tools' (parent grants children).
 *
 * @param string $key Feature key to check (e.g. 'abg_calc', 'tools.pf_ratio')
 * @return bool True if the user has access
 */
function has_feature(string $key): bool {
    static $set = null;
    if ($key === '__reset__') {
        $set = null;
        return false;
    }
    if ($set === null) {
        $set = array_flip(get_user_features_cached());
    }

    // Direct match
    if (isset($set[$key])) return true;

    // Parent walk: 'tools.pf_ratio' → check 'tools'
    $dot = strrpos($key, '.');
    while ($dot !== false) {
        $key = substr($key, 0, $dot);
        if (isset($set[$key])) return true;
        $dot = strrpos($key, '.');
    }

    return false;
}

/**
 * Render a <script> tag injecting the user's feature list into the frontend.
 * Called from ventguide.php before echoing HTML.
 *
 * @return string HTML script tag
 */
function render_feature_script(): string {
    $keys = get_user_features_cached();
    $json = json_encode($keys, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
    return '<script>window.__FEATURES=' . $json . ';</script>';
}

/**
 * Force session feature cache refresh.
 * Call from admin when activating/changing subscriptions.
 */
function invalidate_feature_cache(): void {
    unset($_SESSION['_feat_keys'], $_SESSION['_feat_ts'], $_SESSION['_feat_user_id']);
    has_feature('__reset__');
}
