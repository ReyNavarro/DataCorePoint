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

$search   = trim($_GET['search'] ?? '');
$statusId = (int)($_GET['status_id'] ?? 0);
$sort     = in_array($_GET['sort'] ?? '', ['p.planting_id','p.planting_date','p.expected_harvest_date']) ? $_GET['sort'] : 'p.planting_id';
$dir      = (($_GET['dir'] ?? 'asc') === 'desc') ? 'DESC' : 'ASC';

$where = ["p.is_deleted=FALSE"];
$params = [];
if ($search !== '') {
    $where[] = "(f.farm_name ILIKE ? OR f.location ILIKE ? OR c.crop_name ILIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($statusId > 0) {
    $where[] = "p.status_id=?";
    $params[] = $statusId;
}
$whereSQL = implode(' AND ', $where);

$total = $pdo->prepare("SELECT COUNT(*) FROM plantings p JOIN farms f ON p.farm_id=f.farm_id JOIN crops c ON p.crop_id=c.crop_id WHERE $whereSQL");
$total->execute($params);
$totalRecords = $total->fetchColumn();
$totalPages = max(1, ceil($totalRecords / $perPage));

$stmt = $pdo->prepare("SELECT p.*,f.farm_name,f.location,c.crop_name,ps.status_name FROM plantings p JOIN farms f ON p.farm_id=f.farm_id JOIN crops c ON p.crop_id=c.crop_id JOIN planting_statuses ps ON p.status_id=ps.status_id WHERE $whereSQL ORDER BY $sort $dir LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$plantings = $stmt->fetchAll();

$statuses = $pdo->query("SELECT * FROM planting_statuses ORDER BY status_name")->fetchAll();

function sortLinkP($col, $label, $sort, $dir)
{
    $newDir = ($sort === $col && $dir === 'ASC') ? 'desc' : 'asc';
    $icon = $sort === $col ? ($dir === 'ASC' ? '↑' : '↓') : '';
    $p = $_GET;
    $p['sort'] = $col;
    $p['dir'] = $newDir;
    $p['page'] = 1;
    return '<a href="?'.http_build_query($p).'" class="text-dark text-decoration-none">'.$label.' <span class="text-muted">'.$icon.'</span></a>';
}

$badgeColors = ['Planted' => 'success','Growing' => 'info','Harvested' => 'primary','Failed' => 'danger','Cancelled' => 'secondary'];

$pageTitle = 'Plantings';
require_once '../includes/header.php';
?>
<?= displayFlash() ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h6 class="mb-0 text-muted">Total: <strong><?= $totalRecords ?></strong> plantings</h6>
  <a href="create.php" class="btn btn-success"><i class="bi bi-plus-lg me-1"></i>Add Planting</a>
</div>
<div class="card mb-3"><div class="card-body">
  <form method="get" class="row g-2 align-items-end">
    <div class="col-md-4"><label class="form-label small mb-1">Search</label><input type="search" name="search" id="searchInput" data-live-search="search_ajax.php" data-live-target="plantingTableBody" class="form-control form-control-sm" placeholder="Farm, location, or crop..." value="<?= htmlspecialchars($search) ?>"></div>
    <div class="col-md-3"><label class="form-label small mb-1">Status</label>
      <select name="status_id" class="form-select form-select-sm"><option value="">All Statuses</option>
        <?php foreach ($statuses as $st): ?><option value="<?= $st['status_id'] ?>" <?= $statusId == $st['status_id'] ? 'selected' : '' ?>><?= htmlspecialchars($st['status_name']) ?></option><?php endforeach; ?>
      </select></div>
    <div class="col-auto"><button type="submit" class="btn btn-sm btn-primary">Filter</button> <a href="index.php" class="btn btn-sm btn-outline-secondary ms-1">Reset</a></div>
    <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
    <input type="hidden" name="dir" value="<?= htmlspecialchars($dir) ?>">
  </form>
</div></div>
<div class="card"><div class="card-body p-0"><div class="table-responsive">
  <table class="table table-hover align-middle mb-0">
    <thead class="table-light"><tr>
      <th><?= sortLinkP('p.planting_id', 'ID', $sort, $dir) ?></th>
      <th>Farm</th><th>Crop</th>
      <th><?= sortLinkP('p.planting_date', 'Planting Date', $sort, $dir) ?></th>
      <th>Area (ha)</th><th>Seed Qty</th>
      <th><?= sortLinkP('p.expected_harvest_date', 'Exp. Harvest', $sort, $dir) ?></th>
      <th>Status</th><th class="text-center">Actions</th>
    </tr></thead>
    <tbody id="plantingTableBody">
    <?php if (empty($plantings)): ?><tr><td colspan="9" class="text-center py-4 text-muted">No plantings found.</td></tr>
    <?php else: foreach ($plantings as $p): $bc = $badgeColors[$p['status_name']] ?? 'secondary'; ?>
    <tr>
      <td class="text-muted small">#<?= $p['planting_id'] ?></td>
      <td class="fw-semibold"><?= htmlspecialchars($p['farm_name']) ?><br><small class="text-muted"><?= htmlspecialchars($p['location']) ?></small></td>
      <td><?= htmlspecialchars($p['crop_name']) ?></td>
      <td><?= htmlspecialchars($p['planting_date'] ?? '—') ?></td>
      <td><?= $p['area_planted'] !== null ? number_format($p['area_planted'], 2) : '—' ?></td>
      <td><?= $p['seed_quantity'] !== null ? number_format($p['seed_quantity'], 2) : '—' ?></td>
      <td><?= htmlspecialchars($p['expected_harvest_date'] ?? '—') ?></td>
      <td><span class="badge bg-<?= $bc ?>"><?= htmlspecialchars($p['status_name']) ?></span></td>
      <td class="text-center">
        <a href="edit.php?id=<?= $p['planting_id'] ?>" class="btn btn-sm btn-outline-warning me-1"><i class="bi bi-pencil"></i></a>
        <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal" data-id="<?= $p['planting_id'] ?>" data-name="<?= htmlspecialchars($p['crop_name'].' @ '.$p['farm_name']) ?>"><i class="bi bi-trash"></i></button>
      </td>
    </tr>
    <?php endforeach;endif; ?>
    </tbody>
  </table>
</div></div></div>
<?php if ($totalPages > 1): ?><nav class="mt-3" data-live-pagination><ul class="pagination pagination-sm justify-content-center">
  <?php for ($i = 1;$i <= $totalPages;$i++): ?><li class="page-item <?= $i === $page ? 'active' : '' ?>"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a></li><?php endfor; ?>
</ul></nav><?php endif; ?>
<div class="modal fade" id="deleteModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header bg-danger text-white"><h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill me-2"></i>Confirm Delete</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">Delete planting record for <strong id="dName"></strong>?</div>
  <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><form method="post" action="delete.php" class="d-inline"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="id" id="deletePlantingId"><button type="submit" class="btn btn-danger">Delete</button></form></div>
</div></div></div>
<script>document.getElementById('deleteModal').addEventListener('show.bs.modal',function(e){var b=e.relatedTarget;document.getElementById('dName').textContent=b.dataset.name;document.getElementById('deletePlantingId').value=b.dataset.id;});</script>
<?php require_once '../includes/footer.php'; ?>
