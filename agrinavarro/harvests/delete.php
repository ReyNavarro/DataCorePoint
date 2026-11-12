<?php

require_once __DIR__ . '/../includes/session.php';
dcpStartSession();
require_once '../includes/auth.php';
requireLogin();
require_once '../includes/db.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
    flashError('Invalid form token. Please try again.');
    header('Location: index.php');
    exit;
}
$id = (int)($_POST['id'] ?? 0);
if ($id > 0) {
    $pdo = getDB();
    $stmt = $pdo->prepare('UPDATE harvests SET is_deleted=TRUE WHERE harvest_id=? AND COALESCE(is_deleted,FALSE)=FALSE');
    $stmt->execute([$id]);
    if ($stmt->rowCount()) {
        logActivity("Archived harvest record #$id.");
        flashSuccess('Harvest moved to Archived Records.');
    } else {
        flashError('Harvest record was not found.');
    }
} else {
    flashError('Invalid harvest record.');
}
header('Location: index.php');
exit;
