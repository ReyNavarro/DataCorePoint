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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, $_POST['csrf_token'] ?? '')) {
        $error = 'Your form session expired. Reload and try again.';
    } else {
        $action = $_POST['action'] ?? '';
        $id = (int)($_POST['user_id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $roleId = (int)($_POST['role_id'] ?? 0);
        $password = $_POST['password'] ?? '';
        if ($action === 'delete') {
            if ($id === (int)$_SESSION['user_id']) {
                $error = 'You cannot delete the account you are currently using.';
            } else {
                $target = $pdo->prepare('SELECT role_id, username FROM users WHERE user_id=?');
                $target->execute([$id]);
                $targetUser = $target->fetch();
                if (!$targetUser) {
                    $error = 'User not found.';
                } elseif ((int)$targetUser['role_id'] === 1 && (int)$pdo->query('SELECT COUNT(*) FROM users WHERE role_id=1')->fetchColumn() <= 1) {
                    $error = 'Keep at least one administrator account.';
                } elseif ((int)$pdo->query('SELECT COUNT(*) FROM activity_logs WHERE user_id='.(int)$id)->fetchColumn() > 0) {
                    $error = 'This user has activity history and cannot be deleted. Change the role instead.';
                } else {
                    $del = $pdo->prepare('DELETE FROM users WHERE user_id=?');
                    $del->execute([$id]);
                    logActivity("Deleted user account #$id ({$targetUser['username']}).");
                    flashSuccess('User deleted.');
                    header('Location: users.php');
                    exit;
                }
            }
        } elseif (($action === 'save') && $username !== '' && $roleId > 0) {
            try {
                $roleCheck = $pdo->prepare('SELECT COUNT(*) FROM roles WHERE role_id=?');
                $roleCheck->execute([$roleId]);
                if (!$roleCheck->fetchColumn()) {
                    $error = 'Choose a valid role.';
                } elseif ($id) {
                    if ($password !== '') {
                        $stmt = $pdo->prepare('UPDATE users SET username=?,role_id=?,password_hash=? WHERE user_id=?');
                        $stmt->execute([$username,$roleId,password_hash($password, PASSWORD_DEFAULT),$id]);
                    } else {
                        $stmt = $pdo->prepare('UPDATE users SET username=?,role_id=? WHERE user_id=?');
                        $stmt->execute([$username,$roleId,$id]);
                    }
                    logActivity("Updated user account #$id ($username).");
                    flashSuccess('User account updated.');
                    header('Location: users.php');
                    exit;
                } elseif ($password === '') {
                    $error = 'Enter a password for the new account.';
                } else {
                    $stmt = $pdo->prepare('INSERT INTO users (username,password_hash,role_id) VALUES (?,?,?)');
                    $stmt->execute([$username,password_hash($password, PASSWORD_DEFAULT),$roleId]);
                    $newUserId = (int)$pdo->lastInsertId('users_user_id_seq');
                    logActivity("Created user account #$newUserId ($username).");
                    flashSuccess('User account created.');
                    header('Location: users.php');
                    exit;
                }
            } catch (PDOException $e) {
                $error = 'Could not save the user. The username may already be in use.';
            }
        } else {
            $error = 'Enter a username and select a role.';
        }
    }
}
$roles = $pdo->query('SELECT role_id,role_name FROM roles ORDER BY role_id')->fetchAll();
$editId = (int)($_GET['edit'] ?? 0);
$editing = null;
if ($editId) {
    $q = $pdo->prepare('SELECT user_id,username,role_id FROM users WHERE user_id=?');
    $q->execute([$editId]);
    $editing = $q->fetch();
    if (!$editing) {
        flashError('User not found.');
        header('Location: users.php');
        exit;
    }
}
$users = $pdo->query('SELECT u.user_id,u.username,u.role_id,r.role_name FROM users u LEFT JOIN roles r ON r.role_id=u.role_id ORDER BY u.user_id')->fetchAll();
$pageTitle = 'User management';
require_once '../includes/header.php';
?>
<?= displayFlash() ?><?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<div class="dashboard-heading"><div><h1>User management</h1><p>Create and maintain accounts and roles.</p></div></div>
<div class="row g-3"><div class="col-lg-4"><div class="card"><div class="card-header bg-white fw-semibold"><?= $editing ? 'Edit account' : 'Add an account' ?></div><div class="card-body"><form method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>"><input type="hidden" name="action" value="save"><input type="hidden" name="user_id" value="<?= (int)($editing['user_id'] ?? 0) ?>"><div class="mb-3"><label class="form-label" for="username">Username</label><input class="form-control" id="username" name="username" maxlength="100" value="<?= htmlspecialchars($editing['username'] ?? '') ?>" required></div><div class="mb-3"><label class="form-label" for="role_id">Role</label><select class="form-select" id="role_id" name="role_id" required><option value="" disabled hidden <?= $editing ? '' : 'selected' ?>>Choose a role…</option><?php foreach ($roles as $role): ?><option value="<?= (int)$role['role_id'] ?>" <?= (($editing['role_id'] ?? '') == $role['role_id']) ? 'selected' : '' ?>><?= htmlspecialchars($role['role_name']) ?></option><?php endforeach; ?></select></div><div class="mb-3"><label class="form-label" for="password"><?= $editing ? 'New password (leave blank to keep current)' : 'Password' ?></label><input class="form-control" id="password" name="password" type="password" <?= $editing ? '' : 'required' ?> autocomplete="new-password"></div><div class="d-flex gap-2"><button class="btn btn-success" type="submit"><?= $editing ? 'Save changes' : 'Create account' ?></button><?php if ($editing): ?><a href="users.php" class="btn btn-outline-secondary">Cancel</a><?php endif; ?></div></form></div></div></div>
<div class="col-lg-8"><div class="card"><div class="card-header bg-white fw-semibold">Accounts</div><div class="card-body p-0 table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>ID</th><th>Username</th><th>Role</th><th>Actions</th></tr></thead><tbody><?php if (!$users): ?><tr><td colspan="4" class="text-center text-muted py-4">No user accounts found.</td></tr><?php else: foreach ($users as $user): ?><tr><td>#<?= (int)$user['user_id'] ?></td><td class="fw-semibold"><?= htmlspecialchars($user['username']) ?><?= (int)$user['user_id'] === (int)$_SESSION['user_id'] ? ' <span class="badge text-bg-light">You</span>' : '' ?></td><td><?= htmlspecialchars($user['role_name'] ?? 'Unassigned') ?></td><td><a class="btn btn-sm btn-outline-primary" href="?edit=<?= (int)$user['user_id'] ?>">Edit</a><?php if ((int)$user['user_id'] !== (int)$_SESSION['user_id']): ?><form method="post" class="d-inline" onsubmit="return confirm('Delete this account?')"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="user_id" value="<?= (int)$user['user_id'] ?>"><button class="btn btn-sm btn-outline-danger" type="submit">Delete</button></form><?php endif; ?></td></tr><?php endforeach; endif; ?></tbody></table></div></div></div></div>
<?php require_once '../includes/footer.php'; ?>
