<?php
require_once __DIR__ . '/includes/session.php';
dcpStartSession();
require_once 'includes/auth.php';
requireLogin();
require_once 'includes/db.php';

$pdo = getDB();

// Summary counts
$totalFarmers   = $pdo->query("SELECT COUNT(*) FROM farmers WHERE COALESCE(is_deleted,FALSE)=FALSE")->fetchColumn();
$totalFarms     = $pdo->query("SELECT COUNT(*) FROM farms WHERE COALESCE(is_deleted,FALSE)=FALSE")->fetchColumn();
$totalCrops     = $pdo->query("SELECT COUNT(*) FROM crops WHERE COALESCE(is_deleted,FALSE)=FALSE")->fetchColumn();
$totalPlantings = $pdo->query("SELECT COUNT(*) FROM plantings WHERE COALESCE(is_deleted,FALSE)=FALSE")->fetchColumn();
$totalHarvests  = $pdo->query("SELECT COUNT(*) FROM harvests WHERE COALESCE(is_deleted,FALSE)=FALSE")->fetchColumn();
$totalQtyKg     = $pdo->query("SELECT COALESCE(SUM(quantity_kg),0) FROM harvests WHERE COALESCE(is_deleted,FALSE)=FALSE")->fetchColumn();

// Plantings by status (chart)
$plantingsByStatus = $pdo->query("
    SELECT ps.status_name, COUNT(p.planting_id) as cnt
    FROM planting_statuses ps
    LEFT JOIN plantings p ON p.status_id = ps.status_id AND COALESCE(p.is_deleted,FALSE)=FALSE
    GROUP BY ps.status_id, ps.status_name ORDER BY ps.status_id
")->fetchAll();

// Harvests by quality grade (chart)
$harvestsByGrade = $pdo->query("
    SELECT qg.grade_name, COUNT(h.harvest_id) as cnt
    FROM quality_grades qg
    LEFT JOIN harvests h ON h.grade_id = qg.grade_id AND COALESCE(h.is_deleted,FALSE)=FALSE
    GROUP BY qg.grade_id, qg.grade_name ORDER BY qg.grade_id
")->fetchAll();

// Recent plantings
$recentPlantings = $pdo->query("
    SELECT p.planting_id, f.farm_name, f.location, c.crop_name, p.planting_date, ps.status_name
    FROM plantings p
    JOIN farms f ON p.farm_id = f.farm_id
    JOIN crops c ON p.crop_id = c.crop_id
    JOIN planting_statuses ps ON p.status_id = ps.status_id
    WHERE COALESCE(p.is_deleted,FALSE)=FALSE
    ORDER BY p.planting_id DESC LIMIT 5
")->fetchAll();

// Recent harvests
$recentHarvests = $pdo->query("
    SELECT h.harvest_id, c.crop_name, f.farm_name, h.harvest_date, h.quantity_kg, COALESCE(qg.grade_name,'Unspecified') AS grade_name
    FROM harvests h
    JOIN plantings p ON h.planting_id = p.planting_id
    JOIN crops c ON p.crop_id = c.crop_id
    JOIN farms f ON p.farm_id = f.farm_id
    LEFT JOIN quality_grades qg ON h.grade_id = qg.grade_id
    WHERE COALESCE(h.is_deleted,FALSE)=FALSE
    ORDER BY h.harvest_id DESC LIMIT 5
")->fetchAll();

$pageTitle = 'Dashboard';
require_once 'includes/header.php';
?>
<?= displayFlash() ?>
<?php if (!empty($_GET['welcome'])): ?>
<main class="loading-screen" id="loadingScreen" aria-live="polite" aria-busy="true"><section class="loading-panel"><div class="loading-emblem"><i class="bi bi-grid-1x2-fill"></i></div><div class="loading-title">DataCorePoint</div><p class="loading-stage" id="loadingStage">Loading your dashboard and latest records…</p><div class="loading-track" role="progressbar" aria-label="Loading dashboard"><div class="loading-progress is-loading"></div></div></section></main>
<?php endif; ?>

<div class="dashboard-heading"><div><h1>Good to see you, <?= htmlspecialchars($_SESSION['username'] ?? 'there') ?></h1><p>Here is the latest overview of your agricultural operations.</p></div><span class="badge rounded-pill text-bg-light border px-3 py-2"><i class="bi bi-shield-check text-primary me-1"></i>Operations overview</span></div>
<!-- Summary Cards -->
<div class="row g-3 mb-4">
  <div class="col-sm-6 col-xl-2"><div class="card metric-card h-100 card-hover-lift anim-fade-in-up anim-delay-1"><div class="card-body d-flex align-items-center gap-3"><div class="metric-icon"><i class="bi bi-people-fill"></i></div><div><div class="metric-label">Farmers</div><div class="metric-value" data-counter="<?= (int)$totalFarmers ?>"><?= number_format($totalFarmers) ?></div></div></div></div></div>
  <div class="col-sm-6 col-xl-2"><div class="card metric-card h-100 card-hover-lift anim-fade-in-up anim-delay-2"><div class="card-body d-flex align-items-center gap-3"><div class="metric-icon"><i class="bi bi-geo-alt-fill"></i></div><div><div class="metric-label">Farms</div><div class="metric-value" data-counter="<?= (int)$totalFarms ?>"><?= number_format($totalFarms) ?></div></div></div></div></div>
  <div class="col-sm-6 col-xl-2"><div class="card metric-card h-100 card-hover-lift anim-fade-in-up anim-delay-3"><div class="card-body d-flex align-items-center gap-3"><div class="metric-icon"><i class="bi bi-flower2"></i></div><div><div class="metric-label">Crops</div><div class="metric-value" data-counter="<?= (int)$totalCrops ?>"><?= number_format($totalCrops) ?></div></div></div></div></div>
  <div class="col-sm-6 col-xl-2"><div class="card metric-card h-100 card-hover-lift anim-fade-in-up anim-delay-4"><div class="card-body d-flex align-items-center gap-3"><div class="metric-icon"><i class="bi bi-calendar2-week-fill"></i></div><div><div class="metric-label">Plantings</div><div class="metric-value" data-counter="<?= (int)$totalPlantings ?>"><?= number_format($totalPlantings) ?></div></div></div></div></div>
  <div class="col-sm-6 col-xl-2"><div class="card metric-card h-100 card-hover-lift anim-fade-in-up anim-delay-5"><div class="card-body d-flex align-items-center gap-3"><div class="metric-icon"><i class="bi bi-basket2-fill"></i></div><div><div class="metric-label">Harvests</div><div class="metric-value" data-counter="<?= (int)$totalHarvests ?>"><?= number_format($totalHarvests) ?></div></div></div></div></div>
  <div class="col-sm-6 col-xl-2"><div class="card metric-card h-100 card-hover-lift anim-fade-in-up anim-delay-6"><div class="card-body d-flex align-items-center gap-3"><div class="metric-icon"><i class="bi bi-box-seam"></i></div><div><div class="metric-label">Yield (kg)</div><div class="metric-value" data-counter="<?= round($totalQtyKg, 1) ?>" data-decimal="1" style="font-size:1.25rem"><?= number_format($totalQtyKg, 1) ?></div></div></div></div></div>
</div>
<!-- Charts -->
<div class="row g-3 mb-4">
  <div class="col-md-7">
    <div class="card h-100 anim-scale-in anim-scale-delay-1">
      <div class="card-header bg-white fw-semibold"><i class="bi bi-bar-chart-fill text-primary me-2"></i>Plantings by Status</div>
      <div class="card-body"><canvas id="plantingChart" height="120"></canvas><div class="chart-fallback" aria-label="Plantings by status">
<?php $plantingMax = max(array_map('intval', array_column($plantingsByStatus, 'cnt')) ?: [0]);
foreach ($plantingsByStatus as $status): $barWidth = $plantingMax ? round((int)$status['cnt'] / $plantingMax * 100) : 0; ?>
<div class="chart-fallback-row"><div class="d-flex justify-content-between small mb-1"><span><?= htmlspecialchars($status['status_name']) ?></span><strong><?= number_format($status['cnt']) ?></strong></div><div class="chart-fallback-track"><span style="width:<?= $barWidth ?>%"></span></div></div>
<?php endforeach; ?><?php if (empty($plantingsByStatus)): ?><p class="text-muted small mb-0">No planting status data yet.</p><?php endif; ?></div></div>
    </div>
  </div>
  <div class="col-md-5">
    <div class="card h-100 anim-scale-in anim-scale-delay-2">
      <div class="card-header bg-white fw-semibold"><i class="bi bi-pie-chart-fill text-primary me-2"></i>Harvests by Quality Grade</div>
      <div class="card-body d-flex align-items-center justify-content-center">
        <canvas id="gradeChart" height="140"></canvas><div class="chart-fallback grade-fallback" aria-label="Harvests by quality grade">
<?php $gradeTotal = array_sum(array_map('intval', array_column($harvestsByGrade, 'cnt')));
foreach ($harvestsByGrade as $grade): $gradeWidth = $gradeTotal ? round((int)$grade['cnt'] / $gradeTotal * 100) : 0; ?>
<div class="chart-fallback-row"><div class="d-flex justify-content-between small mb-1"><span><?= htmlspecialchars($grade['grade_name']) ?></span><strong><?= number_format($grade['cnt']) ?></strong></div><div class="chart-fallback-track"><span style="width:<?= $gradeWidth ?>%"></span></div></div>
<?php endforeach; ?><?php if (empty($harvestsByGrade)): ?><p class="text-muted small mb-0">No quality grade data yet.</p><?php endif; ?></div>
      </div>
    </div>
  </div>
</div>

<!-- Recent Tables -->
<div class="row g-3 mb-4">
  <div class="col-md-6">
    <div class="card anim-slide-left">
      <div class="card-header bg-white fw-semibold d-flex justify-content-between">
        <span><i class="bi bi-calendar2-week text-primary me-2"></i>Recent Plantings</span>
        <a href="plantings/index.php" class="btn btn-sm btn-outline-primary">View All</a>
      </div>
      <div class="card-body p-0">
        <table class="table table-hover table-sm mb-0">
          <thead class="table-light"><tr><th>Farm</th><th>Crop</th><th>Date</th><th>Status</th></tr></thead>
          <tbody>
          <?php foreach ($recentPlantings as $rp): ?>
          <tr>
            <td><?= htmlspecialchars($rp['farm_name']) ?><br><small class="text-muted"><?= htmlspecialchars($rp['location']) ?></small></td>
            <td><?= htmlspecialchars($rp['crop_name']) ?></td>
            <td><?= htmlspecialchars($rp['planting_date']) ?></td>
            <td><span class="badge bg-info"><?= htmlspecialchars($rp['status_name']) ?></span></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($recentPlantings)): ?><tr><td colspan="4" class="text-center text-muted py-3">No plantings yet</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card anim-slide-right">
      <div class="card-header bg-white fw-semibold d-flex justify-content-between">
        <span><i class="bi bi-basket2 text-primary me-2"></i>Recent Harvests</span>
        <a href="harvests/index.php" class="btn btn-sm btn-outline-primary">View All</a>
      </div>
      <div class="card-body p-0">
        <table class="table table-hover table-sm mb-0">
          <thead class="table-light"><tr><th>Crop</th><th>Farm</th><th>Date</th><th>Qty (kg)</th><th>Grade</th></tr></thead>
          <tbody>
          <?php foreach ($recentHarvests as $rh): ?>
          <tr>
            <td><?= htmlspecialchars($rh['crop_name']) ?></td>
            <td><?= htmlspecialchars($rh['farm_name']) ?></td>
            <td><?= htmlspecialchars($rh['harvest_date']) ?></td>
            <td><?= number_format($rh['quantity_kg'], 1) ?></td>
            <td><span class="badge bg-success"><?= htmlspecialchars($rh['grade_name']) ?></span></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($recentHarvests)): ?><tr><td colspan="5" class="text-center text-muted py-3">No harvests yet</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Module Navigation Cards -->
<div class="row g-3 dashboard-modules">
  <div class="col-6 col-md-3 col-xl-2">
    <a href="farmers/index.php" class="card text-center text-decoration-none h-100 card-hover anim-fade-in-up anim-delay-1">
      <div class="card-body py-4"><i class="bi bi-people-fill text-success fs-2 d-block mb-2"></i><span class="fw-semibold text-dark">Farmers</span></div>
    </a>
  </div>
  <div class="col-6 col-md-3 col-xl-2">
    <a href="farms/index.php" class="card text-center text-decoration-none h-100 anim-fade-in-up anim-delay-2">
      <div class="card-body py-4"><i class="bi bi-geo-alt-fill text-primary fs-2 d-block mb-2"></i><span class="fw-semibold text-dark">Farms</span></div>
    </a>
  </div>
  <div class="col-6 col-md-3 col-xl-2">
    <a href="crops/index.php" class="card text-center text-decoration-none h-100 anim-fade-in-up anim-delay-3">
      <div class="card-body py-4"><i class="bi bi-flower2 text-warning fs-2 d-block mb-2"></i><span class="fw-semibold text-dark">Crops</span></div>
    </a>
  </div>
  <div class="col-6 col-md-3 col-xl-2">
    <a href="plantings/index.php" class="card text-center text-decoration-none h-100 anim-fade-in-up anim-delay-4">
      <div class="card-body py-4"><i class="bi bi-calendar2-week-fill text-info fs-2 d-block mb-2"></i><span class="fw-semibold text-dark">Plantings</span></div>
    </a>
  </div>
  <div class="col-6 col-md-3 col-xl-2">
    <a href="harvests/index.php" class="card text-center text-decoration-none h-100 anim-fade-in-up anim-delay-5">
      <div class="card-body py-4"><i class="bi bi-basket2-fill text-danger fs-2 d-block mb-2"></i><span class="fw-semibold text-dark">Harvests</span></div>
    </a>
  </div>
  <div class="col-6 col-md-3 col-xl-2">
    <a href="reports/index.php" class="card text-center text-decoration-none h-100 anim-fade-in-up anim-delay-6">
      <div class="card-body py-4"><i class="bi bi-bar-chart-fill text-secondary fs-2 d-block mb-2"></i><span class="fw-semibold text-dark">Reports</span></div>
    </a>
  </div>
</div>

<script>
var plantingLabels = <?= json_encode(array_column($plantingsByStatus, 'status_name')) ?>;
var plantingData = <?= json_encode(array_map('intval', array_column($plantingsByStatus, 'cnt'))) ?>;
var gradeLabels = <?= json_encode(array_column($harvestsByGrade, 'grade_name')) ?>;
var gradeData = <?= json_encode(array_map('intval', array_column($harvestsByGrade, 'cnt'))) ?>;
var dashboardLoader = document.getElementById('loadingScreen');

function drawDashboardCharts() {
  if (typeof Chart !== 'undefined') {
    new Chart(document.getElementById('plantingChart'), {
      type: 'bar',
      data: {
        labels: plantingLabels,
        datasets: [{
          label: 'Plantings',
          data: plantingData,
          backgroundColor: ['#1e3a8a', '#2563eb', '#3b82f6', '#60a5fa', '#93c5fd', '#bfdbfe'],
          borderRadius: 6,
        }],
      },
      options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
      },
    });

    new Chart(document.getElementById('gradeChart'), {
      type: 'doughnut',
      data: {
        labels: gradeLabels,
        datasets: [{
          data: gradeData,
          backgroundColor: ['#1e3a8a', '#2563eb', '#3b82f6', '#60a5fa', '#93c5fd', '#bfdbfe', '#dbeafe'],
          hoverOffset: 10,
        }],
      },
      options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } },
      },
    });
    return;
  }

  document.querySelectorAll('.chart-fallback').forEach(function (element) {
    element.style.display = 'block';
  });
  document.querySelectorAll('#plantingChart, #gradeChart').forEach(function (canvas) {
    canvas.style.display = 'none';
  });
}

