<?php
require_once __DIR__ . '/../includes/session.php';
dcpStartSession();

require_once '../includes/auth.php';
requireLogin();
require_once '../includes/db.php';

$pdo = getDB();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// The dropdowns send lookup IDs. The joined labels are displayed to the user.
$plantings = $pdo->query(
    'SELECT p.planting_id, f.farm_name, c.crop_name
     FROM plantings p
     JOIN farms f ON f.farm_id = p.farm_id
     JOIN crops c ON c.crop_id = p.crop_id
     WHERE COALESCE(p.is_deleted, FALSE) = FALSE
     ORDER BY p.planting_id DESC'
)->fetchAll();
$grades = $pdo->query(
    'SELECT grade_id, grade_name FROM quality_grades ORDER BY grade_name'
)->fetchAll();
$remarks = $pdo->query(
    'SELECT remark_id, remark_description FROM harvest_remarks ORDER BY remark_description'
)->fetchAll();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = 'Your form session expired. Reload the page and try again.';
    }

    $plantingId = (int)($_POST['planting_id'] ?? 0);
    $harvestDate = trim($_POST['harvest_date'] ?? '');
    $quantityKg = trim($_POST['quantity_kg'] ?? '');
    $pricePerKg = trim($_POST['selling_price_per_kg'] ?? '');
    $gradeId = (int)($_POST['grade_id'] ?? 0);
    $remarkId = (int)($_POST['remark_id'] ?? 0);

    if ($error === '' && ($plantingId < 1 || $harvestDate === '' || !is_numeric($quantityKg) || (float)$quantityKg <= 0)) {
        $error = 'Choose a planting, enter a harvest date, and enter a positive yield amount.';
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $harvestDate);
    if ($error === '' && (!$date || $date->format('Y-m-d') !== $harvestDate)) {
        $error = 'Enter a valid harvest date.';
    } elseif ($error === '' && $date > new DateTimeImmutable('today')) {
        $error = 'A recorded harvest date cannot be in the future.';
    }

    if ($error === '') {
        $plantingQuery = $pdo->prepare(
            'SELECT planting_date FROM plantings
             WHERE planting_id = ? AND COALESCE(is_deleted, FALSE) = FALSE'
        );
        $plantingQuery->execute([$plantingId]);
        $plantingDate = $plantingQuery->fetchColumn();

        if ($plantingDate === false) {
            $error = 'Choose an active planting record.';
        } elseif ($harvestDate < $plantingDate) {
            $error = 'Harvest date cannot be before its planting date.';
        }
    }

    if ($error === '' && ($pricePerKg === '' || !is_numeric($pricePerKg) || (float)$pricePerKg < 0)) {
        $error = 'Enter a price per kilogram of zero or greater.';
    }
    if ($error === '' && $gradeId < 1) {
        $error = 'Choose a quality grade.';
    }
    if ($error === '' && $remarkId < 1) {
        $error = 'Choose a standard harvest remark.';
    }

    if ($error === '') {
        $gradeQuery = $pdo->prepare('SELECT grade_id FROM quality_grades WHERE grade_id = ?');
        $gradeQuery->execute([$gradeId]);
        $validGradeId = $gradeQuery->fetchColumn();

        $remarkQuery = $pdo->prepare('SELECT remark_id FROM harvest_remarks WHERE remark_id = ?');
        $remarkQuery->execute([$remarkId]);
        $validRemarkId = $remarkQuery->fetchColumn();

        if ($validGradeId === false || $validRemarkId === false) {
            $error = 'Choose valid quality and remark values.';
        } else {
            try {
                $columns = ['planting_id', 'harvest_date', 'quantity_kg', 'selling_price_per_kg'];
                $values = [
                    $plantingId,
                    $harvestDate,
                    (float)$quantityKg,
                    (float)$pricePerKg,
                ];

                // Old Supabase schemas still require these duplicate labels.
                // Keep them empty; the selected values are stored by lookup ID.
                foreach (['quality_grade', 'remarks'] as $legacyColumn) {
                    if (hasDBColumn('harvests', $legacyColumn)) {
                        $columns[] = $legacyColumn;
                        $values[] = '';
                    }
                }
                $columns[] = 'grade_id';
                $columns[] = 'remark_id';
                $values[] = $gradeId;
                $values[] = $remarkId;

                $placeholders = implode(', ', array_fill(0, count($columns), '?'));
                $insert = $pdo->prepare(
                    'INSERT INTO harvests (' . implode(', ', $columns) . ') VALUES (' . $placeholders . ')'
                );
                $insert->execute($values);

                $harvestId = (int)$pdo->lastInsertId('harvests_harvest_id_seq');
                logActivity("Recorded harvest #$harvestId for planting #$plantingId.");
                flashSuccess('Harvest record added.');
                header('Location: index.php');
                exit;
            } catch (PDOException $exception) {
                error_log('Harvest insert failed: ' . $exception->getMessage());
                $error = 'The harvest could not be saved. Check the selected planting and dropdown values.';
            }
        }
    }
}

