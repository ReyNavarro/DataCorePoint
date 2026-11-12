<?php
require_once __DIR__ . '/../includes/session.php';
dcpStartSession();
require_once '../includes/auth.php';
requireLogin();
require_once '../includes/db.php';

$pdo = getDB();
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$search    = trim($_GET['search'] ?? '');
$soilId    = (int)($_GET['soil_id'] ?? 0);
$irrId     = (int)($_GET['irrigation_id'] ?? 0);
$sort      = in_array($_GET['sort'] ?? '', ['f.farm_id','f.farm_name','f.area_hectares']) ? $_GET['sort'] : 'f.farm_id';
$dir       = (($_GET['dir'] ?? 'asc') === 'desc') ? 'DESC' : 'ASC';

$where  = ["f.is_deleted = FALSE"];
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

$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM farms f JOIN farmers fa ON f.farmer_id = fa.farmer_id WHERE $whereSQL");
$totalStmt->execute($params);
$totalRecords = $totalStmt->fetchColumn();
$totalPages = max(1, ceil($totalRecords / $perPage));

$stmt = $pdo->prepare("
    SELECT f.*, fa.first_name, fa.last_name, st.soil_name, it.irrigation_name
    FROM farms f
    JOIN farmers fa ON f.farmer_id = fa.farmer_id
    LEFT JOIN soil_types st ON f.soil_id = st.soil_id
    LEFT JOIN irrigation_types it ON f.irrigation_id = it.irrigation_id
    WHERE $whereSQL ORDER BY $sort $dir LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$farms = $stmt->fetchAll();

$soilTypes   = $pdo->query("SELECT * FROM soil_types ORDER BY soil_name")->fetchAll();
$irrTypes    = $pdo->query("SELECT * FROM irrigation_types ORDER BY irrigation_name")->fetchAll();

function sortLink2($col, $label, $sort, $dir)
{
    $newDir = ($sort === $col && $dir === 'ASC') ? 'desc' : 'asc';
    $icon = $sort === $col ? ($dir === 'ASC' ? '↑' : '↓') : '';
    $params = $_GET;
    $params['sort'] = $col;
    $params['dir'] = $newDir;
    $params['page'] = 1;
    return '<a href="?' . http_build_query($params) . '" class="text-dark text-decoration-none">' . $label . ' <span class="text-muted">' . $icon . '</span></a>';
}

$pageTitle = 'Farms';
require_once '../includes/header.php';
?>
<?= displayFlash() ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h6 class="mb-0 text-muted">Total: <strong><?= $totalRecords ?></strong> farms</h6>
  <a href="create.php" class="btn btn-success"><i class="bi bi-plus-lg me-1"></i>Add Farm</a>
</div>
<div class="card mb-3">
  <div class="card-body">
    <form method="get" class="row g-2 align-items-end">
      <div class="col-md-3">
        <label class="form-label small mb-1">Search</label>
        <input type="search" name="search" id="searchInput" data-live-search="search_ajax.php" data-live-target="farmTableBody" class="form-control form-control-sm" placeholder="Name, location, owner..." value="<?= htmlspecialchars($search) ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label small mb-1">Soil Type</label>
        <select name="soil_id" class="form-select form-select-sm">
          <option value="">All Soils</option>
          <?php foreach ($soilTypes as $s): ?><option value="<?= $s['soil_id'] ?>" <?= $soilId == $s['soil_id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['soil_name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small mb-1">Irrigation</label>
        <select name="irrigation_id" class="form-select form-select-sm">
          <option value="">All Types</option>
          <?php foreach ($irrTypes as $i): ?><option value="<?= $i['irrigation_id'] ?>" <?= $irrId == $i['irrigation_id'] ? 'selected' : '' ?>><?= htmlspecialchars($i['irrigation_name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-auto">
        <button type="submit" class="btn btn-sm btn-primary">Filter</button>
        <a href="index.php" class="btn btn-sm btn-outline-secondary ms-1">Reset</a>
      </div>
      <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
      <input type="hidden" name="dir" value="<?= htmlspecialchars($dir) ?>">
    </form>
  </div>
</div>

<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th><?= sortLink2('f.farm_id', 'ID', $sort, $dir) ?></th>
            <th><?= sortLink2('f.farm_name', 'Farm Name', $sort, $dir) ?></th>
            <th>Owner</th>
            <th>Location</th>
            <th><?= sortLink2('f.area_hectares', 'Area (ha)', $sort, $dir) ?></th>
            <th>Soil Type</th>
            <th>Irrigation</th>
            <th class="text-center">Actions</th>
          </tr>
        </thead>
        <tbody id="farmTableBody">
        <?php if (empty($farms)): ?>
        <tr><td colspan="8" class="text-center py-4 text-muted">No farms found.</td></tr>
        <?php else: foreach ($farms as $f): ?>
        <tr>
          <td class="text-muted small">#<?= $f['farm_id'] ?></td>
          <td class="fw-semibold"><?= htmlspecialchars($f['farm_name']) ?></td>
          <td><?= htmlspecialchars($f['first_name'] . ' ' . $f['last_name']) ?></td>
          <td><?= htmlspecialchars($f['location'] ?? '—') ?></td>
          <td><?= $f['area_hectares'] !== null ? number_format($f['area_hectares'], 2) : '—' ?></td>
          <td><?= htmlspecialchars($f['soil_name'] ?? '—') ?></td>
          <td><?= htmlspecialchars($f['irrigation_name'] ?? '—') ?></td>
          <td class="text-center">
            <a href="edit.php?id=<?= $f['farm_id'] ?>" class="btn btn-sm btn-outline-warning me-1"><i class="bi bi-pencil"></i></a>
            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal" data-id="<?= $f['farm_id'] ?>" data-name="<?= htmlspecialchars($f['farm_name']) ?>">
              <i class="bi bi-trash"></i>
            </button>
          </td>
        </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php if ($totalPages > 1): ?>
<nav class="mt-3" data-live-pagination><ul class="pagination pagination-sm justify-content-center">
  <?php for ($i = 1;$i <= $totalPages;$i++): ?>
  <li class="page-item <?= $i === $page ? 'active' : '' ?>"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a></li>
  <?php endfor; ?>
</ul></nav>
<?php endif; ?>

<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header bg-danger text-white"><h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill me-2"></i>Confirm Delete</h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">Delete farm <strong id="dName"></strong>?</div>
    <div class="modal-footer">
      <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      <a id="deleteBtn" href="#" class="btn btn-danger">Delete</a>
    </div>
  </div></div>
</div>
<script>
document.getElementById('deleteModal').addEventListener('show.bs.modal',function(e){
  var btn=e.relatedTarget;
  document.getElementById('dName').textContent=btn.dataset.name;
  document.getElementById('deleteBtn').href='delete.php?id='+btn.dataset.id;
});
</script>
<?php require_once '../includes/footer.php'; ?>
