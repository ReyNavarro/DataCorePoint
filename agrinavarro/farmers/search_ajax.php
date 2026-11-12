<?php

require_once __DIR__ . '/../includes/session.php';
dcpStartSession();
require_once '../includes/auth.php';
requireLogin();
require_once '../includes/db.php';

$pdo = getDB();
$search  = trim($_GET['search'] ?? '');
$address = trim($_GET['address'] ?? '');
$sort    = in_array($_GET['sort'] ?? '', ['farmer_id','first_name','last_name']) ? $_GET['sort'] : 'farmer_id';
$dir     = (($_GET['dir'] ?? 'asc') === 'desc') ? 'DESC' : 'ASC';

$where = ["is_deleted = FALSE"];
$params = [];
if ($search !== '') {
    $where[] = "(first_name ILIKE ? OR last_name ILIKE ? OR contact_number ILIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($address !== '') {
    $where[] = "address ILIKE ?";
    $params[] = "%$address%";
}
$whereSQL = implode(' AND ', $where);
$stmt = $pdo->prepare("SELECT * FROM farmers WHERE $whereSQL ORDER BY $sort $dir LIMIT 50");
$stmt->execute($params);
$farmers = $stmt->fetchAll();
header('Content-Type: text/html; charset=utf-8');
if (empty($farmers)) {
    echo '<tr><td colspan="5" class="text-center py-4 text-muted">No farmers found.</td></tr>';
} else {
    foreach ($farmers as $f) {
        echo '<tr>';
        echo '<td class="text-muted small">#' . $f['farmer_id'] . '</td>';
        echo '<td class="fw-semibold">' . htmlspecialchars($f['first_name'] . ' ' . $f['last_name']) . '</td>';
        echo '<td>' . htmlspecialchars($f['contact_number'] ?? '—') . '</td>';
        echo '<td>' . htmlspecialchars($f['address'] ?? '—') . '</td>';
        echo '<td class="text-center">';
        echo '<a href="edit.php?id=' . $f['farmer_id'] . '" class="btn btn-sm btn-outline-warning me-1"><i class="bi bi-pencil"></i></a>';
        echo '<a href="delete.php?id=' . $f['farmer_id'] . '" class="btn btn-sm btn-outline-danger" onclick="return confirm(\'Delete this farmer?\')"><i class="bi bi-trash"></i></a>';
        echo '</td>';
        echo '</tr>';
    }
}
