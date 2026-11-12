<?php
require_once __DIR__ . '/../includes/session.php';
dcpStartSession();
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/db.php';

$pdo = getDB();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];
$error = '';
$entities = [
    'farmers' => ['farmer_id', 'Farmers', "SELECT farmer_id AS id, first_name || ' ' || last_name AS label, address AS detail FROM farmers WHERE COALESCE(is_deleted,FALSE)=TRUE ORDER BY farmer_id DESC"],
    'farms' => ['farm_id', 'Farms', "SELECT farm_id AS id, farm_name AS label, location AS detail FROM farms WHERE COALESCE(is_deleted,FALSE)=TRUE ORDER BY farm_id DESC"],
    'crops' => ['crop_id', 'Crops', "SELECT crop_id AS id, crop_name AS label, variety AS detail FROM crops WHERE COALESCE(is_deleted,FALSE)=TRUE ORDER BY crop_id DESC"],
    'plantings' => ['planting_id', 'Plantings', "SELECT p.planting_id AS id, c.crop_name || ' — ' || f.farm_name AS label, p.planting_date AS detail FROM plantings p LEFT JOIN crops c ON c.crop_id=p.crop_id LEFT JOIN farms f ON f.farm_id=p.farm_id WHERE COALESCE(p.is_deleted,FALSE)=TRUE ORDER BY p.planting_id DESC"],
    'harvests' => ['harvest_id', 'Harvests', "SELECT h.harvest_id AS id, COALESCE(c.crop_name,'Crop') || ' — ' || COALESCE(f.farm_name,'Farm') AS label, h.harvest_date AS detail FROM harvests h LEFT JOIN plantings p ON p.planting_id=h.planting_id LEFT JOIN crops c ON c.crop_id=p.crop_id LEFT JOIN farms f ON f.farm_id=p.farm_id WHERE COALESCE(h.is_deleted,FALSE)=TRUE ORDER BY h.harvest_id DESC"],
];

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
                    $error = 'This record is still linked to other records. Permanently delete its dependent records first.';
                } else {
                    $delete = $pdo->prepare("DELETE FROM $entity WHERE $key = ? AND COALESCE(is_deleted,FALSE)=TRUE");
                    $delete->execute([$id]);
                    if ($delete->rowCount()) {
                        logActivity("Permanently deleted archived $entity record #$id.");
                        flashSuccess('Archived record permanently deleted.');
                        header('Location: archived.php');
                        exit;
                    }
                    $error = 'Archived record was not found.';
                }
            } elseif ($action === 'restore') {
                $restore = $pdo->prepare("UPDATE $entity SET is_deleted=FALSE WHERE $key=? AND COALESCE(is_deleted,FALSE)=TRUE");
                $restore->execute([$id]);
                if ($restore->rowCount()) {
                    logActivity("Restored archived $entity record #$id.");
                    flashSuccess('Record restored.');
                    header('Location: archived.php');
                    exit;
                }
                $error = 'Archived record was not found.';
            } else {
                $error = 'Choose a valid action.';
            }
        } else {
            $error = 'Choose a valid archived record.';
        }
    }
}

$archived = [];
foreach ($entities as $table => $metadata) {
    $archived[$table] = $pdo->query($metadata[2])->fetchAll();
}
$pageTitle = 'Archived records';
require_once '../includes/header.php';
?>
<?= displayFlash() ?>
<?php if ($error !== ''): ?><div class="alert alert-danger" role="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<div class="dashboard-heading"><div><h1>Archived records</h1><p>Deleted records are kept here and can be restored.</p></div></div>

<?php foreach ($entities as $table => $metadata): ?>
    <div class="card mb-3">
        <div class="card-header bg-white d-flex justify-content-between"><span class="fw-semibold"><?= htmlspecialchars($metadata[1]) ?></span><span class="badge text-bg-light"><?= count($archived[$table]) ?></span></div>
        <div class="card-body p-0 table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>ID</th><th>Record</th><th>Details</th><th>Action</th></tr></thead>
                <tbody>
                <?php if (!$archived[$table]): ?>
                    <tr><td colspan="4" class="text-center text-muted py-3">No archived <?= htmlspecialchars(strtolower($metadata[1])) ?>.</td></tr>
                <?php else: foreach ($archived[$table] as $record): ?>
                    <tr>
                        <td>#<?= (int)$record['id'] ?></td>
                        <td class="fw-semibold"><?= htmlspecialchars($record['label'] ?? '—') ?></td>
                        <td><?= htmlspecialchars((string)($record['detail'] ?? '—')) ?></td>
                        <td><div class="d-flex gap-2">
                            <form method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="entity" value="<?= htmlspecialchars($table, ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="id" value="<?= (int)$record['id'] ?>"><input type="hidden" name="action" value="restore"><button class="btn btn-sm btn-outline-primary" type="submit"><i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i>Restore</button></form>
                            <button class="btn btn-sm btn-outline-danger" type="button" data-bs-toggle="modal" data-bs-target="#permanentDeleteModal" data-entity="<?= htmlspecialchars($table, ENT_QUOTES, 'UTF-8') ?>" data-id="<?= (int)$record['id'] ?>" data-name="<?= htmlspecialchars($record['label'] ?? 'this record', ENT_QUOTES, 'UTF-8') ?>"><i class="bi bi-trash me-1" aria-hidden="true"></i>Delete forever</button>
                        </div></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endforeach; ?>

<div class="modal fade" id="permanentDeleteModal" tabindex="-1" aria-labelledby="permanentDeleteTitle" aria-hidden="true">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header bg-danger text-white"><h2 class="modal-title fs-5" id="permanentDeleteTitle"><i class="bi bi-exclamation-triangle-fill me-2" aria-hidden="true"></i>Confirm Delete</h2><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button></div>
        <div class="modal-body">Permanently delete <strong id="permanentDeleteName"></strong>? This cannot be undone.</div>
        <div class="modal-footer"><button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancel</button><form method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="action" value="permanent_delete"><input type="hidden" name="entity" id="permanentDeleteEntity"><input type="hidden" name="id" id="permanentDeleteId"><button class="btn btn-danger" type="submit">Delete forever</button></form></div>
    </div></div>
</div>
<script>document.getElementById('permanentDeleteModal').addEventListener('show.bs.modal',function(event){var button=event.relatedTarget;document.getElementById('permanentDeleteName').textContent=button.dataset.name;document.getElementById('permanentDeleteEntity').value=button.dataset.entity;document.getElementById('permanentDeleteId').value=button.dataset.id;});</script>
<?php require_once '../includes/footer.php'; ?>
