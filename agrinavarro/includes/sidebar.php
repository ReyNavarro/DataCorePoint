<?php
$currentScript = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME']);
$currentScript = basename(dirname($currentScript)) . '/' . basename($currentScript);
function isActive($paths)
{
    global $currentScript;
    if (!is_array($paths)) {
        $paths = [$paths];
    } foreach ($paths as $p) {
        if ($p === 'index.php' &&  $currentScript === basename(dirname(__DIR__)) . '/index.php') {
            return 'active';
        } if ($p !== 'index.php' && strpos($currentScript, $p) !== false) {
            return 'active';
        }
    } return '';
}
$base = getBaseUrl();
require_once __DIR__ . '/branding.php';

// Build user initials for avatar
$_sidebarUsername = $_SESSION['username'] ?? 'Guest';
$_sidebarInitials = '';
$_sidebarWords = preg_split('/[\s._-]+/', $_sidebarUsername);
foreach ($_sidebarWords as $_w) {
    if ($_w !== '') $_sidebarInitials .= mb_strtoupper(mb_substr($_w, 0, 1));
    if (mb_strlen($_sidebarInitials) >= 2) break;
}
if ($_sidebarInitials === '') $_sidebarInitials = 'U';
?>
<nav id="sidebar" class="premium-sidebar" aria-label="Main navigation">
  <div class="ps-inner">
    <!-- Brand -->
    <a class="ps-brand" href="<?= $base ?>/index.php" title="DataCorePoint">
      <span class="ps-brand-icon"><?php renderBrandMark(); ?></span>
      <span class="ps-brand-label">DataCorePoint</span>
    </a>

    <!-- Navigation -->
    <ul class="ps-nav">
      <li class="ps-nav-item">
        <a href="<?= $base ?>/index.php" class="ps-nav-link <?= isActive('index.php') ?>" data-tooltip="Dashboard">
          <span class="ps-nav-icon"><i class="bi bi-speedometer2"></i></span>
          <span class="ps-nav-label">Dashboard</span>
        </a>
      </li>
      <li class="ps-nav-item">
        <a href="<?= $base ?>/farmers/index.php" class="ps-nav-link <?= isActive('farmers/') ?>" data-tooltip="Farmers">
          <span class="ps-nav-icon"><i class="bi bi-people-fill"></i></span>
          <span class="ps-nav-label">Farmers</span>
        </a>
      </li>
      <li class="ps-nav-item">
        <a href="<?= $base ?>/farms/index.php" class="ps-nav-link <?= isActive('farms/') ?>" data-tooltip="Farms">
          <span class="ps-nav-icon"><i class="bi bi-geo-alt-fill"></i></span>
          <span class="ps-nav-label">Farms</span>
        </a>
      </li>
      <li class="ps-nav-item">
        <a href="<?= $base ?>/crops/index.php" class="ps-nav-link <?= isActive('crops/') ?>" data-tooltip="Crops">
          <span class="ps-nav-icon"><i class="bi bi-flower2"></i></span>
          <span class="ps-nav-label">Crops</span>
        </a>
      </li>
      <li class="ps-nav-item">
        <a href="<?= $base ?>/plantings/index.php" class="ps-nav-link <?= isActive('plantings/') ?>" data-tooltip="Plantings">
          <span class="ps-nav-icon"><i class="bi bi-calendar2-week-fill"></i></span>
          <span class="ps-nav-label">Plantings</span>
        </a>
      </li>
      <li class="ps-nav-item">
        <a href="<?= $base ?>/harvests/index.php" class="ps-nav-link <?= isActive('harvests/') ?>" data-tooltip="Harvests">
          <span class="ps-nav-icon"><i class="bi bi-basket2-fill"></i></span>
          <span class="ps-nav-label">Harvests</span>
        </a>
      </li>
      <li class="ps-nav-item">
        <a href="<?= $base ?>/reports/index.php" class="ps-nav-link <?= isActive('reports/') ?>" data-tooltip="Reports">
          <span class="ps-nav-icon"><i class="bi bi-bar-chart-fill"></i></span>
          <span class="ps-nav-label">Reports</span>
        </a>
      </li>

      <?php if (isAdmin()): ?>
      <li class="ps-nav-divider">
        <span class="ps-nav-divider-label">Admin</span>
      </li>
      <li class="ps-nav-item">
        <a href="<?= $base ?>/admin/users.php" class="ps-nav-link <?= isActive('admin/users') ?>" data-tooltip="Users">
          <span class="ps-nav-icon"><i class="bi bi-person-lock"></i></span>
          <span class="ps-nav-label">Users</span>
        </a>
      </li>
      <li class="ps-nav-item">
        <a href="<?= $base ?>/admin/archived.php" class="ps-nav-link <?= isActive('admin/archived') ?>" data-tooltip="Archived">
          <span class="ps-nav-icon"><i class="bi bi-archive-fill"></i></span>
          <span class="ps-nav-label">Archived</span>
        </a>
      </li>
      <li class="ps-nav-item">
        <a href="<?= $base ?>/admin/activity-logs.php" class="ps-nav-link <?= isActive('admin/activity-logs') ?>" data-tooltip="Activity Logs">
          <span class="ps-nav-icon"><i class="bi bi-clock-history"></i></span>
          <span class="ps-nav-label">Activity Logs</span>
        </a>
      </li>
      <?php endif; ?>
    </ul>

    <!-- Footer area -->
    <div class="ps-footer">
      <a href="<?= $base ?>/logout.php" class="ps-nav-link ps-logout" data-tooltip="Logout">
        <span class="ps-nav-icon"><i class="bi bi-box-arrow-left"></i></span>
        <span class="ps-nav-label">Logout</span>
      </a>
      <div class="ps-user">
        <span class="ps-avatar" title="<?= htmlspecialchars($_sidebarUsername) ?>"><?= htmlspecialchars($_sidebarInitials) ?></span>
        <span class="ps-user-name"><?= htmlspecialchars($_sidebarUsername) ?></span>
      </div>
    </div>
  </div>
</nav>
<button type="button" class="sidebar-backdrop" id="sidebarBackdrop" aria-label="Close navigation menu" hidden></button>
