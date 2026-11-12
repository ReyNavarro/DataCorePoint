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
$crop = $pdo->prepare("SELECT * FROM crops WHERE crop_id=? AND is_deleted=FALSE");
$crop->execute([$id]);
$crop = $crop->fetch();
if (!$crop) {
    flashError('Crop not found.');
    header('Location: index.php');
    exit;
}
$childCheck = $pdo->prepare('SELECT COUNT(*) FROM plantings WHERE crop_id = ? AND COALESCE(is_deleted, FALSE) = FALSE');
$childCheck->execute([$id]);
if ((int)$childCheck->fetchColumn() > 0) {
    flashError('This crop is used by active planting records. Delete or archive those plantings first.');
    header('Location: index.php');
    exit;
}
$pdo->prepare("UPDATE crops SET is_deleted=TRUE WHERE crop_id=?")->execute([$id]);
logActivity("Archived crop #$id ({$crop['crop_name']}).");
flashSuccess('Crop deleted successfully.');
header('Location: index.php');
exit;
