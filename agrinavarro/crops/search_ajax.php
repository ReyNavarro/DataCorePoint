<?php

require_once __DIR__ . '/../includes/session.php';
dcpStartSession();
require_once '../includes/auth.php';
requireLogin();
require_once '../includes/db.php';
$pdo = getDB();
$search = trim($_GET['search'] ?? '');
$catId = (int)($_GET['category_id'] ?? 0);
$seaId = (int)($_GET['season_id'] ?? 0);
$where = ["c.is_deleted=FALSE"];
$params = [];
if ($search !== '') {
    $where[] = "(c.crop_name ILIKE ? OR c.variety ILIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($catId > 0) {
    $where[] = "c.category_id=?";
    $params[] = $catId;
}
if ($seaId > 0) {
    $where[] = "c.season_id=?";
    $params[] = $seaId;
}
$whereSQL = implode(' AND ', $where);
$stmt = $pdo->prepare("SELECT c.*,cc.category_name,s.season_name FROM crops c LEFT JOIN crop_categories cc ON c.category_id=cc.category_id LEFT JOIN seasons s ON c.season_id=s.season_id WHERE $whereSQL ORDER BY c.crop_id DESC LIMIT 50");
$stmt->execute($params);
$crops = $stmt->fetchAll();
header('Content-Type: text/html; charset=utf-8');
if (empty($crops)) {
    echo '<tr><td colspan="7" class="text-center py-4 text-muted">No crops found.</td></tr>';
} else {
    foreach ($crops as $c) {
        echo '<tr><td class="text-muted small">#'.$c['crop_id'].'</td><td class="fw-semibold">'.htmlspecialchars($c['crop_name']).'</td><td>'.htmlspecialchars($c['variety'] ?? '—').'</td><td><span class="badge bg-warning text-dark">'.htmlspecialchars($c['category_name'] ?? '—').'</span></td><td>'.($c['growing_period_days'] !== null ? $c['growing_period_days'].' days' : '—').'</td><td>'.htmlspecialchars($c['season_name'] ?? '—').'</td><td class="text-center"><a href="edit.php?id='.$c['crop_id'].'" class="btn btn-sm btn-outline-warning me-1"><i class="bi bi-pencil"></i></a><a href="delete.php?id='.$c['crop_id'].'" class="btn btn-sm btn-outline-danger" onclick="return confirm(\'Delete?\')"><i class="bi bi-trash"></i></a></td></tr>';
    }
}
