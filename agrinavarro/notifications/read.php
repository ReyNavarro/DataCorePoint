<?php
require_once __DIR__ . '/../includes/session.php';
dcpStartSession();
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=UTF-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false]);
    exit;
}

$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
if (!is_string($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    http_response_code(403);
    echo json_encode(['ok' => false]);
    exit;
}

$pdo = getDB();
$latest = $pdo->prepare(
    isAdmin()
        ? 'SELECT COALESCE(MAX(log_id), 0) FROM activity_logs'
        : 'SELECT COALESCE(MAX(log_id), 0) FROM activity_logs WHERE user_id = ?'
);
$latest->execute(isAdmin() ? [] : [(int)$_SESSION['user_id']]);
$readThrough = (int)$latest->fetchColumn();
$update = $pdo->prepare(
    'UPDATE users SET last_notification_read_id = GREATEST(last_notification_read_id, ?) WHERE user_id = ?'
);
$update->execute([$readThrough, (int)$_SESSION['user_id']]);

echo json_encode(['ok' => true, 'readThrough' => $readThrough]);
