<?php
require_once __DIR__ . '/../includes/session.php';
dcpStartSession();
require_once '../includes/auth.php';
requireLogin();
require_once '../includes/db.php';

$pdo = getDB();
$search = trim($_GET['search'] ?? '');
$gradeId = (int)($_GET['grade_id'] ?? 0);
$direction = ($_GET['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
$where = ['COALESCE(h.is_deleted, FALSE) = FALSE'];
$params = [];
if ($search !== '') {
    $where[] = '(c.crop_name ILIKE ? OR f.farm_name ILIKE ? OR f.location ILIKE ?)';
    $term = '%' . $search . '%';
    array_push($params, $term, $term, $term);
}
if ($gradeId > 0) {
    $where[] = 'h.grade_id = ?';
    $params[] = $gradeId;
}
$sql = 'SELECT h.harvest_id,h.harvest_date,h.quantity_kg,h.selling_price_per_kg,qg.grade_name,hr.remark_description,c.crop_name,f.farm_name,f.location
    FROM harvests h
    LEFT JOIN plantings p ON h.planting_id=p.planting_id
    LEFT JOIN farms f ON p.farm_id=f.farm_id
    LEFT JOIN crops c ON p.crop_id=c.crop_id
    LEFT JOIN quality_grades qg ON h.grade_id=qg.grade_id
    LEFT JOIN harvest_remarks hr ON h.remark_id=hr.remark_id
    WHERE ' . implode(' AND ', $where) . ' ORDER BY h.harvest_id ' . $direction . ' LIMIT 50';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$harvests = $stmt->fetchAll();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$token = htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8');
header('Content-Type: text/html; charset=utf-8');

if (!$harvests) {
    echo '<tr><td colspan="8" class="text-center text-muted py-4">No active harvest records found.</td></tr>';
    exit;
}

foreach ($harvests as $harvest) {
    $id = (int)$harvest['harvest_id'];
    $crop = htmlspecialchars($harvest['crop_name'] ?? '—', ENT_QUOTES, 'UTF-8');
    $farm = htmlspecialchars($harvest['farm_name'] ?? $harvest['location'] ?? '—', ENT_QUOTES, 'UTF-8');
    $date = htmlspecialchars($harvest['harvest_date'] ?? '—', ENT_QUOTES, 'UTF-8');
    $quantity = $harvest['quantity_kg'] !== null ? number_format((float)$harvest['quantity_kg'], 2) : '—';
    $price = $harvest['selling_price_per_kg'] !== null ? number_format((float)$harvest['selling_price_per_kg'], 2) : '—';
    $grade = htmlspecialchars($harvest['grade_name'] ?? '—', ENT_QUOTES, 'UTF-8');
    $remark = htmlspecialchars($harvest['remark_description'] ?? 'No remarks yet', ENT_QUOTES, 'UTF-8');
    $name = htmlspecialchars(($harvest['crop_name'] ?? 'Harvest') . ' — ' . ($harvest['farm_name'] ?? 'Farm'), ENT_QUOTES, 'UTF-8');
    echo '<tr><td class="text-muted small">#' . $id . '</td><td class="fw-semibold">' . $crop . '<br><small class="text-muted">' . $farm . '</small></td><td>' . $date . '</td><td>' . $quantity . '</td><td>' . $price . '</td><td>' . $grade . '</td><td>' . $remark . '</td><td class="text-center"><a href="edit.php?id=' . $id . '" class="btn btn-sm btn-outline-warning me-1" aria-label="Edit harvest #' . $id . '" title="Edit"><i class="bi bi-pencil" aria-hidden="true"></i></a><button class="btn btn-sm btn-outline-danger" type="button" data-bs-toggle="modal" data-bs-target="#deleteModal" data-id="' . $id . '" data-name="' . $name . '" aria-label="Delete harvest #' . $id . '" title="Delete"><i class="bi bi-trash" aria-hidden="true"></i></button></td></tr>';
}
