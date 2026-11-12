<?php
require_once __DIR__ . '/../includes/session.php';
dcpStartSession();
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed');
}

$plantingId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$plantingId || $plantingId < 1) {
    flashError('Choose a valid planting record.');
    header('Location: index.php');
    exit;
}

$pdo = getDB();
$dependentHarvests = $pdo->prepare(
    'SELECT COUNT(*) FROM harvests WHERE planting_id = ? AND COALESCE(is_deleted, FALSE) = FALSE'
);
$dependentHarvests->execute([$plantingId]);

if ((int)$dependentHarvests->fetchColumn() > 0) {
    flashError('This planting has active harvest records. Archive those harvests first.');
} else {
    $archive = $pdo->prepare(
        'UPDATE plantings SET is_deleted = TRUE WHERE planting_id = ? AND COALESCE(is_deleted, FALSE) = FALSE'
    );
    $archive->execute([$plantingId]);

    if ($archive->rowCount() > 0) {
        logActivity("Archived planting record #$plantingId.");
        flashSuccess('Planting moved to Archived Records.');
    } else {
        flashError('Planting record was not found.');
    }
}

header('Location: index.php');
exit;
