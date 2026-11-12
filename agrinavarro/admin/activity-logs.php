<?php
require_once __DIR__ . '/../includes/session.php';
dcpStartSession();
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/db.php';

$pdo = getDB();
$search = trim($_GET['search'] ?? '');
$where = [];
$parameters = [];

if ($search !== '') {
    $where[] = '(u.username ILIKE ? OR a.log_action ILIKE ?)';
    $parameters[] = '%' . $search . '%';
    $parameters[] = '%' . $search . '%';
}

$whereSql = $where ? implode(' AND ', $where) : 'TRUE';
$perPage = 50;
$page = max(1, (int)($_GET['page'] ?? 1));
$countQuery = $pdo->prepare(
    'SELECT COUNT(*)
     FROM activity_logs a
     LEFT JOIN users u ON u.user_id = a.user_id
     WHERE ' . $whereSql
);
$countQuery->execute($parameters);
$totalRows = (int)$countQuery->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$logsQuery = $pdo->prepare(
    'SELECT a.log_id, a.log_action, a.created_at, u.username
     FROM activity_logs a
     LEFT JOIN users u ON u.user_id = a.user_id
     WHERE ' . $whereSql . '
     ORDER BY a.created_at DESC NULLS LAST, a.log_id DESC
     LIMIT ' . $perPage . ' OFFSET ' . $offset
);
$logsQuery->execute($parameters);
$logs = $logsQuery->fetchAll();

$pageTitle = 'Activity logs';
require_once '../includes/header.php';
?>

<?= displayFlash() ?>
<div class="dashboard-heading">
    <div>
        <h1>Activity logs</h1>
        <p>Review account activity and changes recorded across the system.</p>
    </div>
    <span class="badge rounded-pill text-bg-light border px-3 py-2">
        <?= number_format($totalRows) ?> recorded actions
    </span>
</div>

<form method="get" class="card mb-3">
    <div class="card-body">
        <label class="form-label" for="activitySearch">Search by user or action</label>
        <div class="input-group">
            <input class="form-control" id="activitySearch" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="Username or action">
            <button class="btn btn-outline-primary" type="submit">Search</button>
            <?php if ($search !== ''): ?><a class="btn btn-outline-secondary" href="activity-logs.php">Clear</a><?php endif; ?>
        </div>
    </div>
</form>

<section class="card">
    <div class="card-header bg-white fw-semibold">Recent actions</div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">Date and time</th>
                    <th scope="col">User</th>
                    <th scope="col">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$logs): ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">No matching activity records.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td class="text-muted">#<?= (int)$log['log_id'] ?></td>
                            <td class="text-nowrap">
                                <?= !empty($log['created_at'])
                                    ? htmlspecialchars(date('M j, Y g:i:s a', strtotime($log['created_at'])), ENT_QUOTES, 'UTF-8')
                                    : 'Time unavailable' ?>
                            </td>
                            <td><?= htmlspecialchars($log['username'] ?? 'Former user', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($log['log_action'], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php if ($totalPages > 1): ?>
    <nav class="mt-3" aria-label="Activity log pages">
        <ul class="pagination pagination-sm justify-content-center">
            <?php for ($number = 1; $number <= $totalPages; $number++): ?>
                <?php $query = $_GET;
                $query['page'] = $number; ?>
                <li class="page-item <?= $number === $page ? 'active' : '' ?>">
                    <a class="page-link" href="?<?= htmlspecialchars(http_build_query($query), ENT_QUOTES, 'UTF-8') ?>"><?= $number ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
