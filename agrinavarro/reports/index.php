<?php
require_once __DIR__ . '/../includes/session.php';
dcpStartSession();
require_once '../includes/auth.php';
requireLogin();
require_once '../includes/db.php';
$pdo = getDB();

$from = trim($_GET['from'] ?? '');
$to = trim($_GET['to'] ?? '');
$dateError = '';
$validDate = static function ($value) {
    $date = DateTime::createFromFormat('!Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value;
};
if (($from !== '' && !$validDate($from)) || ($to !== '' && !$validDate($to))) {
    $dateError = 'Enter valid dates for the report range.';
    $from = $to = '';
} elseif ($from !== '' && $to !== '' && $from > $to) {
    $dateError = 'The start date must be on or before the end date.';
    $from = $to = '';
}

$harvestWhere = ['COALESCE(h.is_deleted,FALSE)=FALSE'];
$harvestParams = [];
if ($from !== '') {
    $harvestWhere[] = 'h.harvest_date >= ?';
    $harvestParams[] = $from;
}
if ($to !== '') {
    $harvestWhere[] = 'h.harvest_date <= ?';
    $harvestParams[] = $to;
}
$harvestFilter = implode(' AND ', $harvestWhere);
$plantingWhere = ['COALESCE(p.is_deleted,FALSE)=FALSE'];
$plantingParams = [];
if ($from !== '') {
    $plantingWhere[] = 'p.planting_date >= ?';
    $plantingParams[] = $from;
}
if ($to !== '') {
    $plantingWhere[] = 'p.planting_date <= ?';
    $plantingParams[] = $to;
}
$plantingFilter = implode(' AND ', $plantingWhere);

$activeCounts = [];
foreach (['farmers' => 'Farmers','farms' => 'Farms','crops' => 'Crops','plantings' => 'Plantings','harvests' => 'Harvests'] as $table => $label) {
    $activeCounts[$label] = (int)$pdo->query("SELECT COUNT(*) FROM $table WHERE COALESCE(is_deleted,FALSE)=FALSE")->fetchColumn();
}
$summaryStmt = $pdo->prepare("SELECT COALESCE(SUM(h.quantity_kg),0) AS total_kg, COALESCE(SUM(h.quantity_kg*COALESCE(h.selling_price_per_kg,0)),0) AS gross_value FROM harvests h WHERE $harvestFilter");
$summaryStmt->execute($harvestParams);
$summary = $summaryStmt->fetch();
$byCropStmt = $pdo->prepare("SELECT c.crop_name,COUNT(h.harvest_id) AS harvest_count,COALESCE(SUM(h.quantity_kg),0) AS total_kg,COALESCE(SUM(h.quantity_kg*COALESCE(h.selling_price_per_kg,0)),0) AS gross_value FROM harvests h JOIN plantings p ON h.planting_id=p.planting_id JOIN crops c ON p.crop_id=c.crop_id WHERE $harvestFilter AND COALESCE(p.is_deleted,FALSE)=FALSE GROUP BY c.crop_id,c.crop_name ORDER BY total_kg DESC,c.crop_name LIMIT 50");
$byCropStmt->execute($harvestParams);
$byCrop = $byCropStmt->fetchAll();
$byGradeStmt = $pdo->prepare("SELECT COALESCE(qg.grade_name,'Unspecified') AS grade_name,COUNT(*) AS harvest_count,COALESCE(SUM(h.quantity_kg),0) AS total_kg FROM harvests h LEFT JOIN quality_grades qg ON h.grade_id=qg.grade_id WHERE $harvestFilter GROUP BY qg.grade_name ORDER BY total_kg DESC");
$byGradeStmt->execute($harvestParams);
$byGrade = $byGradeStmt->fetchAll();
$farmerFarms = $pdo->query("SELECT fa.farmer_id,fa.first_name,fa.last_name,COUNT(f.farm_id) AS farm_count,COALESCE(SUM(f.area_hectares),0) AS area_hectares FROM farmers fa LEFT JOIN farms f ON f.farmer_id=fa.farmer_id AND COALESCE(f.is_deleted,FALSE)=FALSE WHERE COALESCE(fa.is_deleted,FALSE)=FALSE GROUP BY fa.farmer_id,fa.first_name,fa.last_name ORDER BY fa.last_name,fa.first_name LIMIT 50")->fetchAll();
$plantingsStmt = $pdo->prepare("SELECT COALESCE(ps.status_name,'Unspecified') AS status_name,COUNT(*) AS record_count FROM plantings p LEFT JOIN planting_statuses ps ON ps.status_id=p.status_id WHERE $plantingFilter GROUP BY ps.status_name ORDER BY status_name");
$plantingsStmt->execute($plantingParams);
$plantingsByStatus = $plantingsStmt->fetchAll();
$cropsPerFarmStmt = $pdo->prepare("SELECT f.farm_name,f.location,c.crop_name,COUNT(p.planting_id) AS planting_count,COALESCE(SUM(p.area_planted),0) AS area_planted FROM plantings p JOIN farms f ON f.farm_id=p.farm_id JOIN crops c ON c.crop_id=p.crop_id WHERE $plantingFilter AND COALESCE(f.is_deleted,FALSE)=FALSE AND COALESCE(c.is_deleted,FALSE)=FALSE GROUP BY f.farm_id,f.farm_name,f.location,c.crop_id,c.crop_name ORDER BY f.farm_name,c.crop_name LIMIT 100");
$cropsPerFarmStmt->execute($plantingParams);
$cropsPerFarm = $cropsPerFarmStmt->fetchAll();
$recentHarvestsStmt = $pdo->prepare("SELECT h.harvest_date,c.crop_name,f.farm_name,h.quantity_kg,h.selling_price_per_kg,COALESCE(qg.grade_name,'Unspecified') AS grade_name FROM harvests h LEFT JOIN plantings p ON p.planting_id=h.planting_id LEFT JOIN crops c ON c.crop_id=p.crop_id LEFT JOIN farms f ON f.farm_id=p.farm_id LEFT JOIN quality_grades qg ON qg.grade_id=h.grade_id WHERE $harvestFilter ORDER BY h.harvest_date DESC,h.harvest_id DESC LIMIT 50");
$recentHarvestsStmt->execute($harvestParams);
$recentHarvests = $recentHarvestsStmt->fetchAll();
$pageTitle = 'Reports';
require_once '../includes/header.php';
?>
<?= displayFlash() ?>
<div class="dashboard-heading"><div><h1>Operations reports</h1><p>Live summaries from Supabase. Date ranges apply to planting and harvest reports.</p></div><button type="button" class="btn btn-outline-primary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print report</button></div>
<form class="card card-body mb-3" method="get"><div class="row g-2 align-items-end"><div class="col-sm-4"><label class="form-label" for="from">From date</label><input class="form-control" type="date" id="from" name="from" value="<?= htmlspecialchars($from) ?>"></div><div class="col-sm-4"><label class="form-label" for="to">To date</label><input class="form-control" type="date" id="to" name="to" value="<?= htmlspecialchars($to) ?>"></div><div class="col-sm-4 d-flex gap-2"><button class="btn btn-primary" type="submit">Apply range</button><a class="btn btn-outline-secondary" href="index.php">Clear</a></div></div><?php if ($dateError): ?><p class="alert alert-danger mt-3 mb-0"><?= htmlspecialchars($dateError) ?></p><?php endif; ?></form>
<div class="row g-3 mb-4"><?php foreach ($activeCounts as $label => $count): ?><div class="col-6 col-xl"><div class="card metric-card h-100"><div class="card-body"><div class="metric-label"><?= htmlspecialchars($label) ?></div><div class="metric-value"><?= number_format($count) ?></div></div></div></div><?php endforeach; ?>
<div class="col-6 col-xl"><div class="card metric-card h-100"><div class="card-body"><div class="metric-label">Harvest yield (kg)</div><div class="metric-value"><?= number_format((float)$summary['total_kg'], 1) ?></div></div></div></div><div class="col-6 col-xl"><div class="card metric-card h-100"><div class="card-body"><div class="metric-label">Estimated harvest value</div><div class="metric-value" style="font-size:1.2rem">&#8369;<?= number_format((float)$summary['gross_value'], 2) ?></div></div></div></div></div>
<div class="row g-3 mb-3"><div class="col-lg-6"><div class="card h-100"><div class="card-header bg-white fw-semibold">Farmers and their farms</div><div class="table-responsive"><table class="table table-hover mb-0"><thead class="table-light"><tr><th>Farmer</th><th>Farms</th><th>Total area (ha)</th></tr></thead><tbody><?php if (!$farmerFarms): ?><tr><td colspan="3" class="text-center text-muted py-3">No farmers found.</td></tr><?php else: foreach ($farmerFarms as $r): ?><tr><td><?= htmlspecialchars($r['last_name'].', '.$r['first_name']) ?></td><td><?= (int)$r['farm_count'] ?></td><td><?= number_format((float)$r['area_hectares'], 2) ?></td></tr><?php endforeach; endif; ?></tbody></table></div></div></div>
<div class="col-lg-6"><div class="card h-100"><div class="card-header bg-white fw-semibold">Plantings by status</div><div class="table-responsive"><table class="table table-hover mb-0"><thead class="table-light"><tr><th>Status</th><th>Records</th></tr></thead><tbody><?php if (!$plantingsByStatus): ?><tr><td colspan="2" class="text-center text-muted py-3">No plantings found.</td></tr><?php else: foreach ($plantingsByStatus as $r): ?><tr><td><?= htmlspecialchars($r['status_name']) ?></td><td><?= (int)$r['record_count'] ?></td></tr><?php endforeach; endif; ?></tbody></table></div></div></div></div>
<div class="card mb-3"><div class="card-header bg-white fw-semibold">Crops planted per farm</div><div class="table-responsive"><table class="table table-hover mb-0"><thead class="table-light"><tr><th>Farm</th><th>Location</th><th>Crop</th><th>Plantings</th><th>Area (ha)</th></tr></thead><tbody><?php if (!$cropsPerFarm): ?><tr><td colspan="5" class="text-center text-muted py-3">No planted crops in this range.</td></tr><?php else: foreach ($cropsPerFarm as $r): ?><tr><td><?= htmlspecialchars($r['farm_name']) ?></td><td><?= htmlspecialchars($r['location']) ?></td><td><?= htmlspecialchars($r['crop_name']) ?></td><td><?= (int)$r['planting_count'] ?></td><td><?= number_format((float)$r['area_planted'], 2) ?></td></tr><?php endforeach; endif; ?></tbody></table></div></div>
<div class="row g-3 mb-3"><div class="col-lg-7"><div class="card h-100"><div class="card-header bg-white fw-semibold">Harvest yield and estimated value by crop</div><div class="table-responsive"><table class="table table-hover mb-0"><thead class="table-light"><tr><th>Crop</th><th>Records</th><th>Yield (kg)</th><th>Est. value</th></tr></thead><tbody><?php if (!$byCrop): ?><tr><td colspan="4" class="text-center text-muted py-3">No harvests in this range.</td></tr><?php else: foreach ($byCrop as $r): ?><tr><td><?= htmlspecialchars($r['crop_name']) ?></td><td><?= (int)$r['harvest_count'] ?></td><td><?= number_format((float)$r['total_kg'], 2) ?></td><td>&#8369;<?= number_format((float)$r['gross_value'], 2) ?></td></tr><?php endforeach; endif; ?></tbody></table></div></div></div><div class="col-lg-5"><div class="card h-100"><div class="card-header bg-white fw-semibold">Harvests by quality grade</div><div class="table-responsive"><table class="table table-hover mb-0"><thead class="table-light"><tr><th>Grade</th><th>Records</th><th>Yield (kg)</th></tr></thead><tbody><?php if (!$byGrade): ?><tr><td colspan="3" class="text-center text-muted py-3">No harvests in this range.</td></tr><?php else: foreach ($byGrade as $r): ?><tr><td><?= htmlspecialchars($r['grade_name']) ?></td><td><?= (int)$r['harvest_count'] ?></td><td><?= number_format((float)$r['total_kg'], 2) ?></td></tr><?php endforeach; endif; ?></tbody></table></div></div></div></div>
<div class="card"><div class="card-header bg-white fw-semibold">Harvest records</div><div class="table-responsive"><table class="table table-hover mb-0"><thead class="table-light"><tr><th>Date</th><th>Crop</th><th>Farm</th><th>Yield (kg)</th><th>Price/kg</th><th>Grade</th></tr></thead><tbody><?php if (!$recentHarvests): ?><tr><td colspan="6" class="text-center text-muted py-3">No harvests in this range.</td></tr><?php else: foreach ($recentHarvests as $r): ?><tr><td><?= htmlspecialchars($r['harvest_date']) ?></td><td><?= htmlspecialchars($r['crop_name'] ?? '—') ?></td><td><?= htmlspecialchars($r['farm_name'] ?? '—') ?></td><td><?= number_format((float)$r['quantity_kg'], 2) ?></td><td>&#8369;<?= number_format((float)$r['selling_price_per_kg'], 2) ?></td><td><?= htmlspecialchars($r['grade_name']) ?></td></tr><?php endforeach; endif; ?></tbody></table></div></div>
<?php require_once '../includes/footer.php'; ?>
