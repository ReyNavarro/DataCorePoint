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

$total = $pdo->prepare("SELECT COUNT(*) FROM farmers WHERE $whereSQL");
$total->execute($params);
$totalRecords = $total->fetchColumn();
$totalPages = max(1, ceil($totalRecords / $perPage));

$stmt = $pdo->prepare("SELECT * FROM farmers WHERE $whereSQL ORDER BY $sort $dir LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$farmers = $stmt->fetchAll();

$addresses = $pdo->query("SELECT DISTINCT address FROM farmers WHERE is_deleted = FALSE AND address IS NOT NULL AND address <> '' ORDER BY address")->fetchAll(PDO::FETCH_COLUMN);

function sortLink($col, $label, $sort, $dir)
{
    $newDir = ($sort === $col && $dir === 'ASC') ? 'desc' : 'asc';
    $icon = $sort === $col ? ($dir === 'ASC' ? '↑' : '↓') : '';
    $params = $_GET;
    $params['sort'] = $col;
    $params['dir'] = $newDir;
    $params['page'] = 1;
    return '<a href="?' . http_build_query($params) . '" class="text-dark text-decoration-none">' . $label . ' <span class="text-muted">' . $icon . '</span></a>';
}

$pageTitle = 'Farmers';
require_once '../includes/header.php';
?>
<?= displayFlash() ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h6 class="mb-0 text-muted">Total: <strong><?= $totalRecords ?></strong> farmers</h6>
  <a href="create.php" class="btn btn-success"><i class="bi bi-plus-lg me-1"></i>Add Farmer</a>
</div>

<!-- Search & Filter -->
<div class="card mb-3">
  <div class="card-body">
    <form method="get" class="row g-2 align-items-end" id="filterForm">
      <div class="col-md-4">
        <label class="form-label small mb-1">Search</label>
        <input type="search" name="search" class="form-control form-control-sm" placeholder="Name or contact..." value="<?= htmlspecialchars($search) ?>" id="searchInput" data-live-search="search_ajax.php" data-live-target="farmerTableBody">
      </div>
      <div class="col-md-3">
        <label class="form-label small mb-1">Filter by Address</label>
        <select name="address" class="form-select form-select-sm">
          <option value="">All Addresses</option>
          <?php foreach ($addresses as $addr): ?>
          <option value="<?= htmlspecialchars($addr) ?>" <?= $address === $addr ? 'selected' : '' ?>><?= htmlspecialchars($addr) ?></option>
          <?php endforeach; ?>
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
            <th><?= sortLink('farmer_id', 'ID', $sort, $dir) ?></th>
            <th><?= sortLink('first_name', 'Full Name', $sort, $dir) ?></th>
            <th>Contact</th>
            <th>Address</th>
            <th class="text-center">Actions</th>
          </tr>
        </thead>
        <tbody id="farmerTableBody">
        <?php if (empty($farmers)): ?>
        <tr><td colspan="5" class="text-center py-4 text-muted">No farmers found.</td></tr>
        <?php else: foreach ($farmers as $f): ?>
        <tr>
          <td class="text-muted small">#<?= $f['farmer_id'] ?></td>
          <td class="fw-semibold"><?= htmlspecialchars($f['first_name'] . ' ' . $f['last_name']) ?></td>
          <td><?= htmlspecialchars($f['contact_number'] ?? '—') ?></td>
          <td><?= htmlspecialchars($f['address'] ?? '—') ?></td>
          <td class="text-center">
            <button class="btn btn-sm btn-outline-info me-1" data-bs-toggle="modal" data-bs-target="#viewModal"
              data-id="<?= $f['farmer_id'] ?>"
              data-name="<?= htmlspecialchars($f['first_name'].' '.$f['last_name']) ?>"
              data-contact="<?= htmlspecialchars($f['contact_number'] ?? '') ?>"
              data-address="<?= htmlspecialchars($f['address'] ?? '') ?>"
              <i class="bi bi-eye"></i>
            </button>
            <a href="edit.php?id=<?= $f['farmer_id'] ?>" class="btn btn-sm btn-outline-warning me-1"><i class="bi bi-pencil"></i></a>
            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal" data-id="<?= $f['farmer_id'] ?>" data-name="<?= htmlspecialchars($f['first_name'].' '.$f['last_name']) ?>">
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

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<nav class="mt-3" data-live-pagination>
  <ul class="pagination pagination-sm justify-content-center">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
      <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
    </li>
    <?php endfor; ?>
  </ul>
</nav>
<?php endif; ?>

<!-- View Modal -->
<div class="modal fade" id="viewModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title"><i class="bi bi-person-circle me-2"></i>Farmer Details</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <table class="table table-bordered table-sm">
          <tr><th>ID</th><td id="vId"></td></tr>
          <tr><th>Full Name</th><td id="vName"></td></tr>
          <tr><th>Contact</th><td id="vContact"></td></tr>
          <tr><th>Address</th><td id="vAddress"></td></tr>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill me-2"></i>Confirm Delete</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">Are you sure you want to delete <strong id="dName"></strong>?</div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <a id="deleteBtn" href="#" class="btn btn-danger">Delete</a>
      </div>
    </div>
  </div>
</div>

<script>
var viewModal = document.getElementById('viewModal');
viewModal.addEventListener('show.bs.modal', function(e) {
  var btn = e.relatedTarget;
  document.getElementById('vId').textContent = btn.dataset.id;
  document.getElementById('vName').textContent = btn.dataset.name;
  document.getElementById('vContact').textContent = btn.dataset.contact || '—';
  document.getElementById('vAddress').textContent = btn.dataset.address || '—';
});
var delModal = document.getElementById('deleteModal');
delModal.addEventListener('show.bs.modal', function(e) {
  var btn = e.relatedTarget;
  document.getElementById('dName').textContent = btn.dataset.name;
  document.getElementById('deleteBtn').href = 'delete.php?id=' + btn.dataset.id;
});
</script>
<?php require_once '../includes/footer.php'; ?>