function animateDashboardMetrics() {
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

  document.querySelectorAll('[data-counter]').forEach(function (element) {
    var target = Number(element.dataset.counter);
    if (!Number.isFinite(target) || target <= 0) return;

    var decimals = Number(element.dataset.decimal || 0);
    var startTime = null;
    var duration = 720;
    var formatter = new Intl.NumberFormat(undefined, {
      minimumFractionDigits: decimals,
      maximumFractionDigits: decimals
    });

    function step(timestamp) {
      if (startTime === null) startTime = timestamp;
      var progress = Math.min((timestamp - startTime) / duration, 1);
      var eased = 1 - Math.pow(1 - progress, 3);
      element.textContent = formatter.format(target * eased);
      if (progress < 1) window.requestAnimationFrame(step);
    }

    element.textContent = formatter.format(0);
    window.requestAnimationFrame(step);
  });
}

function finishDashboardBoot() {
  animateDashboardMetrics();
  drawDashboardCharts();

  if (!dashboardLoader) return;

  // Paint the completed dashboard under the overlay before beginning its fade.
  window.requestAnimationFrame(function () {
    window.requestAnimationFrame(function () {
      var status = document.getElementById('loadingStage');
      var progress = dashboardLoader.querySelector('.loading-progress');

      if (status) status.textContent = 'Dashboard ready';
      if (progress) {
        progress.classList.remove('is-loading');
        progress.style.width = '100%';
      }

      dashboardLoader.setAttribute('aria-busy', 'false');
      dashboardLoader.classList.add('is-leaving');

      if (window.history && window.history.replaceState) {
        window.history.replaceState(null, '', 'index.php');
      }

      window.setTimeout(function () {
        dashboardLoader.remove();
      }, 700);
    });
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', finishDashboardBoot, { once: true });
} else {
  finishDashboardBoot();
}
</script>
<?php require_once 'includes/footer.php'; ?>
