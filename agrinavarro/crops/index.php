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

$search  = trim($_GET['search'] ?? '');
$catId   = (int)($_GET['category_id'] ?? 0);
$seaId   = (int)($_GET['season_id'] ?? 0);
$sort    = in_array($_GET['sort'] ?? '', ['c.crop_id','c.crop_name','c.growing_period_days']) ? $_GET['sort'] : 'c.crop_id';
$dir     = (($_GET['dir'] ?? 'asc') === 'desc') ? 'DESC' : 'ASC';

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

$total = $pdo->prepare("SELECT COUNT(*) FROM crops c WHERE $whereSQL");
$total->execute($params);
$totalRecords = $total->fetchColumn();
$totalPages = max(1, ceil($totalRecords / $perPage));

$stmt = $pdo->prepare("SELECT c.*,cc.category_name,s.season_name FROM crops c LEFT JOIN crop_categories cc ON c.category_id=cc.category_id LEFT JOIN seasons s ON c.season_id=s.season_id WHERE $whereSQL ORDER BY $sort $dir LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$crops = $stmt->fetchAll();

$categories = $pdo->query("SELECT * FROM crop_categories ORDER BY category_name")->fetchAll();
$seasons = $pdo->query("SELECT * FROM seasons ORDER BY season_name")->fetchAll();

function sortLinkC($col, $label, $sort, $dir)
{
    $newDir = ($sort === $col && $dir === 'ASC') ? 'desc' : 'asc';
    $icon = $sort === $col ? ($dir === 'ASC' ? '↑' : '↓') : '';
    $p = $_GET;
    $p['sort'] = $col;
    $p['dir'] = $newDir;
    $p['page'] = 1;
    return '<a href="?'.http_build_query($p).'" class="text-dark text-decoration-none">'.$label.' <span class="text-muted">'.$icon.'</span></a>';
}

$pageTitle = 'Crops';
require_once '../includes/header.php';
?>
<?= displayFlash() ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h6 class="mb-0 text-muted">Total: <strong><?= $totalRecords ?></strong> crops</h6>
  <a href="create.php" class="btn btn-success"><i class="bi bi-plus-lg me-1"></i>Add Crop</a>
</div>
<div class="card mb-3"><div class="card-body">
  <form method="get" class="row g-2 align-items-end">
    <div class="col-md-3"><label class="form-label small mb-1">Search</label><input type="search" name="search" id="searchInput" data-live-search="search_ajax.php" data-live-target="cropTableBody" class="form-control form-control-sm" placeholder="Crop name or variety..." value="<?= htmlspecialchars($search) ?>"></div>
    <div class="col-md-2"><label class="form-label small mb-1">Category</label>
      <select name="category_id" class="form-select form-select-sm"><option value="">All Categories</option>
        <?php foreach ($categories as $cat): ?><option value="<?= $cat['category_id'] ?>" <?= $catId == $cat['category_id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['category_name']) ?></option><?php endforeach; ?>
      </select></div>
    <div class="col-md-2"><label class="form-label small mb-1">Season</label>
      <select name="season_id" class="form-select form-select-sm"><option value="">All Seasons</option>
        <?php foreach ($seasons as $s): ?><option value="<?= $s['season_id'] ?>" <?= $seaId == $s['season_id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['season_name']) ?></option><?php endforeach; ?>
      </select></div>
    <div class="col-auto"><button type="submit" class="btn btn-sm btn-primary">Filter</button> <a href="index.php" class="btn btn-sm btn-outline-secondary ms-1">Reset</a></div>
    <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
    <input type="hidden" name="dir" value="<?= htmlspecialchars($dir) ?>">
  </form>
</div></div>
<div class="card"><div class="card-body p-0"><div class="table-responsive">
  <table class="table table-hover align-middle mb-0">
    <thead class="table-light"><tr>
      <th><?= sortLinkC('c.crop_id', 'ID', $sort, $dir) ?></th>
      <th><?= sortLinkC('c.crop_name', 'Crop Name', $sort, $dir) ?></th>
      <th>Variety</th><th>Category</th>
      <th><?= sortLinkC('c.growing_period_days', 'Growing Period (days)', $sort, $dir) ?></th>
      <th>Season</th><th class="text-center">Actions</th>
    </tr></thead>
    <tbody id="cropTableBody">
    <?php if (empty($crops)): ?><tr><td colspan="7" class="text-center py-4 text-muted">No crops found.</td></tr>
    <?php else: foreach ($crops as $c): ?>
    <tr>
      <td class="text-muted small">#<?= $c['crop_id'] ?></td>
      <td class="fw-semibold"><?= htmlspecialchars($c['crop_name']) ?></td>
      <td><?= htmlspecialchars($c['variety'] ?? '—') ?></td>
      <td><span class="badge bg-warning text-dark"><?= htmlspecialchars($c['category_name'] ?? '—') ?></span></td>
      <td><?= $c['growing_period_days'] !== null ? $c['growing_period_days'].' days' : '—' ?></td>
      <td><?= htmlspecialchars($c['season_name'] ?? '—') ?></td>
      <td class="text-center">
        <a href="edit.php?id=<?= $c['crop_id'] ?>" class="btn btn-sm btn-outline-warning me-1"><i class="bi bi-pencil"></i></a>
        <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal" data-id="<?= $c['crop_id'] ?>" data-name="<?= htmlspecialchars($c['crop_name']) ?>"><i class="bi bi-trash"></i></button>
      </td>
    </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div></div></div>
<?php if ($totalPages > 1): ?><nav class="mt-3" data-live-pagination><ul class="pagination pagination-sm justify-content-center">
  <?php for ($i = 1;$i <= $totalPages;$i++): ?><li class="page-item <?= $i === $page ? 'active' : '' ?>"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a></li><?php endfor; ?>
</ul></nav><?php endif; ?>
<div class="modal fade" id="deleteModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header bg-danger text-white"><h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill me-2"></i>Confirm Delete</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">Delete crop <strong id="dName"></strong>?</div>
  <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><a id="deleteBtn" href="#" class="btn btn-danger">Delete</a></div>
</div></div></div>
<script>document.getElementById('deleteModal').addEventListener('show.bs.modal',function(e){var b=e.relatedTarget;document.getElementById('dName').textContent=b.dataset.name;document.getElementById('deleteBtn').href='delete.php?id='+b.dataset.id;});</script>
<?php require_once '../includes/footer.php'; ?>
