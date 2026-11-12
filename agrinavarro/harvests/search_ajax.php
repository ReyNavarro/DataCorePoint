<?php

require_once __DIR__ . '/../includes/session.php';
dcpStartSession();
require_once '../includes/auth.php';
requireLogin();
require_once '../includes/db.php';
$pdo = getDB();
$search = trim($_GET['search'] ?? '');
$gradeId = (int)($_GET['grade_id'] ?? 0);
$where = ['COALESCE(h.is_deleted,FALSE)=FALSE'];
$params = [];
if ($search !== '') {
    $where[] = '(c.crop_name ILIKE ? OR f.farm_name ILIKE ? OR f.location ILIKE ?)';
    $term = '%'.$search.'%';
    array_push($params, $term, $term, $term);
}
if ($gradeId > 0) {
    $where[] = 'h.grade_id=?';
    $params[] = $gradeId;
}
$sql = "SELECT h.harvest_id,h.planting_id,h.harvest_date,h.quantity_kg,h.selling_price_per_kg,qg.grade_name,hr.remark_description,c.crop_name,f.farm_name,f.location FROM harvests h LEFT JOIN plantings p ON h.planting_id=p.planting_id LEFT JOIN farms f ON p.farm_id=f.farm_id LEFT JOIN crops c ON p.crop_id=c.crop_id LEFT JOIN quality_grades qg ON h.grade_id=qg.grade_id LEFT JOIN harvest_remarks hr ON h.remark_id=hr.remark_id WHERE ".implode(' AND ', $where).' ORDER BY h.harvest_id DESC LIMIT 50';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
header('Content-Type: text/html; charset=utf-8');
if (!$rows) {
    echo '<tr><td colspan="8" class="text-center text-muted py-4">No active harvest records found.</td></tr>';
    exit;
}
foreach ($rows as $h) {
    $id = (int)$h['harvest_id'];
    $crop = htmlspecialchars($h['crop_name'] ?? '—', ENT_QUOTES, 'UTF-8');
    $farm = htmlspecialchars($h['farm_name'] ?? $h['location'] ?? '—', ENT_QUOTES, 'UTF-8');
    $date = htmlspecialchars($h['harvest_date'] ?? '—', ENT_QUOTES, 'UTF-8');
    $qty = $h['quantity_kg'] !== null ? number_format((float)$h['quantity_kg'], 2) : '—';
    $price = $h['selling_price_per_kg'] !== null ? number_format((float)$h['selling_price_per_kg'], 2) : '—';
    $grade = htmlspecialchars($h['grade_name'] ?? '—', ENT_QUOTES, 'UTF-8');
    $remark = htmlspecialchars($h['remark_description'] ?? '—', ENT_QUOTES, 'UTF-8');
    $token = htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8');
    echo "<tr><td class=\"text-muted small\">#$id</td><td class=\"fw-semibold\">$crop<br><small class=\"text-muted\">$farm</small></td><td>$date</td><td>$qty</td><td>$price</td><td>$grade</td><td>$remark</td><td class=\"text-center\"><a href=\"edit.php?id=$id\" class=\"btn btn-sm btn-outline-primary\">Edit</a> <form method=\"post\" action=\"delete.php\" class=\"d-inline\" onsubmit=\"return confirm('Move this harvest to Archived Records?')\"><input type=\"hidden\" name=\"csrf_token\" value=\"$token\"><input type=\"hidden\" name=\"id\" value=\"$id\"><button class=\"btn btn-sm btn-outline-danger\" type=\"submit\">Archive</button></form></td></tr>";
}
