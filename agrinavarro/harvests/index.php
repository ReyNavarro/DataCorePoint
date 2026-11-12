<?php
require_once __DIR__ . '/../includes/session.php';
dcpStartSession();
require_once '../includes/auth.php';
requireLogin();
require_once '../includes/db.php';
$pdo = getDB();
$search = trim($_GET['search'] ?? '');
$gradeId = (int)($_GET['grade_id'] ?? 0);
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$grades = $pdo->query('SELECT grade_id,grade_name FROM quality_grades ORDER BY grade_name')->fetchAll();
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
$countStmt = $pdo->prepare('SELECT COUNT(*) FROM harvests h LEFT JOIN plantings p ON h.planting_id=p.planting_id LEFT JOIN farms f ON p.farm_id=f.farm_id LEFT JOIN crops c ON p.crop_id=c.crop_id WHERE '.implode(' AND ', $where));
$countStmt->execute($params);
$totalPages = max(1, (int)ceil((int)$countStmt->fetchColumn() / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$sql = "SELECT h.harvest_id,h.planting_id,h.harvest_date,h.quantity_kg,h.selling_price_per_kg,qg.grade_name,hr.remark_description,c.crop_name,f.farm_name,f.location FROM harvests h LEFT JOIN plantings p ON h.planting_id=p.planting_id LEFT JOIN farms f ON p.farm_id=f.farm_id LEFT JOIN crops c ON p.crop_id=c.crop_id LEFT JOIN quality_grades qg ON h.grade_id=qg.grade_id LEFT JOIN harvest_remarks hr ON h.remark_id=hr.remark_id WHERE " . implode(' AND ', $where) . ' ORDER BY h.harvest_id DESC LIMIT ' . (int)$perPage . ' OFFSET ' . (int)$offset;
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$harvests = $stmt->fetchAll();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$pageTitle = 'Harvests';
require_once '../includes/header.php';
?>
<?= displayFlash() ?>
<div class="d-flex justify-content-between align-items-center mb-3"><div><h1 class="h4 mb-1">Harvest records</h1><p class="text-muted small mb-0">Yield and quality recorded for each planting.</p></div><a href="create.php" class="btn btn-success"><i class="bi bi-plus-lg me-1"></i>Record harvest</a></div>
<form method="get" class="card mb-3"><div class="card-body"><div class="row g-2 align-items-end"><div class="col-md-8"><label for="searchInput" class="form-label small mb-1">Search harvests</label><input id="searchInput" name="search" data-live-search="search_ajax.php" data-live-target="harvestTableBody" type="search" class="form-control" placeholder="Crop, farm, or location…" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" autocomplete="off"></div><div class="col-md-4"><label for="gradeFilter" class="form-label small mb-1">Quality grade</label><select id="gradeFilter" name="grade_id" class="form-select"><option value="">All grades</option><?php foreach ($grades as $grade): ?><option value="<?= (int)$grade['grade_id'] ?>" <?= $gradeId === (int)$grade['grade_id'] ? 'selected' : '' ?>><?= htmlspecialchars($grade['grade_name']) ?></option><?php endforeach; ?></select></div></div></div></form>
<div class="card"><div class="card-body p-0 table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>ID</th><th>Crop / Farm</th><th>Harvest date</th><th>Yield (kg)</th><th>Price / kg</th><th>Quality</th><th>Remarks</th><th class="text-center">Actions</th></tr></thead><tbody id="harvestTableBody">
<?php if (!$harvests): ?><tr><td colspan="8" class="text-center text-muted py-4">No active harvest records found.</td></tr><?php else: foreach ($harvests as $h): ?>
<tr><td class="text-muted small">#<?= (int)$h['harvest_id'] ?></td><td class="fw-semibold"><?= htmlspecialchars($h['crop_name'] ?? '—') ?><br><small class="text-muted"><?= htmlspecialchars($h['farm_name'] ?? $h['location'] ?? '—') ?></small></td><td><?= htmlspecialchars($h['harvest_date'] ?? '—') ?></td><td><?= $h['quantity_kg'] !== null ? number_format((float)$h['quantity_kg'], 2) : '—' ?></td><td><?= $h['selling_price_per_kg'] !== null ? number_format((float)$h['selling_price_per_kg'], 2) : '—' ?></td><td><?= htmlspecialchars($h['grade_name'] ?? '—') ?></td><td><?= htmlspecialchars($h['remark_description'] ?? '—') ?></td><td class="text-center"><a href="edit.php?id=<?= (int)$h['harvest_id'] ?>" class="btn btn-sm btn-outline-primary" aria-label="Edit harvest">Edit</a><form method="post" action="delete.php" class="d-inline" onsubmit="return confirm('Move this harvest to Archived Records?')"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"><input type="hidden" name="id" value="<?= (int)$h['harvest_id'] ?>"><button class="btn btn-sm btn-outline-danger" type="submit">Delete</button></form></td></tr>
<?php endforeach; endif; ?></tbody></table></div></div>
<?php if ($totalPages > 1): ?><nav class="mt-3" data-live-pagination aria-label="Harvest pages"><ul class="pagination pagination-sm justify-content-center"><?php for ($n = 1;$n <= $totalPages;$n++):$query = $_GET;
    $query['page'] = $n; ?><li class="page-item <?= $n === $page ? 'active' : '' ?>"><a class="page-link" href="?<?= htmlspecialchars(http_build_query($query), ENT_QUOTES, 'UTF-8') ?>"><?= $n ?></a></li><?php endfor; ?></ul></nav><?php endif; ?>
<?php require_once '../includes/footer.php'; ?>
