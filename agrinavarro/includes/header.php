<?php
$pageTitle = $pageTitle ?? 'Dashboard';
$recentNotifications = [];
$unreadNotificationCount = 0;
$notificationReadThrough = 0;

try {
    $adminView = isAdmin();
    $userId = (int)$_SESSION['user_id'];
    $watermark = getDB()->prepare('SELECT last_notification_read_id FROM users WHERE user_id = ?');
    $watermark->execute([$userId]);
    $notificationReadThrough = (int)$watermark->fetchColumn();

    $scope = $adminView ? '' : ' WHERE a.user_id = ?';
    $unreadSql = 'SELECT COUNT(*) FROM activity_logs a' . $scope
        . ($scope === '' ? ' WHERE a.log_id > ?' : ' AND a.log_id > ?');
    $unreadQuery = getDB()->prepare($unreadSql);
    $unreadQuery->execute($adminView ? [$notificationReadThrough] : [$userId, $notificationReadThrough]);
    $unreadNotificationCount = (int)$unreadQuery->fetchColumn();

    $notificationSql = 'SELECT a.log_id, a.log_action, a.created_at, u.username
                        FROM activity_logs a
                        LEFT JOIN users u ON u.user_id = a.user_id' . $scope;

    $notificationSql .= ' ORDER BY a.created_at DESC, a.log_id DESC LIMIT 8';
    $notificationQuery = getDB()->prepare($notificationSql);
    if ($adminView) {
        $notificationQuery->execute();
    } else {
        $notificationQuery->execute([$userId]);
    }
    $recentNotifications = $notificationQuery->fetchAll();
} catch (Throwable $exception) {
    error_log('Recent activity notifications could not be loaded: ' . $exception->getMessage());
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | DataCorePoint</title>
    <link rel="icon" type="image/svg+xml" href="<?= getBaseUrl() ? getBaseUrl() . '/assets/img/favicon.svg' : 'assets/img/favicon.svg' ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <script>
        tailwind = {
            config: {
                corePlugins: { preflight: false },
                darkMode: "class",
                theme: {
                    extend: {
                        fontFamily: { sans: ["Inter", "sans-serif"], mono: ["JetBrains Mono", "monospace"] },
                        colors: {
                            core: {
                                50: "#eff6ff",
                                100: "#dbeafe",
                                400: "#60a5fa",
                                500: "#3b82f6",
                                600: "#2563eb",
                                900: "#1e3a8a",
                                950: "#172554"
                            }
                        }
                    }
                }
            }
        };
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="<?= getBaseUrl() ? getBaseUrl() . '/assets/css/datacore.css' : 'assets/css/datacore.css' ?>?v=<?= filemtime(__DIR__ . '/../assets/css/datacore.css') ?>">
</head>
<body>
<div class="page-wrapper">
<?php require_once __DIR__ . '/sidebar.php'; ?>
<div class="main-content">
<div class="topbar d-flex align-items-center justify-content-between px-4">
<button class="mobile-nav-toggle btn btn-outline-secondary" type="button" aria-label="Open navigation menu" aria-controls="sidebar" aria-expanded="false" id="mobileNavToggle">
<i class="bi bi-list" aria-hidden="true"></i>
</button>
<h5 class="mb-0 fw-semibold"><i class="bi bi-grid-1x2-fill me-2"></i><?= htmlspecialchars($pageTitle) ?></h5>
<div class="topbar-actions">
<span class="topbar-date text-muted small"><i class="bi bi-clock me-1"></i><?= date('D, M j Y') ?></span>
<div class="dropdown topbar-notifications">
<button class="btn btn-light notification-toggle" type="button" id="notificationsMenu" data-bs-toggle="dropdown" data-read-url="<?= htmlspecialchars(getBaseUrl() . '/notifications/read.php', ENT_QUOTES, 'UTF-8') ?>" data-csrf-token="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>" aria-expanded="false" aria-label="Recent activity notifications">
<i class="bi bi-bell" aria-hidden="true"></i>
<?php if ($unreadNotificationCount > 0): ?><span class="notification-count" id="notificationCount"><?= $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount ?></span><?php endif; ?>
</button>
<div class="dropdown-menu dropdown-menu-end notification-menu" aria-labelledby="notificationsMenu">
<h6 class="dropdown-header">Recent activity</h6>
<?php if (!$recentNotifications): ?>
<div class="notification-empty">No recent activity yet.</div>
<?php else: foreach ($recentNotifications as $notification): $isUnread = (int)$notification['log_id'] > $notificationReadThrough; ?>
<div class="notification-item<?= $isUnread ? ' is-unread' : '' ?>">
<span class="notification-action"><?= htmlspecialchars($notification['log_action'], ENT_QUOTES, 'UTF-8') ?></span>
<small><?= htmlspecialchars($notification['username'] ?? 'Former user', ENT_QUOTES, 'UTF-8') ?>
<?php if ($isUnread): ?><span class="notification-new">New</span><?php endif; ?>
<?php if (!empty($notification['created_at'])): ?>
&middot; <?= htmlspecialchars(date('M j, g:i a', strtotime($notification['created_at'])), ENT_QUOTES, 'UTF-8') ?>
<?php endif; ?>
</small>
</div>
<?php endforeach; endif; ?>
<?php if (isAdmin()): ?><a class="dropdown-item notification-all" href="<?= getBaseUrl() ?>/admin/activity-logs.php">View all activity logs</a><?php endif; ?>
</div>
</div>
</div>
</div>
<div class="content-area">
