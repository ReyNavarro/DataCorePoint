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
$direction = ($_GET['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
$grades = $pdo->query('SELECT grade_id, grade_name FROM quality_grades ORDER BY grade_name')->fetchAll();

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
$whereSql = implode(' AND ', $where);

$count = $pdo->prepare('SELECT COUNT(*) FROM harvests h LEFT JOIN plantings p ON h.planting_id=p.planting_id LEFT JOIN farms f ON p.farm_id=f.farm_id LEFT JOIN crops c ON p.crop_id=c.crop_id WHERE ' . $whereSql);
$count->execute($params);
$totalPages = max(1, (int)ceil((int)$count->fetchColumn() / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$query = 'SELECT h.harvest_id,h.planting_id,h.harvest_date,h.quantity_kg,h.selling_price_per_kg,qg.grade_name,hr.remark_description,c.crop_name,f.farm_name,f.location
    FROM harvests h
    LEFT JOIN plantings p ON h.planting_id=p.planting_id
    LEFT JOIN farms f ON p.farm_id=f.farm_id
    LEFT JOIN crops c ON p.crop_id=c.crop_id
    LEFT JOIN quality_grades qg ON h.grade_id=qg.grade_id
    LEFT JOIN harvest_remarks hr ON h.remark_id=hr.remark_id
    WHERE ' . $whereSql . ' ORDER BY h.harvest_id ' . $direction . ' LIMIT ' . (int)$perPage . ' OFFSET ' . (int)$offset;
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$harvests = $stmt->fetchAll();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$sortUrl = '?' . http_build_query(array_merge($_GET, ['dir' => $direction === 'ASC' ? 'desc' : 'asc', 'page' => 1]));
$sortIcon = $direction === 'ASC' ? 'bi-arrow-up' : 'bi-arrow-down';
$pageTitle = 'Harvests';
require_once '../includes/header.php';
?>
<?= displayFlash() ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h4 mb-1">Harvest records</h1>
        <p class="text-muted small mb-0">Yield and quality recorded for each planting.</p>
    </div>
    <a href="create.php" class="btn btn-success"><i class="bi bi-plus-lg me-1"></i>Record harvest</a>
</div>

<form method="get" class="card mb-3">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-8">
                <label for="searchInput" class="form-label small mb-1">Search harvests</label>
                <input id="searchInput" name="search" data-live-search="search_ajax.php" data-live-target="harvestTableBody" type="search" class="form-control" placeholder="Crop, farm, or location..." value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" autocomplete="off">
            </div>
            <div class="col-md-4">
                <label for="gradeFilter" class="form-label small mb-1">Quality grade</label>
                <select id="gradeFilter" name="grade_id" class="form-select">
                    <option value="">All grades</option>
                    <?php foreach ($grades as $grade): ?>
                        <option value="<?= (int)$grade['grade_id'] ?>" <?= $gradeId === (int)$grade['grade_id'] ? 'selected' : '' ?>><?= htmlspecialchars($grade['grade_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <input type="hidden" name="dir" value="<?= strtolower($direction) ?>">
        </div>
    </div>
</form>

<div class="card">
    <div class="card-body p-0 table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr>
                <th><a class="text-dark text-decoration-none" href="<?= htmlspecialchars($sortUrl, ENT_QUOTES, 'UTF-8') ?>">ID <i class="bi <?= $sortIcon ?>" aria-hidden="true"></i><span class="visually-hidden">, sorted <?= $direction === 'ASC' ? 'ascending' : 'descending' ?></span></a></th>
                <th>Crop / Farm</th><th>Harvest date</th><th>Yield (kg)</th><th>Price / kg</th><th>Quality</th><th>Remarks</th><th class="text-center">Actions</th>
            </tr></thead>
            <tbody id="harvestTableBody">
            <?php if (!$harvests): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">No active harvest records found.</td></tr>
            <?php else: foreach ($harvests as $harvest): $id = (int)$harvest['harvest_id']; ?>
                <tr>
                    <td class="text-muted small">#<?= $id ?></td>
                    <td class="fw-semibold"><?= htmlspecialchars($harvest['crop_name'] ?? '—') ?><br><small class="text-muted"><?= htmlspecialchars($harvest['farm_name'] ?? $harvest['location'] ?? '—') ?></small></td>
                    <td><?= htmlspecialchars($harvest['harvest_date'] ?? '—') ?></td>
                    <td><?= $harvest['quantity_kg'] !== null ? number_format((float)$harvest['quantity_kg'], 2) : '—' ?></td>
                    <td><?= $harvest['selling_price_per_kg'] !== null ? number_format((float)$harvest['selling_price_per_kg'], 2) : '—' ?></td>
                    <td><?= htmlspecialchars($harvest['grade_name'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($harvest['remark_description'] ?? 'No remarks yet') ?></td>
                    <td class="text-center">
                        <a href="edit.php?id=<?= $id ?>" class="btn btn-sm btn-outline-warning me-1" aria-label="Edit harvest #<?= $id ?>" title="Edit"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                        <button class="btn btn-sm btn-outline-danger" type="button" data-bs-toggle="modal" data-bs-target="#deleteModal" data-id="<?= $id ?>" data-name="<?= htmlspecialchars(($harvest['crop_name'] ?? 'Harvest') . ' — ' . ($harvest['farm_name'] ?? 'Farm'), ENT_QUOTES, 'UTF-8') ?>" aria-label="Delete harvest #<?= $id ?>" title="Delete"><i class="bi bi-trash" aria-hidden="true"></i></button>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($totalPages > 1): ?>
    <nav class="mt-3" data-live-pagination aria-label="Harvest pages"><ul class="pagination pagination-sm justify-content-center">
        <?php for ($number = 1; $number <= $totalPages; $number++): $params = $_GET; $params['page'] = $number; ?>
            <li class="page-item <?= $number === $page ? 'active' : '' ?>"><a class="page-link" href="?<?= htmlspecialchars(http_build_query($params), ENT_QUOTES, 'UTF-8') ?>"><?= $number ?></a></li>
        <?php endfor; ?>
    </ul></nav>
<?php endif; ?>

<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalTitle" aria-hidden="true">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header bg-danger text-white"><h2 class="modal-title fs-5" id="deleteModalTitle"><i class="bi bi-exclamation-triangle-fill me-2" aria-hidden="true"></i>Confirm Delete</h2><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button></div>
        <div class="modal-body">Delete harvest record for <strong id="deleteHarvestName"></strong>? It will be moved to Archived Records and can be restored.</div>
        <div class="modal-footer"><button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancel</button><form method="post" action="delete.php" class="d-inline"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="id" id="deleteHarvestId"><button class="btn btn-danger" type="submit">Delete</button></form></div>
    </div></div>
</div>
<script>document.getElementById('deleteModal').addEventListener('show.bs.modal',function(event){var button=event.relatedTarget;document.getElementById('deleteHarvestName').textContent=button.dataset.name;document.getElementById('deleteHarvestId').value=button.dataset.id;});</script>
<?php require_once '../includes/footer.php'; ?>
