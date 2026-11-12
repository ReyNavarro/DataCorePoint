<?php
require_once __DIR__ . '/../includes/session.php';
dcpStartSession();
require_once '../includes/auth.php';
requireLogin();
require_once '../includes/db.php';
$pdo = getDB();
$nameSuggestions = $pdo->query(
    'SELECT DISTINCT first_name, last_name FROM farmers
     WHERE COALESCE(is_deleted, FALSE) = FALSE ORDER BY first_name, last_name LIMIT 100'
)->fetchAll();

$errors = [];
$data = ['first_name' => '','last_name' => '','contact_number' => '','address' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['first_name']     = trim($_POST['first_name'] ?? '');
    $data['last_name']      = trim($_POST['last_name'] ?? '');
    $data['contact_number'] = trim($_POST['contact_number'] ?? '');
    $data['address']        = trim($_POST['address'] ?? '');

    if ($data['first_name'] === '') {
        $errors[] = 'First name is required.';
    }
    if ($data['last_name'] === '') {
        $errors[] = 'Last name is required.';
    }
    if ($data['contact_number'] !== '' && !preg_match('/^[\d\s\+\-\(\)]{7,20}$/', $data['contact_number'])) {
        $errors[] = 'Contact number format is invalid.';
    }
    if (empty($errors)) {
        $pdo = getDB();
        try {
            $stmt = $pdo->prepare('INSERT INTO farmers (first_name, last_name, contact_number, address, is_deleted) VALUES (?, ?, ?, ?, FALSE)');
            $stmt->execute([$data['first_name'], $data['last_name'], $data['contact_number'], $data['address']]);
            $newId = $pdo->lastInsertId('farmers_farmer_id_seq');
            logActivity("Added farmer: {$data['first_name']} {$data['last_name']} (ID: $newId)");
            flashSuccess('Farmer added successfully!');
            header('Location: index.php');
            exit;
        } catch (PDOException $exception) {
            error_log('Farmer insert failed: ' . $exception->getMessage());
            $errors[] = 'The farmer could not be saved. Check the information and try again.';
        }
    }
}

$pageTitle = 'Add Farmer';
require_once '../includes/header.php';
?>
<?= displayFlash() ?>
<div class="mb-3">
  <a href="index.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to Farmers</a>
</div>
<div class="card" style="max-width:600px">
  <div class="card-header bg-success text-white fw-semibold"><i class="bi bi-person-plus-fill me-2"></i>Add New Farmer</div>
  <div class="card-body">
    <?php if ($errors): ?>
    <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <form method="post" action="">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label fw-semibold">First Name <span class="text-danger">*</span></label>
          <input type="text" name="first_name" list="farmer-first-name-suggestions" class="form-control" value="<?= htmlspecialchars($data['first_name']) ?>" required>
          <datalist id="farmer-first-name-suggestions"><?php foreach ($nameSuggestions as $suggestion): ?><option value="<?= htmlspecialchars($suggestion['first_name'], ENT_QUOTES, 'UTF-8') ?>"><?php endforeach; ?></datalist>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Last Name <span class="text-danger">*</span></label>
          <input type="text" name="last_name" list="farmer-last-name-suggestions" class="form-control" value="<?= htmlspecialchars($data['last_name']) ?>" required>
          <datalist id="farmer-last-name-suggestions"><?php foreach ($nameSuggestions as $suggestion): ?><option value="<?= htmlspecialchars($suggestion['last_name'], ENT_QUOTES, 'UTF-8') ?>"><?php endforeach; ?></datalist>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Contact Number</label>
          <input type="text" name="contact_number" class="form-control" value="<?= htmlspecialchars($data['contact_number']) ?>" placeholder="e.g. 09171234567">
        </div>
        <div class="col-12">
          <label class="form-label fw-semibold">Address</label>
          <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($data['address']) ?></textarea>
        </div>
        <div class="col-12 d-flex gap-2">
          <button type="submit" class="btn btn-success"><i class="bi bi-check-lg me-1"></i>Save Farmer</button>
          <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
        </div>
      </div>
    </form>
  </div>
</div>
<?php require_once '../includes/footer.php'; ?>
