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
$stmt = $pdo->prepare("SELECT * FROM farmers WHERE farmer_id = ? AND is_deleted = FALSE");
$stmt->execute([$id]);
$farmer = $stmt->fetch();

if (!$farmer) {
    flashError('Farmer not found.');
    header('Location: index.php');
    exit;
}

$pdo->prepare("UPDATE farmers SET is_deleted = TRUE WHERE farmer_id = ?")->execute([$id]);
logActivity("Archived farmer #$id ({$farmer['first_name']} {$farmer['last_name']}).");
flashSuccess('Farmer deleted successfully.');
header('Location: index.php');
exit;
