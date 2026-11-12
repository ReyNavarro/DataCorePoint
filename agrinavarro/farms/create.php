<?php
require_once __DIR__ . '/../includes/session.php';
dcpStartSession();
require_once '../includes/auth.php';
requireLogin();
require_once '../includes/db.php';

$pdo = getDB();
$farmers   = $pdo->query("SELECT farmer_id, first_name, last_name FROM farmers WHERE is_deleted=FALSE ORDER BY last_name, first_name")->fetchAll();
$soilTypes = $pdo->query("SELECT * FROM soil_types ORDER BY soil_name")->fetchAll();
$irrTypes  = $pdo->query("SELECT * FROM irrigation_types ORDER BY irrigation_name")->fetchAll();
$farmSuggestions = $pdo->query('SELECT DISTINCT farm_name FROM farms WHERE COALESCE(is_deleted, FALSE) = FALSE ORDER BY farm_name LIMIT 100')->fetchAll(PDO::FETCH_COLUMN);
$locationSuggestions = $pdo->query('SELECT DISTINCT location FROM farms WHERE COALESCE(is_deleted, FALSE) = FALSE AND location IS NOT NULL ORDER BY location LIMIT 100')->fetchAll(PDO::FETCH_COLUMN);

$errors = [];
$data = ['farmer_id' => '','farm_name' => '','location' => '','area_hectares' => '','soil_id' => '','irrigation_id' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['farmer_id']      = (int)($_POST['farmer_id'] ?? 0);
    $data['farm_name']      = trim($_POST['farm_name'] ?? '');
    $data['location']       = trim($_POST['location'] ?? '');
    $data['area_hectares']  = trim($_POST['area_hectares'] ?? '');
    $data['soil_id']        = (int)($_POST['soil_id'] ?? 0);
    $data['irrigation_id']  = (int)($_POST['irrigation_id'] ?? 0);

    if (!$data['farmer_id']) {
        $errors[] = 'Please select a farmer.';
    }
    if ($data['farm_name'] === '') {
        $errors[] = 'Farm name is required.';
    }
    if ($data['location'] === '') {
        $errors[] = 'Location is required.';
    }
    if ($data['area_hectares'] !== '' && (!is_numeric($data['area_hectares']) || $data['area_hectares'] < 0)) {
        $errors[] = 'Area must be a positive number.';
    }

    if (empty($errors)) {
        $soilCheck = $pdo->prepare('SELECT soil_id FROM soil_types WHERE soil_id = ?');
        $soilCheck->execute([$data['soil_id']]);
        $irrigationCheck = $pdo->prepare('SELECT irrigation_id FROM irrigation_types WHERE irrigation_id = ?');
        $irrigationCheck->execute([$data['irrigation_id']]);

        if ($soilCheck->fetchColumn() === false || $irrigationCheck->fetchColumn() === false) {
            $errors[] = 'Choose valid soil and irrigation types.';
        } else {
            $columns = ['farmer_id', 'farm_name', 'location', 'area_hectares'];
            $values = [
                $data['farmer_id'], $data['farm_name'], $data['location'],
                (float)$data['area_hectares'],
            ];
            foreach (['soil_type', 'irrigation_type'] as $legacyColumn) {
                if (hasDBColumn('farms', $legacyColumn)) {
                    $columns[] = $legacyColumn;
                    $values[] = '';
                }
            }
            $columns[] = 'soil_id';
            $columns[] = 'irrigation_id';
            $columns[] = 'is_deleted';
            $placeholders = array_fill(0, count($columns) - 1, '?');
            $placeholders[] = 'FALSE';

            $insert = $pdo->prepare(
                'INSERT INTO farms (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')'
            );
            $values[] = $data['soil_id'];
            $values[] = $data['irrigation_id'];
            $insert->execute($values);
            logActivity("Added farm: {$data['farm_name']}");
            flashSuccess('Farm added successfully!');
            header('Location: index.php');
            exit;
        }
    }
}

$pageTitle = 'Add Farm';
require_once '../includes/header.php';
?>
<?= displayFlash() ?>
<div class="mb-3"><a href="index.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a></div>
<div class="card" style="max-width:650px">
  <div class="card-header bg-success text-white fw-semibold"><i class="bi bi-geo-alt-fill me-2"></i>Add New Farm</div>
  <div class="card-body">
    <?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
      <div class="row g-3">
        <div class="col-12">
          <label class="form-label fw-semibold">Farmer <span class="text-danger">*</span></label>
          <select name="farmer_id" class="form-select" required>
            <option value="">-- Select Farmer --</option>
            <?php foreach ($farmers as $fa): ?>
            <option value="<?= $fa['farmer_id'] ?>" <?= $data['farmer_id'] == $fa['farmer_id'] ? 'selected' : '' ?>><?= htmlspecialchars($fa['last_name'].', '.$fa['first_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Farm Name <span class="text-danger">*</span></label>
          <input type="text" name="farm_name" list="farm-name-suggestions" class="form-control" value="<?= htmlspecialchars($data['farm_name']) ?>" required>
          <datalist id="farm-name-suggestions"><?php foreach ($farmSuggestions as $suggestion): ?><option value="<?= htmlspecialchars($suggestion, ENT_QUOTES, 'UTF-8') ?>"><?php endforeach; ?></datalist>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Location <span class="text-danger">*</span></label>
          <input type="text" name="location" list="farm-location-suggestions" class="form-control" value="<?= htmlspecialchars($data['location']) ?>" required>
          <datalist id="farm-location-suggestions"><?php foreach ($locationSuggestions as $suggestion): ?><option value="<?= htmlspecialchars($suggestion, ENT_QUOTES, 'UTF-8') ?>"><?php endforeach; ?></datalist>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Area (hectares)</label>
          <input type="number" step="0.01" min="0" name="area_hectares" class="form-control" required value="<?= htmlspecialchars($data['area_hectares']) ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Soil Type</label>
          <select name="soil_id" class="form-select" required>
            <option value="">-- Select Soil --</option>
            <?php foreach ($soilTypes as $s): ?><option value="<?= $s['soil_id'] ?>" <?= $data['soil_id'] == $s['soil_id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['soil_name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Irrigation Type</label>
          <select name="irrigation_id" class="form-select" required>
            <option value="">-- Select Irrigation --</option>
            <?php foreach ($irrTypes as $i): ?><option value="<?= $i['irrigation_id'] ?>" <?= $data['irrigation_id'] == $i['irrigation_id'] ? 'selected' : '' ?>><?= htmlspecialchars($i['irrigation_name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="col-12 d-flex gap-2">
          <button type="submit" class="btn btn-success"><i class="bi bi-check-lg me-1"></i>Save Farm</button>
          <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
        </div>
      </div>
    </form>
  </div>
</div>
<?php require_once '../includes/footer.php'; ?>
