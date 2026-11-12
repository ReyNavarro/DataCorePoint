<?php

require_once __DIR__ . '/../includes/session.php';
dcpStartSession();
require_once '../includes/auth.php';
requireLogin();
require_once '../includes/db.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    flashError('Invalid ID.');
    header('Location: index.php');
    exit;
}

$pdo = getDB();
$stmt = $pdo->prepare("SELECT * FROM farms WHERE farm_id = ? AND is_deleted = FALSE");
$stmt->execute([$id]);
$farm = $stmt->fetch();
if (!$farm) {
    flashError('Farm not found.');
    header('Location: index.php');
    exit;
}

$childCheck = $pdo->prepare('SELECT COUNT(*) FROM plantings WHERE farm_id = ? AND COALESCE(is_deleted, FALSE) = FALSE');
$childCheck->execute([$id]);
if ((int)$childCheck->fetchColumn() > 0) {
    flashError('This farm still has active planting records. Delete or archive those plantings first.');
    header('Location: index.php');
    exit;
}

$pdo->prepare("UPDATE farms SET is_deleted = TRUE WHERE farm_id = ?")->execute([$id]);
logActivity("Archived farm #$id ({$farm['farm_name']}).");
flashSuccess('Farm deleted successfully.');
header('Location: index.php');
exit;
