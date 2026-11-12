<?php

require_once __DIR__ . '/../includes/session.php';
dcpStartSession();
require_once '../includes/auth.php';
requireLogin();
require_once '../includes/db.php';
$pdo = getDB();
$search = trim($_GET['search'] ?? '');
$statusId = (int)($_GET['status_id'] ?? 0);
$where = ['COALESCE(p.is_deleted,FALSE)=FALSE'];
$params = [];
if ($search !== '') {
    $where[] = '(f.farm_name ILIKE ? OR f.location ILIKE ? OR c.crop_name ILIKE ?)';
    $term = '%'.$search.'%';
    array_push($params, $term, $term, $term);
}if ($statusId > 0) {
    $where[] = 'p.status_id=?';
    $params[] = $statusId;
}
$sql = 'SELECT p.planting_id,p.planting_date,p.area_planted,p.seed_quantity,p.expected_harvest_date,f.farm_name,f.location,c.crop_name,ps.status_name FROM plantings p JOIN farms f ON f.farm_id=p.farm_id JOIN crops c ON c.crop_id=p.crop_id JOIN planting_statuses ps ON ps.status_id=p.status_id WHERE '.implode(' AND ', $where).' ORDER BY p.planting_id DESC LIMIT 50';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
header('Content-Type: text/html; charset=utf-8');
$colors = ['Planted' => 'success','Growing' => 'info','Harvested' => 'primary','Failed' => 'danger','Cancelled' => 'secondary'];
if (!$rows) {
    echo '<tr><td colspan="9" class="text-center py-4 text-muted">No plantings found.</td></tr>';
    exit;
}
foreach ($rows as $p) {
    $id = (int)$p['planting_id'];
    $farm = htmlspecialchars($p['farm_name'], ENT_QUOTES, 'UTF-8');
    $location = htmlspecialchars($p['location'] ?? '—', ENT_QUOTES, 'UTF-8');
    $crop = htmlspecialchars($p['crop_name'], ENT_QUOTES, 'UTF-8');
    $date = htmlspecialchars($p['planting_date'] ?? '—', ENT_QUOTES, 'UTF-8');
    $area = $p['area_planted'] !== null ? number_format((float)$p['area_planted'], 2) : '—';
    $seed = $p['seed_quantity'] !== null ? number_format((float)$p['seed_quantity'], 2) : '—';
    $expected = htmlspecialchars($p['expected_harvest_date'] ?? '—', ENT_QUOTES, 'UTF-8');
    $status = htmlspecialchars($p['status_name'], ENT_QUOTES, 'UTF-8');
    $color = $colors[$p['status_name']] ?? 'secondary';
    $name = htmlspecialchars($p['crop_name'].' @ '.$p['farm_name'], ENT_QUOTES, 'UTF-8');
    echo "<tr><td class=\"text-muted small\">#$id</td><td class=\"fw-semibold\">$farm<br><small class=\"text-muted\">$location</small></td><td>$crop</td><td>$date</td><td>$area</td><td>$seed</td><td>$expected</td><td><span class=\"badge bg-$color\">$status</span></td><td class=\"text-center\"><a href=\"edit.php?id=$id\" class=\"btn btn-sm btn-outline-warning me-1\"><i class=\"bi bi-pencil\"></i></a><button class=\"btn btn-sm btn-outline-danger\" data-bs-toggle=\"modal\" data-bs-target=\"#deleteModal\" data-id=\"$id\" data-name=\"$name\"><i class=\"bi bi-trash\"></i></button></td></tr>";
}
