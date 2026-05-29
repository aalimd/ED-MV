<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function subscription_duration_days(array $plan): int {
    $days = (int)($plan['duration_days'] ?? 0);
    return $days > 0 ? $days : 30;
}

function subscription_expires_at(int $durationDays, int $rolloverSeconds = 0): string {
    return date('Y-m-d H:i:s', time() + ($durationDays * 86400) + max(0, $rolloverSeconds));
}

function subscription_lock_user(PDO $db, int $userId): ?array {
    $stmt = $db->prepare('SELECT id, role, status FROM users WHERE id = ? LIMIT 1 FOR UPDATE');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    return is_array($row) ? $row : null;
}

function subscription_active_plan(PDO $db, int $planId, bool $forUpdate = false): ?array {
    $sql = 'SELECT id, name, duration_days FROM plans WHERE id = ? AND is_active = 1 LIMIT 1';
    if ($forUpdate) {
        $sql .= ' FOR UPDATE';
    }

    $stmt = $db->prepare($sql);
    $stmt->execute([$planId]);
    $row = $stmt->fetch();
    return is_array($row) ? $row : null;
}

function subscription_user_id(PDO $db, int $subId): ?int {
    $stmt = $db->prepare('SELECT user_id FROM subscriptions WHERE id = ? LIMIT 1');
    $stmt->execute([$subId]);
    $userId = $stmt->fetchColumn();
    return $userId === false ? null : (int)$userId;
}

function subscription_row_with_plan(PDO $db, int $subId): ?array {
    $stmt = $db->prepare(
        'SELECT s.id, s.user_id, s.plan_id, s.status, s.starts_at, s.expires_at,
                p.name AS plan_name, p.duration_days, p.is_active
         FROM subscriptions s
         JOIN plans p ON p.id = s.plan_id
         WHERE s.id = ?
         LIMIT 1
         FOR UPDATE'
    );
    $stmt->execute([$subId]);
    $row = $stmt->fetch();
    return is_array($row) ? $row : null;
}

function subscription_lock_user_subscriptions(PDO $db, int $userId): array {
    $stmt = $db->prepare(
        'SELECT id, status, expires_at
         FROM subscriptions
         WHERE user_id = ?
         ORDER BY id
         FOR UPDATE'
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function subscription_rollover_seconds(array $subscriptions, ?int $now = null): int {
    $now ??= time();
    $rolloverSeconds = 0;

    foreach ($subscriptions as $subscription) {
        if (($subscription['status'] ?? '') !== 'active') {
            continue;
        }

        $expiresAt = $subscription['expires_at'] ?? null;
        if (!$expiresAt) {
            continue;
        }

        $expires = strtotime((string)$expiresAt);
        if ($expires !== false && $expires > $now) {
            $rolloverSeconds += ($expires - $now);
        }
    }

    return $rolloverSeconds;
}

function subscription_cancel_user_statuses(PDO $db, int $userId, array $statuses, ?int $exceptId = null): int {
    $allowed = ['pending', 'active', 'expired', 'cancelled'];
    $statuses = array_values(array_unique(array_intersect($statuses, $allowed)));
    if ($statuses === []) {
        return 0;
    }

    $placeholders = implode(', ', array_fill(0, count($statuses), '?'));
    $sql = "UPDATE subscriptions SET status = 'cancelled' WHERE user_id = ? AND status IN ({$placeholders})";
    $params = array_merge([$userId], $statuses);

    if ($exceptId !== null) {
        $sql .= ' AND id <> ?';
        $params[] = $exceptId;
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}

function subscription_sync_user_role(PDO $db, int $userId): void {
    $user = subscription_lock_user($db, $userId);
    if (!$user || ($user['role'] ?? '') === 'admin') {
        return;
    }

    $stmt = $db->prepare(
        "SELECT id FROM subscriptions
         WHERE user_id = ?
           AND status = 'active'
           AND (expires_at IS NULL OR expires_at > NOW())
         LIMIT 1"
    );
    $stmt->execute([$userId]);
    $role = $stmt->fetch() ? 'subscriber' : 'user';

    if (($user['role'] ?? '') !== $role) {
        $stmt = $db->prepare('UPDATE users SET role = ? WHERE id = ?');
        $stmt->execute([$role, $userId]);
    }
}
