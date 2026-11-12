<?php
require_once __DIR__ . '/../includes/session.php';
dcpStartSession();
require_once '../includes/auth.php';
requireLogin();
require_once '../includes/db.php';

$pdo = getDB();
$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    flashError('Invalid farmer ID.');
    header('Location: index.php');
    exit;
}

$farmer = $pdo->prepare("SELECT * FROM farmers WHERE farmer_id = ? AND is_deleted = FALSE");
$farmer->execute([$id]);
$farmer = $farmer->fetch();
if (!$farmer) {
    flashError('Farmer not found.');
    header('Location: index.php');
    exit;
}

$errors = [];
$data = $farmer;

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
        $stmt = $pdo->prepare("UPDATE farmers SET first_name=?, last_name=?, contact_number=?, address=? WHERE farmer_id=?");
        $stmt->execute([$data['first_name'], $data['last_name'], $data['contact_number'], $data['address'], $id]);
        $changedFields = describeActivityChanges($farmer, $data, [
            'first_name' => 'first name',
            'last_name' => 'last name',
            'contact_number' => 'contact number',
            'address' => 'address',
        ]);
        logActivity("Updated farmer #$id ({$data['first_name']} {$data['last_name']}); changed fields: $changedFields.");
        flashSuccess('Farmer updated successfully!');
        header('Location: index.php');
        exit;
    }
}

$pageTitle = 'Edit Farmer';
require_once '../includes/header.php';
?>
<?= displayFlash() ?>
<div class="mb-3">
  <a href="index.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to Farmers</a>
</div>
<div class="card" style="max-width:600px">
  <div class="card-header bg-warning fw-semibold"><i class="bi bi-pencil-fill me-2"></i>Edit Farmer #<?= $id ?></div>
  <div class="card-body">
    <?php if ($errors): ?>
    <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <form method="post" action="">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label fw-semibold">First Name <span class="text-danger">*</span></label>
          <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($data['first_name']) ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Last Name <span class="text-danger">*</span></label>
          <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($data['last_name']) ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Contact Number</label>
          <input type="text" name="contact_number" class="form-control" value="<?= htmlspecialchars($data['contact_number'] ?? '') ?>">
        </div>
        <div class="col-12">
          <label class="form-label fw-semibold">Address</label>
          <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($data['address'] ?? '') ?></textarea>
        </div>
        <div class="col-12 d-flex gap-2">
          <button type="submit" class="btn btn-warning"><i class="bi bi-check-lg me-1"></i>Update Farmer</button>
          <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
        </div>
      </div>
    </form>
  </div>
</div>
<?php require_once '../includes/footer.php'; ?>