$pageTitle = 'Record harvest';
require_once '../includes/header.php';
?>

<?= displayFlash() ?>
<section class="card mx-auto" style="max-width: 760px">
    <div class="card-header bg-white">
        <h1 class="h5 mb-0">Record a harvest</h1>
    </div>
    <div class="card-body">
        <?php if ($error !== ''): ?>
            <div class="alert alert-danger" role="alert"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

            <div class="mb-3">
                <label class="form-label" for="planting_id">Planting</label>
                <select class="form-select" id="planting_id" name="planting_id" required>
                    <option value="">Select a planting</option>
                    <?php foreach ($plantings as $planting): ?>
                        <option value="<?= (int)$planting['planting_id'] ?>"
                            <?= (($_POST['planting_id'] ?? '') == $planting['planting_id']) ? 'selected' : '' ?>>
                            #<?= (int)$planting['planting_id'] ?> ·
                            <?= htmlspecialchars($planting['crop_name']) ?> —
                            <?= htmlspecialchars($planting['farm_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="harvest_date">Harvest date</label>
                    <input class="form-control" id="harvest_date" type="date" name="harvest_date"
                           value="<?= htmlspecialchars($_POST['harvest_date'] ?? date('Y-m-d')) ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="quantity_kg">Yield (kg)</label>
                    <input class="form-control" id="quantity_kg" type="number" name="quantity_kg"
                           min="0.01" step="0.01" value="<?= htmlspecialchars($_POST['quantity_kg'] ?? '') ?>" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="selling_price_per_kg">Selling price per kg</label>
                    <input class="form-control" id="selling_price_per_kg" type="number"
                           name="selling_price_per_kg" min="0" step="0.01"
                           value="<?= htmlspecialchars($_POST['selling_price_per_kg'] ?? '0') ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="grade_id">Quality grade</label>
                    <select class="form-select" id="grade_id" name="grade_id" required>
                        <option value="">Select a grade</option>
                        <?php foreach ($grades as $grade): ?>
                            <option value="<?= (int)$grade['grade_id'] ?>"
                                <?= (($_POST['grade_id'] ?? '') == $grade['grade_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($grade['grade_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label" for="remark_id">Harvest remark</label>
                <select class="form-select" id="remark_id" name="remark_id" required>
                    <option value="">Select a remark</option>
                    <?php foreach ($remarks as $remark): ?>
                        <option value="<?= (int)$remark['remark_id'] ?>"
                            <?= (($_POST['remark_id'] ?? '') == $remark['remark_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($remark['remark_description']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="d-flex gap-2">
                <button class="btn btn-success" type="submit">Save harvest</button>
                <a class="btn btn-outline-secondary" href="index.php">Cancel</a>
            </div>
        </form>
    </div>
</section>

<?php require_once '../includes/footer.php'; ?>
