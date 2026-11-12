<?php
require_once __DIR__ . '/../includes/session.php';
dcpStartSession();
require_once '../includes/auth.php';
requireLogin();
require_once '../includes/db.php';

$pdo = getDB();
$farms = $pdo->query("SELECT farm_id,farm_name,location FROM farms WHERE is_deleted=FALSE ORDER BY farm_name")->fetchAll();
$crops = $pdo->query("SELECT crop_id,crop_name,variety FROM crops WHERE is_deleted=FALSE ORDER BY crop_name")->fetchAll();
$statuses = $pdo->query("SELECT * FROM planting_statuses ORDER BY status_name")->fetchAll();

$errors = [];
$data = ['farm_id' => '','crop_id' => '','planting_date' => date('Y-m-d'),'area_planted' => '','seed_quantity' => '','expected_harvest_date' => '','status_id' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['farm_id']              = (int)($_POST['farm_id'] ?? 0);
    $data['crop_id']              = (int)($_POST['crop_id'] ?? 0);
    $data['planting_date']        = trim($_POST['planting_date'] ?? '');
    $data['area_planted']         = trim($_POST['area_planted'] ?? '');
    $data['seed_quantity']        = trim($_POST['seed_quantity'] ?? '');
    $data['expected_harvest_date'] = trim($_POST['expected_harvest_date'] ?? '');
    $data['status_id']            = (int)($_POST['status_id'] ?? 0);

    if (!$data['farm_id']) {
        $errors[] = 'Please select a farm.';
    }
    if (!$data['crop_id']) {
        $errors[] = 'Please select a crop.';
    }
    if ($data['planting_date'] === '') {
        $errors[] = 'Planting date is required.';
    }
    if (!$data['status_id']) {
        $errors[] = 'Please select a status.';
    }
    if ($data['area_planted'] === '' || !is_numeric($data['area_planted']) || $data['area_planted'] < 0) {
        $errors[] = 'Enter a valid planted area.';
    }
    if ($data['seed_quantity'] === '' || !is_numeric($data['seed_quantity']) || $data['seed_quantity'] < 0) {
        $errors[] = 'Enter a valid seed quantity.';
    }
    if ($data['expected_harvest_date'] === '') {
        $errors[] = 'Expected harvest date is required.';
    }
    if ($data['expected_harvest_date'] !== '' && $data['planting_date'] !== '' && $data['expected_harvest_date'] < $data['planting_date']) {
        $errors[] = 'Expected harvest date must be on or after planting date.';
    }

    if (empty($errors)) {
        $statusCheck = $pdo->prepare('SELECT status_id FROM planting_statuses WHERE status_id = ?');
        $statusCheck->execute([$data['status_id']]);
        if ($statusCheck->fetchColumn() === false) {
            $errors[] = 'Choose a valid planting status.';
        } else {
            $columns = [
                'farm_id', 'crop_id', 'planting_date', 'area_planted',
                'seed_quantity', 'expected_harvest_date',
            ];
            $values = [
                $data['farm_id'], $data['crop_id'], $data['planting_date'],
                (float)$data['area_planted'], (float)$data['seed_quantity'],
                $data['expected_harvest_date'],
            ];
            if (hasDBColumn('plantings', 'status')) {
                $columns[] = 'status';
                $values[] = '';
            }
            $columns[] = 'status_id';
            $columns[] = 'is_deleted';
            $placeholders = array_fill(0, count($columns) - 1, '?');
            $placeholders[] = 'FALSE';

            $insert = $pdo->prepare(
                'INSERT INTO plantings (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')'
            );
            $values[] = $data['status_id'];
            $insert->execute($values);
            $plantingId = (int)$pdo->lastInsertId('plantings_planting_id_seq');
            logActivity("Created planting #$plantingId (farm #{$data['farm_id']}, crop #{$data['crop_id']}).");
            flashSuccess('Planting record added successfully!');
            header('Location: index.php');
            exit;
        }
    }
}

$pageTitle = 'Add Planting';
require_once '../includes/header.php';
?>
<?= displayFlash() ?>
<div class="mb-3"><a href="index.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a></div>
<div class="card" style="max-width:650px"><div class="card-header bg-success text-white fw-semibold"><i class="bi bi-calendar2-week-fill me-2"></i>Add Planting Record</div>
<div class="card-body">
  <?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
  <form method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>"><div class="row g-3">
    <div class="col-md-6"><label class="form-label fw-semibold">Farm <span class="text-danger">*</span></label>
      <select name="farm_id" class="form-select" required><option value="">-- Select Farm --</option>
        <?php foreach ($farms as $f): ?><option value="<?= $f['farm_id'] ?>" <?= $data['farm_id'] == $f['farm_id'] ? 'selected' : '' ?>><?= htmlspecialchars($f['farm_name'].' ('.$f['location'].')') ?></option><?php endforeach; ?>
      </select></div>
    <div class="col-md-6"><label class="form-label fw-semibold">Crop <span class="text-danger">*</span></label>
      <select name="crop_id" class="form-select" required><option value="">-- Select Crop --</option>
        <?php foreach ($crops as $c): ?><option value="<?= $c['crop_id'] ?>" <?= $data['crop_id'] == $c['crop_id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['crop_name'].($c['variety'] ? ' ('.$c['variety'].')' : '')) ?></option><?php endforeach; ?>
      </select></div>
    <div class="col-md-6"><label class="form-label fw-semibold">Planting Date <span class="text-danger">*</span></label><input type="date" name="planting_date" class="form-control" value="<?= htmlspecialchars($data['planting_date']) ?>" required id="plantingDate"></div>
    <div class="col-md-6"><label class="form-label fw-semibold">Expected Harvest Date</label><input type="date" name="expected_harvest_date" class="form-control" value="<?= htmlspecialchars($data['expected_harvest_date']) ?>" id="harvestDate" required></div>
    <div class="col-md-6"><label class="form-label fw-semibold">Area Planted (ha)</label><input type="number" step="0.01" min="0" name="area_planted" class="form-control" value="<?= htmlspecialchars($data['area_planted']) ?>" required></div>
    <div class="col-md-6"><label class="form-label fw-semibold">Seed Quantity (kg)</label><input type="number" step="0.01" min="0" name="seed_quantity" class="form-control" value="<?= htmlspecialchars($data['seed_quantity']) ?>" required></div>
    <div class="col-12"><label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
      <select name="status_id" class="form-select" required><option value="">-- Select Status --</option>
        <?php foreach ($statuses as $st): ?><option value="<?= $st['status_id'] ?>" <?= $data['status_id'] == $st['status_id'] ? 'selected' : '' ?>><?= htmlspecialchars($st['status_name']) ?></option><?php endforeach; ?>
      </select></div>
    <div class="col-12 d-flex gap-2"><button type="submit" class="btn btn-success"><i class="bi bi-check-lg me-1"></i>Save Planting</button><a href="index.php" class="btn btn-outline-secondary">Cancel</a></div>
  </div></form>
</div></div>
<script>
document.getElementById('plantingDate').addEventListener('change',function(){
  var hd=document.getElementById('harvestDate');
  if(hd.value&&hd.value<this.value) hd.value=this.value;
  hd.min=this.value;
});
</script>
<?php require_once '../includes/footer.php'; ?>
