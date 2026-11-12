<?php
require_once __DIR__ . '/../includes/session.php';
dcpStartSession();
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/db.php';
$pdo = getDB();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}$csrf = $_SESSION['csrf_token'];
$error = '';
$entities = ['farmers' => ['farmer_id','Farmers',"SELECT farmer_id AS id,first_name || ' ' || last_name AS label,address AS detail FROM farmers WHERE COALESCE(is_deleted,FALSE)=TRUE ORDER BY farmer_id DESC"], 'farms' => ['farm_id','Farms',"SELECT farm_id AS id,farm_name AS label,location AS detail FROM farms WHERE COALESCE(is_deleted,FALSE)=TRUE ORDER BY farm_id DESC"], 'crops' => ['crop_id','Crops',"SELECT crop_id AS id,crop_name AS label,variety AS detail FROM crops WHERE COALESCE(is_deleted,FALSE)=TRUE ORDER BY crop_id DESC"], 'plantings' => ['planting_id','Plantings',"SELECT p.planting_id AS id,c.crop_name || ' — ' || f.farm_name AS label,p.planting_date AS detail FROM plantings p LEFT JOIN crops c ON c.crop_id=p.crop_id LEFT JOIN farms f ON f.farm_id=p.farm_id WHERE COALESCE(p.is_deleted,FALSE)=TRUE ORDER BY p.planting_id DESC"], 'harvests' => ['harvest_id','Harvests',"SELECT h.harvest_id AS id,COALESCE(c.crop_name,'Crop') || ' — ' || COALESCE(f.farm_name,'Farm') AS label,h.harvest_date AS detail FROM harvests h LEFT JOIN plantings p ON p.planting_id=h.planting_id LEFT JOIN crops c ON c.crop_id=p.crop_id LEFT JOIN farms f ON f.farm_id=p.farm_id WHERE COALESCE(h.is_deleted,FALSE)=TRUE ORDER BY h.harvest_id DESC"]];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, $_POST['csrf_token'] ?? '')) {
        $error = 'Your form session expired. Reload and try again.';
    } else {
        $entity = $_POST['entity'] ?? '';
        $action = $_POST['action'] ?? 'restore';
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0 && isset($entities[$entity])) {
            [$key] = $entities[$entity];
            if ($action === 'permanent_delete') {
                $dependencies = [
                    'farmers' => [['farms', 'farmer_id']],
                    'farms' => [['plantings', 'farm_id']],
                    'crops' => [['plantings', 'crop_id']],
                    'plantings' => [['harvests', 'planting_id']],
                ];
                $hasChildren = false;
                foreach ($dependencies[$entity] ?? [] as [$childTable, $foreignKey]) {
                    $check = $pdo->prepare("SELECT EXISTS (SELECT 1 FROM $childTable WHERE $foreignKey = ?)");
                    $check->execute([$id]);
                    $hasChildren = $hasChildren || (bool)$check->fetchColumn();
                }
                if ($hasChildren) {
                    $error = 'This archived record is still referenced by other records. Remove those records first.';
                } else {
                    $stmt = $pdo->prepare("DELETE FROM $entity WHERE $key = ? AND COALESCE(is_deleted, FALSE) = TRUE");
                    $stmt->execute([$id]);
                    if ($stmt->rowCount()) {
                        logActivity("Permanently deleted archived $entity record #$id.");
                        flashSuccess('Archived record permanently deleted.');
                        header('Location: archived.php');
                        exit;
                    }
                    $error = 'Archived record was not found.';
                }
            } else {
                $stmt = $pdo->prepare("UPDATE $entity SET is_deleted=FALSE WHERE $key=? AND COALESCE(is_deleted,FALSE)=TRUE");
                $stmt->execute([$id]);
                if ($stmt->rowCount()) {
                    logActivity("Restored archived $entity record #$id.");
                    flashSuccess('Record restored.');
                    header('Location: archived.php');
                    exit;
                }
                $error = 'Archived record was not found.';
            }
        } else {
            $error = 'Choose a valid archived record.';
        }
    }
}
$archived = [];
foreach ($entities as $table => $meta) {
    $archived[$table] = $pdo->query($meta[2])->fetchAll();
}$pageTitle = 'Archived records';
require_once '../includes/header.php';
?>
<?= displayFlash() ?><?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?><div class="dashboard-heading"><div><h1>Archived records</h1><p>Deleted records are kept here and can be restored.</p></div></div>
<?php foreach ($entities as $table => $meta): ?><div class="card mb-3"><div class="card-header bg-white d-flex justify-content-between"><span class="fw-semibold"><?= htmlspecialchars($meta[1]) ?></span><span class="badge text-bg-light"><?= count($archived[$table]) ?></span></div><div class="card-body p-0 table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>ID</th><th>Record</th><th>Details</th><th>Action</th></tr></thead><tbody><?php if (!$archived[$table]): ?><tr><td colspan="4" class="text-center text-muted py-3">No archived <?= strtolower($meta[1]) ?>.</td></tr><?php else: foreach ($archived[$table] as $record): ?><tr><td>#<?= (int)$record['id'] ?></td><td class="fw-semibold"><?= htmlspecialchars($record['label'] ?? '—') ?></td><td><?= htmlspecialchars((string)($record['detail'] ?? '—')) ?></td><td class="d-flex gap-2"><form method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>"><input type="hidden" name="entity" value="<?= htmlspecialchars($table) ?>"><input type="hidden" name="id" value="<?= (int)$record['id'] ?>"><input type="hidden" name="action" value="restore"><button class="btn btn-sm btn-outline-primary" type="submit"><i class="bi bi-arrow-counterclockwise me-1"></i>Restore</button></form><form method="post" onsubmit="return confirm('Permanently delete this archived record? This cannot be undone.')"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>"><input type="hidden" name="entity" value="<?= htmlspecialchars($table) ?>"><input type="hidden" name="id" value="<?= (int)$record['id'] ?>"><input type="hidden" name="action" value="permanent_delete"><button class="btn btn-sm btn-danger" type="submit"><i class="bi bi-trash me-1"></i>Delete forever</button></form></td></tr><?php endforeach; endif; ?></tbody></table></div></div><?php endforeach; ?>
<?php require_once '../includes/footer.php'; ?>
