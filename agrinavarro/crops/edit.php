<?php
require_once __DIR__ . '/../includes/session.php';
dcpStartSession();
require_once '../includes/auth.php';
requireLogin();
require_once '../includes/db.php';

$pdo = getDB();
$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    flashError('Invalid ID.');
    header('Location: index.php');
    exit;
}

$crop = $pdo->prepare("SELECT * FROM crops WHERE crop_id=? AND is_deleted=FALSE");
$crop->execute([$id]);
$crop = $crop->fetch();
if (!$crop) {
    flashError('Crop not found.');
    header('Location: index.php');
    exit;
}

$categories = $pdo->query("SELECT * FROM crop_categories ORDER BY category_name")->fetchAll();
$seasons = $pdo->query("SELECT * FROM seasons ORDER BY season_name")->fetchAll();

$errors = [];
$data = $crop;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['crop_name']          = trim($_POST['crop_name'] ?? '');
    $data['variety']            = trim($_POST['variety'] ?? '');
    $data['category_id']        = (int)($_POST['category_id'] ?? 0);
    $data['growing_period_days'] = trim($_POST['growing_period_days'] ?? '');
    $data['season_id']          = (int)($_POST['season_id'] ?? 0);

    if ($data['crop_name'] === '') {
        $errors[] = 'Crop name is required.';
    }
    if (!$data['category_id']) {
        $errors[] = 'Category is required.';
    }
    if ($data['growing_period_days'] === '' || !is_numeric($data['growing_period_days']) || $data['growing_period_days'] < 0) {
        $errors[] = 'Growing period must be zero or greater.';
    }

    if (empty($errors)) {
        $gpd = (int)$data['growing_period_days'];
        $categoryCheck = $pdo->prepare('SELECT category_id FROM crop_categories WHERE category_id = ?');
        $categoryCheck->execute([$data['category_id']]);
        $seasonIsValid = true;
        if ($data['season_id']) {
            $seasonCheck = $pdo->prepare('SELECT season_id FROM seasons WHERE season_id = ?');
            $seasonCheck->execute([$data['season_id']]);
            $seasonIsValid = $seasonCheck->fetchColumn() !== false;
        }

        if ($categoryCheck->fetchColumn() === false || !$seasonIsValid) {
            $errors[] = 'Choose valid crop category and season values.';
        } else {
            $setClauses = ['crop_name = ?', 'variety = ?'];
            $values = [$data['crop_name'], $data['variety']];
            foreach (['crop_category', 'recommended_season'] as $legacyColumn) {
                if (hasDBColumn('crops', $legacyColumn)) {
                    $setClauses[] = $legacyColumn . ' = ?';
                    $values[] = '';
                }
            }
            $setClauses = array_merge(
                $setClauses,
                ['category_id = ?', 'growing_period_days = ?', 'season_id = ?']
            );
            array_push($values, $data['category_id'], $gpd, $data['season_id'] ?: null, $id);
            $update = $pdo->prepare(
                'UPDATE crops SET ' . implode(', ', $setClauses) . ' WHERE crop_id = ?'
            );
            $update->execute($values);
            $changedFields = describeActivityChanges($crop, $data, [
                'crop_name' => 'crop name',
                'variety' => 'variety',
                'category_id' => 'category',
                'growing_period_days' => 'growing period',
                'season_id' => 'recommended season',
            ]);
            logActivity("Updated crop #$id ({$data['crop_name']}); changed fields: $changedFields.");
            flashSuccess('Crop updated successfully!');
            header('Location: index.php');
            exit;
        }
    }
}

$pageTitle = 'Edit Crop';
require_once '../includes/header.php';
?>
<?= displayFlash() ?>
<div class="mb-3"><a href="index.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a></div>
<div class="card" style="max-width:580px"><div class="card-header bg-warning fw-semibold"><i class="bi bi-pencil-fill me-2"></i>Edit Crop #<?= $id ?></div>
<div class="card-body">
  <?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
  <form method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>"><div class="row g-3">
    <div class="col-md-6"><label class="form-label fw-semibold">Crop Name <span class="text-danger">*</span></label><input type="text" name="crop_name" class="form-control" value="<?= htmlspecialchars($data['crop_name']) ?>" required></div>
    <div class="col-md-6"><label class="form-label fw-semibold">Variety</label><input type="text" name="variety" class="form-control" value="<?= htmlspecialchars($data['variety'] ?? '') ?>"></div>
    <div class="col-md-6"><label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
      <select name="category_id" class="form-select" required><option value="">-- Select Category --</option>
        <?php foreach ($categories as $cat): ?><option value="<?= $cat['category_id'] ?>" <?= $data['category_id'] == $cat['category_id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['category_name']) ?></option><?php endforeach; ?>
      </select></div>
    <div class="col-md-6"><label class="form-label fw-semibold">Growing Period (days)</label><input type="number" min="0" name="growing_period_days" class="form-control" value="<?= htmlspecialchars($data['growing_period_days'] ?? '0') ?>" required></div>
    <div class="col-12"><label class="form-label fw-semibold">Recommended Season</label>
      <select name="season_id" class="form-select"><option value="">-- Select Season --</option>
        <?php foreach ($seasons as $s): ?><option value="<?= $s['season_id'] ?>" <?= $data['season_id'] == $s['season_id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['season_name']) ?></option><?php endforeach; ?>
      </select></div>
    <div class="col-12 d-flex gap-2"><button type="submit" class="btn btn-warning"><i class="bi bi-check-lg me-1"></i>Update Crop</button><a href="index.php" class="btn btn-outline-secondary">Cancel</a></div>
  </div></form>
</div></div>
<?php require_once '../includes/footer.php'; ?>
