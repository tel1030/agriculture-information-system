<?php $pageTitle = 'Reports';
require __DIR__ . '/layout_top.php';
?>
<section class="page-heading"><div><div class="eyebrow">INSIGHTS · LIVE DATABASE RESULTS</div><h1>Agricultural reports</h1><p>Review operational activity and recorded harvest totals.</p></div><button class="button button-secondary" type="button" data-print>Print reports</button></section>
<form class="panel report-filter" method="get" action="reports.php"><label class="filter-row">From date<input type="date" name="start" value="<?= e(
    $start,
) ?>"></label><label class="filter-row">To date<input type="date" name="end" value="<?= e(
    $end,
) ?>"></label><button class="button button-primary button-small" type="submit">Apply range</button><a class="button button-secondary button-small" href="reports.php">Clear</a><a class="button button-secondary button-small" href="reports.php?export=harvests&amp;start=<?= e(
    $start,
) ?>&amp;end=<?= e($end) ?>">Export harvest CSV</a></form>
<div class="report-total-grid"><article class="report-total"><small>Total harvested quantity</small><strong><?= number_format(
    $harvestTotal,
    2,
) ?> kg</strong></article><article class="report-total"><small>Estimated harvest value</small><strong><?= $harvestValue ===
 null
     ? 'Not recorded'
     : '₱ ' .
         number_format(
             $harvestValue,
             2,
         ) ?></strong></article><article class="report-total"><small>Harvest records in range</small><strong><?= number_format(
    count($harvestRows),
) ?></strong></article></div>
<div class="reports-grid"><section class="panel report-panel"><div class="eyebrow">FARMER DIRECTORY</div><h2>Farmers and their farms</h2><div class="table-scroll"><table class="report-table responsive-list-table"><thead><tr><th>ID ↑</th><th>Farmer</th><th>Address</th><th>Farm count</th><th>Farms</th></tr></thead><tbody><?php foreach (
    $farmers
    as $row
): ?><tr><td data-label="ID"><?= e($row['farmer_id']) ?></td><td data-label="Farmer"><?= e(
    $row['first_name'] . ' ' . $row['last_name'],
) ?></td><td data-label="Address"><?= e($row['address']) ?></td><td data-label="Farm count"><?= e($row['farm_count']) ?></td><td data-label="Farms"><?= e(
    $row['farm_names'] ?: '—',
) ?></td></tr><?php endforeach; ?></tbody></table></div></section>
<section class="panel report-panel"><div class="eyebrow">PLANTING ACTIVITY</div><h2>Crops planted per farm</h2><div class="table-scroll"><table class="report-table responsive-list-table"><thead><tr><th>ID ↑</th><th>Farm</th><th>Crop</th><th>Date</th><th>Area (ha)</th><th>Status</th></tr></thead><tbody><?php foreach (
    $plantingRows
    as $row
): ?><tr><td data-label="ID"><?= e($row['planting_id']) ?></td><td data-label="Farm"><?= e($row['farm_name']) ?></td><td data-label="Crop"><?= e(
    $row['crop_name'] . ' · ' . $row['variety'],
) ?></td><td data-label="Date"><?= e($row['planting_date']) ?></td><td data-label="Area (ha)"><?= number_format(
    (float) $row['area_planted'],
    2,
) ?></td><td data-label="Status"><?= e(
    $row['status_name'],
) ?></td></tr><?php endforeach; ?></tbody></table></div></section>
<section class="panel report-panel"><div class="eyebrow">STATUS SUMMARY</div><h2>Plantings by status</h2><div class="table-scroll"><table class="report-table responsive-list-table"><thead><tr><th>ID ↑</th><th>Status</th><th>Plantings</th></tr></thead><tbody><?php foreach (
    $byStatus
    as $row
): ?><tr><td data-label="ID"><?= e($row['status_id']) ?></td><td data-label="Status"><?= e(
    $row['status_name'],
) ?></td><td data-label="Plantings"><?= number_format(
    (int) $row['total'],
) ?></td></tr><?php endforeach; ?></tbody></table></div></section>
<section class="panel report-panel"><div class="eyebrow">HARVEST ACTIVITY</div><h2>Harvest records</h2><div class="table-scroll"><table class="report-table responsive-list-table"><thead><tr><th>ID ↑</th><th>Date</th><th>Farm</th><th>Crop</th><th>Quantity (kg)</th><th>Grade</th><th>Remark</th><th>Est. value</th></tr></thead><tbody><?php foreach (
    $harvestRows
    as $row
): ?><tr><td data-label="ID"><?= e($row['harvest_id']) ?></td><td data-label="Date"><?= e($row['harvest_date']) ?></td><td data-label="Farm"><?= e(
    $row['farm_name'],
) ?></td><td data-label="Crop"><?= e($row['crop_name'] . ' · ' . $row['variety']) ?></td><td data-label="Quantity (kg)"><?= number_format(
    (float) $row['quantity_kg'],
    2,
) ?></td><td data-label="Grade"><?= e($row['quality_name']) ?></td><td data-label="Remark"><?= e($row['remark_name']) ?></td><td data-label="Estimated value"><?= $row[
    'estimated_value'
] === null
    ? '—'
    : '₱ ' .
        number_format(
            (float) $row['estimated_value'],
            2,
        ) ?></td></tr><?php endforeach; ?></tbody></table></div></section></div>
<?php require __DIR__ . '/layout_bottom.php'; ?>
