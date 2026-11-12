<?php

require_once __DIR__ . '/../includes/session.php';
dcpStartSession();
require_once '../includes/auth.php';
requireLogin();
require_once '../includes/db.php';

$pdo = getDB();
$search = trim($_GET['search'] ?? '');
$soilId = (int)($_GET['soil_id'] ?? 0);
$irrId  = (int)($_GET['irrigation_id'] ?? 0);

$where = ["f.is_deleted = FALSE"];
$params = [];
if ($search !== '') {
    $where[] = "(f.farm_name ILIKE ? OR f.location ILIKE ? OR fa.first_name ILIKE ? OR fa.last_name ILIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($soilId > 0) {
    $where[] = "f.soil_id = ?";
    $params[] = $soilId;
}
if ($irrId  > 0) {
    $where[] = "f.irrigation_id = ?";
    $params[] = $irrId;
}
$whereSQL = implode(' AND ', $where);

$stmt = $pdo->prepare("SELECT f.*, fa.first_name, fa.last_name, st.soil_name, it.irrigation_name FROM farms f JOIN farmers fa ON f.farmer_id=fa.farmer_id LEFT JOIN soil_types st ON f.soil_id=st.soil_id LEFT JOIN irrigation_types it ON f.irrigation_id=it.irrigation_id WHERE $whereSQL ORDER BY f.farm_id DESC LIMIT 50");
$stmt->execute($params);
$farms = $stmt->fetchAll();
header('Content-Type: text/html; charset=utf-8');
if (empty($farms)) {
    echo '<tr><td colspan="8" class="text-center py-4 text-muted">No farms found.</td></tr>';
} else {
    foreach ($farms as $f) {
        echo '<tr><td class="text-muted small">#' . $f['farm_id'] . '</td>';
        echo '<td class="fw-semibold">' . htmlspecialchars($f['farm_name']) . '</td>';
        echo '<td>' . htmlspecialchars($f['first_name'].' '.$f['last_name']) . '</td>';
        echo '<td>' . htmlspecialchars($f['location'] ?? '—') . '</td>';
        echo '<td>' . ($f['area_hectares'] !== null ? number_format($f['area_hectares'], 2) : '—') . '</td>';
        echo '<td>' . htmlspecialchars($f['soil_name'] ?? '—') . '</td>';
        echo '<td>' . htmlspecialchars($f['irrigation_name'] ?? '—') . '</td>';
        echo '<td class="text-center"><a href="edit.php?id='.$f['farm_id'].'" class="btn btn-sm btn-outline-warning me-1"><i class="bi bi-pencil"></i></a><a href="delete.php?id='.$f['farm_id'].'" class="btn btn-sm btn-outline-danger" onclick="return confirm(\'Delete?\')"><i class="bi bi-trash"></i></a></td></tr>';
    }
}
